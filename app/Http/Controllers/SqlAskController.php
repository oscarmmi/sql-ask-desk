<?php

namespace App\Http\Controllers;

use App\Models\AskQuery;
use App\Services\SqlAsk\SqlAskFailed;
use App\Services\SqlAsk\SqlAskRejected;
use App\Services\SqlAsk\SqlAskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SqlAskController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'recentQueries' => AskQuery::query()->latest()->limit(5)->get([
                'id', 'question', 'sql_text', 'row_count', 'status', 'preview_json', 'created_at',
            ]),
        ]);
    }

    public function ask(Request $request, SqlAskService $service): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        try {
            return response()->json(['query' => $this->serialize($service->ask($validated['question']))]);
        } catch (SqlAskRejected $exception) {
            return response()->json([
                'query' => $this->serialize($exception->query),
                'message' => 'The generated statement did not pass the read-only SQL guard.',
            ], 422);
        } catch (SqlAskFailed $exception) {
            return response()->json([
                'query' => $this->serialize($exception->query),
                'message' => 'The request could not be completed. Please try again.',
            ], 503);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(AskQuery $query): array
    {
        return [
            'id' => $query->id,
            'question' => $query->question,
            'sql_text' => $query->sql_text,
            'row_count' => $query->row_count,
            'status' => $query->status,
            'rows' => $query->preview_json ?? [],
        ];
    }
}
