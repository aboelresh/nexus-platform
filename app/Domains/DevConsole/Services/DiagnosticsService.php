<?php

namespace App\Domains\DevConsole\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class DiagnosticsService
{
    public function getHealthCheck(): array
    {
        return [
            'database'  => $this->checkDatabase(),
            'redis'     => $this->checkRedis(),
            'storage'   => $this->checkStorage(),
            'queue'     => $this->checkQueue(),
            'broadcast' => $this->checkBroadcast(),
            'mail'      => $this->checkMail(),
        ];
    }

    public function getEnvironment(): array
    {
        return [
            'php'         => PHP_VERSION,
            'laravel'     => app()->version(),
            'environment' => app()->environment(),
            'debug'       => config('app.debug'),
            'timezone'    => config('app.timezone'),
            'locale'      => config('app.locale'),
            'drivers'     => [
                'database'   => config('database.default'),
                'cache'      => config('cache.default'),
                'queue'      => config('queue.default'),
                'broadcast'  => config('broadcasting.default'),
                'mail'       => config('mail.default'),
                'storage'    => config('filesystems.default'),
                'session'    => config('session.driver'),
            ],
            'extensions'  => [
                'redis'   => extension_loaded('redis'),
                'gd'      => extension_loaded('gd'),
                'curl'    => extension_loaded('curl'),
                'mbstring'=> extension_loaded('mbstring'),
                'openssl' => extension_loaded('openssl'),
                'pdo'     => extension_loaded('pdo'),
            ],
        ];
    }

    public function getPerformance(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak  = memory_get_peak_usage(true);

        return [
            'memory' => [
                'current'    => $this->formatBytes($memoryUsage),
                'peak'       => $this->formatBytes($memoryPeak),
                'limit'      => ini_get('memory_limit'),
                'percentage' => round(($memoryUsage / $this->parseBytes(ini_get('memory_limit'))) * 100, 2),
            ],
            'opcache' => function_exists('opcache_get_status') ? [
                'enabled'     => opcache_get_status()['opcache_enabled'] ?? false,
                'hit_rate'    => round(opcache_get_status()['opcache_statistics']['opcache_hit_rate'] ?? 0, 2),
                'cached_files'=> opcache_get_status()['opcache_statistics']['num_cached_scripts'] ?? 0,
            ] : ['enabled' => false],
            'uptime'  => $this->getServerUptime(),
        ];
    }

    public function getDatabaseStats(): array
    {
        try {
            $queries = DB::getQueryLog();

            $slowQueries = collect(DB::select("
                SELECT * FROM information_schema.processlist
                WHERE command != 'Sleep'
                AND time > 1
                LIMIT 10
            "))->map(fn($q) => (array) $q)->toArray();

            $tableStats = DB::select("
                SELECT
                    table_name,
                    table_rows,
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 20
            ", [config('database.connections.mysql.database')]);

            return [
                'connection'   => config('database.default'),
                'queries_count'=> count($queries),
                'slow_queries' => $slowQueries,
                'tables'       => collect($tableStats)->map(fn($t) => (array) $t)->toArray(),
                'connections'  => DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0,
                'version'      => DB::select("SELECT VERSION() as version")[0]->version ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getQueueStats(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();

            $recentFailed = DB::table('failed_jobs')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id'          => $job->id,
                        'uuid'        => $job->uuid,
                        'job'         => $payload['displayName'] ?? 'Unknown',
                        'failed_at'   => $job->failed_at,
                        'exception'   => substr($job->exception, 0, 200),
                    ];
                })->toArray();

            $avgTime = DB::table('jobs')
                ->selectRaw('AVG(attempts) as avg_attempts')
                ->first();

            return [
                'pending'       => $pending,
                'failed'        => $failed,
                'recent_failed' => $recentFailed,
                'avg_attempts'  => round($avgTime->avg_attempts ?? 0, 2),
                'driver'        => config('queue.default'),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getRedisStats(): array
    {
        try {
            $info = Redis::info();

            return [
                'connected'      => true,
                'version'        => $info['redis_version'] ?? 'unknown',
                'memory_used'    => $info['used_memory_human'] ?? 'unknown',
                'memory_peak'    => $info['used_memory_peak_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits'  => $info['keyspace_hits'] ?? 0,
                'keyspace_misses'=> $info['keyspace_misses'] ?? 0,
                'hit_rate'       => $this->calculateHitRate(
                    $info['keyspace_hits'] ?? 0,
                    $info['keyspace_misses'] ?? 0
                ),
                'uptime_seconds' => $info['uptime_in_seconds'] ?? 0,
                'keys'           => $this->getRedisKeysCount(),
            ];
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    public function getLogs(string $level = 'all', int $limit = 50): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return ['logs' => [], 'error' => 'Log file not found'];
        }

        $lines    = array_reverse(file($logFile));
        $logs     = [];
        $pattern  = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)/';
        $count    = 0;

        foreach ($lines as $line) {
            if ($count >= $limit) break;

            if (preg_match($pattern, trim($line), $matches)) {
                $logLevel = strtolower($matches[2]);

                if ($level !== 'all' && $logLevel !== $level) continue;

                $logs[] = [
                    'timestamp' => $matches[1],
                    'level'     => $logLevel,
                    'message'   => substr($matches[3], 0, 300),
                ];
                $count++;
            }
        }

        return [
            'logs'  => $logs,
            'total' => count($lines),
            'level' => $level,
        ];
    }

    public function getStorageStats(): array
    {
        try {
            $publicPath = storage_path('app/public');
            $totalSize  = $this->getDirectorySize($publicPath);

            $typeStats = [];
            $types     = ['images', 'videos', 'audio', 'documents', 'voice', 'avatars'];

            foreach ($types as $type) {
                $path = $publicPath . '/media/' . $type;
                if (is_dir($path)) {
                    $size  = $this->getDirectorySize($path);
                    $files = count(glob($path . '/*'));
                    $typeStats[$type] = [
                        'files' => $files,
                        'size'  => $this->formatBytes($size),
                    ];
                }
            }

            $mediaCount = DB::table('media')->count();
            $mediaByType = DB::table('media')
                ->selectRaw('type, COUNT(*) as count, SUM(size) as total_size')
                ->groupBy('type')
                ->get()
                ->map(fn($m) => (array) $m)
                ->toArray();

            return [
                'total_size'   => $this->formatBytes($totalSize),
                'total_files'  => $mediaCount,
                'by_type'      => $mediaByType,
                'disk_usage'   => $typeStats,
                'disk_free'    => $this->formatBytes(disk_free_space($publicPath)),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getSecurityStats(): array
    {
        try {
            $failedLogins = DB::table('audits')
                ->where('event', 'failed_login')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            $activeTokens = DB::table('personal_access_tokens')
                ->where('expires_at', '>', now())
                ->count();

            $expiredTokens = DB::table('personal_access_tokens')
                ->where('expires_at', '<=', now())
                ->count();

            $bannedUsers = DB::table('users')
                ->where('is_banned', true)
                ->count();

            $blockedRelations = DB::table('user_blocks')->count();

            return [
                'failed_logins_24h' => $failedLogins,
                'active_tokens'     => $activeTokens,
                'expired_tokens'    => $expiredTokens,
                'banned_users'      => $bannedUsers,
                'blocked_relations' => $blockedRelations,
                'sanctum_expiry'    => config('sanctum.expiration') . ' minutes',
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getSystemDoctor(): array
    {
        $checks  = [];
        $issues  = [];

        $dbCheck = $this->checkDatabase();
        $checks['database'] = $dbCheck;
        if (!$dbCheck['status']) {
            $issues[] = ['problem' => 'Database connection failed', 'fix' => 'Check DB_HOST, DB_PORT, DB_USERNAME in .env'];
        }

        $redisCheck = $this->checkRedis();
        $checks['redis'] = $redisCheck;
        if (!$redisCheck['status']) {
            $issues[] = ['problem' => 'Redis not connected', 'fix' => 'Start Redis server or check REDIS_HOST in .env'];
        }

        $queueCheck = $this->checkQueue();
        $checks['queue'] = $queueCheck;
        if (!$queueCheck['status']) {
            $issues[] = ['problem' => 'Queue has failed jobs', 'fix' => 'Run: php artisan queue:retry all'];
        }

        $storageCheck = $this->checkStorage();
        $checks['storage'] = $storageCheck;
        if (!$storageCheck['status']) {
            $issues[] = ['problem' => 'Storage link missing', 'fix' => 'Run: php artisan storage:link'];
        }

        $broadcastCheck = $this->checkBroadcast();
        $checks['broadcast'] = $broadcastCheck;
        if (!$broadcastCheck['status']) {
            $issues[] = ['problem' => 'Reverb WebSocket not running', 'fix' => 'Run: php artisan reverb:start'];
        }

        return [
            'checks'      => $checks,
            'issues'      => $issues,
            'healthy'     => empty($issues),
            'score'       => round((count(array_filter($checks, fn($c) => $c['status'])) / count($checks)) * 100),
            'checked_at'  => now()->toISOString(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $version = DB::select("SELECT VERSION() as v")[0]->v;
            return ['status' => true, 'message' => 'Connected', 'version' => $version];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
{
    $link   = public_path('storage');
    $exists = file_exists($link);
    return [
        'status'  => $exists,
        'message' => $exists ? 'Storage accessible' : 'Storage link missing - run php artisan storage:link',
    ];
}

    private function checkQueue(): array
    {
        try {
            $failed = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();
            return [
                'status'  => true,
                'message' => "Pending: {$pending}, Failed: {$failed}",
                'failed'  => $failed,
                'pending' => $pending,
            ];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkBroadcast(): array
{
    try {
        $host       = config('reverb.servers.reverb.hostname', 'localhost');
        $port       = config('reverb.servers.reverb.port', 8080);

        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);

        if ($connection) {
            fclose($connection);
            return ['status' => true, 'message' => "Reverb running on {$host}:{$port}"];
        }

        $connection2 = @stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2);
        if ($connection2) {
            fclose($connection2);
            return ['status' => true, 'message' => "Reverb running on {$host}:{$port}"];
        }

        return ['status' => false, 'message' => "Reverb not running - run php artisan reverb:start"];
    } catch (\Exception $e) {
        return ['status' => false, 'message' => $e->getMessage()];
    }
}

    private function checkMail(): array
    {
        $driver = config('mail.default');
        return ['status' => true, 'message' => "Driver: {$driver}", 'driver' => $driver];
    }

    private function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        if ($total === 0) return 0;
        return round(($hits / $total) * 100, 2);
    }

    private function getRedisKeysCount(): int
    {
        try {
            $info = Redis::info('keyspace');
            preg_match('/keys=(\d+)/', json_encode($info), $matches);
            return (int) ($matches[1] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getDirectorySize(string $path): int
    {
        $size = 0;
        if (!is_dir($path)) return 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    private function parseBytes(string $size): int
    {
        $unit  = strtolower(substr($size, -1));
        $value = (int) $size;
        return match($unit) {
            'g' => $value * 1073741824,
            'm' => $value * 1048576,
            'k' => $value * 1024,
            default => $value,
        };
    }

    private function getServerUptime(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return 'N/A on Windows';
        }
        $uptime = shell_exec('uptime -p');
        return trim($uptime ?? 'unknown');
    }
}