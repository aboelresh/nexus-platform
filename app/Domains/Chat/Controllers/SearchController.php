<?php

namespace App\Domains\Chat\Controllers;

use App\Domains\Chat\Resources\ConversationResource;
use App\Domains\Chat\Resources\MessageResource;
use App\Domains\Chat\Services\SearchService;
use App\Infrastructure\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    ) {}

    public function messages(Request $request): JsonResponse
    {
        $request->validate([
            'q'               => ['required', 'string', 'min:2', 'max:100'],
            'conversation_id' => ['sometimes', 'integer', 'exists:conversations,id'],
            'per_page'        => ['sometimes', 'integer', 'min:5', 'max:50'],
        ]);

        $results = $this->searchService->searchMessages(
            $request->input('q'),
            $request->user(),
            $request->input('conversation_id'),
            $request->input('per_page', 20)
        );

        return response()->json([
            'status' => true,
            'data'   => MessageResource::collection($results),
            'meta'   => [
                'query'        => $request->input('q'),
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $request->validate([
            'q'        => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:50'],
        ]);

        $results = $this->searchService->searchConversations(
            $request->input('q'),
            $request->user(),
            $request->input('per_page', 20)
        );

        return response()->json([
            'status' => true,
            'data'   => ConversationResource::collection($results),
            'meta'   => [
                'query'        => $request->input('q'),
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'total'        => $results->total(),
            ],
        ]);
    }
}