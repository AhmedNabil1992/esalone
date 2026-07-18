<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PlayStationAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Invoke the PlayStation assistant agent.
     */
    public function invoke(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string',
            'conversation_id' => 'nullable|string',
        ]);

        $user = auth()->user();

        // Instantiate the PlayStationAssistant agent
        $assistant = new PlayStationAssistant;

        // Continue conversation if ID is provided, otherwise start a new one for user
        if ($request->filled('conversation_id')) {
            $response = $assistant
                ->continue($request->input('conversation_id'), as: $user)
                ->prompt($request->input('prompt'));
        } else {
            $response = $assistant
                ->forUser($user)
                ->prompt($request->input('prompt'));
        }

        return response()->json([
            'text' => $response->text,
            'conversation_id' => $response->conversationId,
        ]);
    }
}
