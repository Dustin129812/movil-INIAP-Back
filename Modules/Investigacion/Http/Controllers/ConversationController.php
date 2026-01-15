<?php

namespace Modules\Investigacion\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Investigacion\Entities\Conversation;
use Modules\Investigacion\Events\ConversationCreated;

class ConversationController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $conversation = Conversation::firstOrCreate(['user_id' => $user->id]);

        if ($conversation->wasRecentlyCreated) {
            broadcast(new ConversationCreated($conversation->load('user:id,name')))->toOthers();
        }

        return response()->json($conversation);
    }

    public function index()
    {
        $admin = Auth::user();

        $conversations = Conversation::with(['user:id,name'])
            ->withLastMessage()
            ->orderByLastMessage()
            ->get();

        $conversations->each(function ($conversation) use ($admin) {
            $pivot = $admin->readConversations()->where('conversation_id', $conversation->id)->first();
            $lastRead = $pivot ? $pivot->pivot->last_read_at : null;

            if ($lastRead) {
                $conversation->unread_count = $conversation->messages()->where('created_at', '>', $lastRead)->count();
            } else {
                $conversation->unread_count = $conversation->messages()->count();
            }
        });

        return response()->json($conversations);
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        $admin = Auth::user();
        $admin->readConversations()->syncWithoutDetaching([
            $conversation->id => ['last_read_at' => now()]
        ]);

        return response()->json(['status' => 'success']);
    }
}
