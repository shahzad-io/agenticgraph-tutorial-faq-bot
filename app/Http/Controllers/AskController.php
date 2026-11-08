<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shahzad\AgenticGraph\Facades\AgenticGraph;
use Shahzad\AgenticGraph\State\State;

final class AskController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $handle = AgenticGraph::stream('faq-bot', new State([
            'question' => $validated['question'],
        ]));

        $streamUrl = $handle->stream()->streamUrl;

        if ($streamUrl !== null) {
            $streamUrl .= (str_contains($streamUrl, '?') ? '&' : '?')
                .'execution_id='.urlencode($handle->id());
        }

        return response()->json([
            'execution_id' => $handle->id(),
            'stream_url' => $streamUrl,
        ]);
    }
}
