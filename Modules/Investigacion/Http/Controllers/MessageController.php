<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Investigacion\Entities\Conversation;
use Modules\Investigacion\Events\MessageSent;

class MessageController extends Controller
{
    public function list($conversationId)
    {
        $messages = Conversation::findOrFail($conversationId)->messages()->with('sender:id,name')->get();
        return response()->json($messages);
    }

    public function store(Request $request, $conversationId)
    {
        $request->validate([
            'content' => 'nullable|string|max:4096',
            'image' => 'nullable|image|max:5120',
        ]);

        if (!$request->hasFile('image') && !$request->filled('content')) {
            return response()->json([
                'message' => 'El mensaje debe contener texto o una imagen.',
                'errors' => ['content' => ['El mensaje debe contener texto o una imagen.']]
            ], 422);
        }

        $conversation = Conversation::findOrFail($conversationId);
        $user = $request->user();

        $filePath = null;
        $messageType = 'text';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filePath = $file->store('chat_images', 'public');
            $messageType = 'image';
        }

        $message = $conversation->messages()->create([
            'sender_id' => $user?->id,
            'content' => $request->input('content'),
            'message_type' => $messageType,
            'file_path' => $filePath,
        ]);

        $message->load('sender:id,name');

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }
}
