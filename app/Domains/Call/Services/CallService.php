<?php

namespace App\Domains\Call\Services;

use App\Domains\Call\Events\CallAnswered;
use App\Domains\Call\Events\CallEnded;
use App\Domains\Call\Events\CallInitiated;
use App\Domains\Call\Events\WebRTCSignal;
use App\Domains\Call\Models\Call;
use App\Domains\Call\Models\CallParticipant;
use App\Domains\Chat\Models\Conversation;
use App\Domains\User\Models\User;
use Illuminate\Validation\ValidationException;

class CallService
{
    public function initiateCall(User $caller, int $conversationId, string $type): Call
    {
        $conversation = Conversation::findOrFail($conversationId);

        if (!$conversation->hasParticipant($caller->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['ليس لديك صلاحية الوصول لهذه المحادثة.'],
            ]);
        }

        $activeCall = Call::where('conversation_id', $conversationId)
            ->whereIn('status', ['ringing', 'ongoing'])
            ->first();

        if ($activeCall) {
            throw ValidationException::withMessages([
                'call' => ['يوجد مكالمة نشطة بالفعل في هذه المحادثة.'],
            ]);
        }

        $call = Call::create([
            'conversation_id' => $conversationId,
            'initiated_by'    => $caller->id,
            'type'            => $type,
            'status'          => 'ringing',
        ]);

        CallParticipant::create([
            'call_id'   => $call->id,
            'user_id'   => $caller->id,
            'status'    => 'joined',
            'joined_at' => now(),
            'camera_on' => $type === 'video',
        ]);

        $targetUserIds = $conversation->participants()
            ->where('user_id', '!=', $caller->id)
            ->pluck('user_id')
            ->toArray();

        broadcast(new CallInitiated($call, $caller, $targetUserIds));

        return $call->load(['initiator', 'participants.user']);
    }

    public function answerCall(User $user, int $callId): Call
    {
        $call = Call::findOrFail($callId);

        if ($call->status !== 'ringing') {
            throw ValidationException::withMessages([
                'call' => ['المكالمة لم تعد متاحة.'],
            ]);
        }

        if (!$call->conversation->hasParticipant($user->id)) {
            throw ValidationException::withMessages([
                'call' => ['ليس لديك صلاحية الانضمام لهذه المكالمة.'],
            ]);
        }

        $call->update([
            'status'     => 'ongoing',
            'started_at' => now(),
        ]);

        CallParticipant::updateOrCreate(
            ['call_id' => $call->id, 'user_id' => $user->id],
            ['status' => 'joined', 'joined_at' => now(), 'camera_on' => $call->type === 'video']
        );

        broadcast(new CallAnswered($call->fresh(), $user));

        return $call->fresh(['initiator', 'participants.user']);
    }

    public function rejectCall(User $user, int $callId): Call
    {
        $call = Call::findOrFail($callId);

        if ($call->status !== 'ringing') {
            throw ValidationException::withMessages([
                'call' => ['المكالمة لم تعد متاحة.'],
            ]);
        }

        CallParticipant::updateOrCreate(
            ['call_id' => $call->id, 'user_id' => $user->id],
            ['status' => 'rejected']
        );

        $call->update(['status' => 'rejected', 'ended_at' => now()]);
broadcast(new CallEnded($call->fresh()));
return $call->fresh(['initiator', 'participants.user']);
    }

    public function endCall(User $user, int $callId): Call
    {
        $call = Call::findOrFail($callId);

        if (!$call->isActive()) {
            throw ValidationException::withMessages([
                'call' => ['المكالمة غير نشطة.'],
            ]);
        }

        $participant = CallParticipant::where('call_id', $callId)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            throw ValidationException::withMessages([
                'call' => ['أنت لست مشاركاً في هذه المكالمة.'],
            ]);
        }

        $participant->update([
            'status'  => 'left',
            'left_at' => now(),
        ]);

        $activeParticipants = CallParticipant::where('call_id', $callId)
            ->where('status', 'joined')
            ->count();

        if ($activeParticipants === 0 || $call->initiated_by === $user->id) {
            $startedAt = $call->started_at;
            $duration = $startedAt ? max(0, (int) now()->diffInSeconds($startedAt)) : 0;

            $call->update([
                'status'   => 'ended',
                'ended_at' => now(),
                'duration' => $duration,
            ]);

            CallParticipant::where('call_id', $callId)
                ->where('status', 'joined')
                ->update(['status' => 'left', 'left_at' => now()]);

            broadcast(new CallEnded($call->fresh()));
        }

        return $call->fresh(['initiator', 'participants.user']);
    }

    public function handleSignal(User $sender, int $callId, int $toUserId, string $signalType, array $payload): void
    {
        $validSignalTypes = ['offer', 'answer', 'ice-candidate', 'renegotiate'];

        if (!in_array($signalType, $validSignalTypes)) {
            throw ValidationException::withMessages([
                'signal_type' => ['نوع الإشارة غير صالح.'],
            ]);
        }

        $call = Call::findOrFail($callId);

        if (!$call->isActive()) {
            throw ValidationException::withMessages([
                'call' => ['المكالمة غير نشطة.'],
            ]);
        }

        broadcast(new WebRTCSignal($callId, $sender->id, $toUserId, $signalType, $payload));
    }

    public function toggleMute(User $user, int $callId): CallParticipant
    {
        $participant = CallParticipant::where('call_id', $callId)
            ->where('user_id', $user->id)
            ->where('status', 'joined')
            ->firstOrFail();

        $participant->update(['is_muted' => !$participant->is_muted]);

        return $participant;
    }

    public function toggleCamera(User $user, int $callId): CallParticipant
    {
        $participant = CallParticipant::where('call_id', $callId)
            ->where('user_id', $user->id)
            ->where('status', 'joined')
            ->firstOrFail();

        $participant->update(['camera_on' => !$participant->camera_on]);

        return $participant;
    }

    public function getCallHistory(User $user, int $perPage = 20)
    {
        return Call::whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['initiator', 'participants.user'])
            ->latest()
            ->paginate($perPage);
    }

    public function getActiveCall(int $conversationId): ?Call
    {
        return Call::where('conversation_id', $conversationId)
            ->whereIn('status', ['ringing', 'ongoing'])
            ->with(['initiator', 'participants.user'])
            ->first();
    }

    public function missedCall(int $callId): void
    {
        $call = Call::find($callId);
        if ($call && $call->status === 'ringing') {
            $call->update(['status' => 'missed', 'ended_at' => now()]);
            broadcast(new CallEnded($call->fresh()));
        }
    }
}