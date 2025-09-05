<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function list($conversationId)
    {
        $messages = Conversation::findOrFail($conversationId)->messages()->with('sender:id,name')->get();
        return response()->json($messages);
    }

    public function store(Request $request, $conversationId)
    {

        $request->validate(['content' => 'required|string']);

        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        $message = $conversation->messages()->create([
            'content' => $request->input('content'),
            'sender_id' => $user?->id,
        ]);

        broadcast(new MessageSent($message));

        return response()->json($message->load('sender:id,name'));
    }
}
