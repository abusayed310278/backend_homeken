<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'messages' => function ($query) {
                $query->latest()->take(1);
            }])
            ->get()
            ->map(function ($convo) use ($userId) {
                $otherUser = $convo->user_one_id == $userId ? $convo->userTwo : $convo->userOne;
                $lastMsg = $convo->messages->first();
                $unread = $convo->messages()->where('sender_id', '!=', $userId)->whereNull('read_at')->count();
                
                return [
                    'id' => $convo->id,
                    'name' => $otherUser->name ?? 'Unknown',
                    'image' => $otherUser->avatar_url ?? 'https://i.pravatar.cc/150?u='.$otherUser->id,
                    'message' => $lastMsg ? $lastMsg->body : '',
                    'time' => $lastMsg ? $lastMsg->created_at->diffForHumans() : '',
                    'count' => $unread > 0 ? (string)$unread : '',
                    'online' => false,
                    'other_user_id' => $otherUser->id
                ];
            });

        return response()->json(['error' => false, 'data' => $conversations]);
    }

    public function show(Request $request, $id)
    {
        $userId = $request->user()->id;
        $convo = Conversation::findOrFail($id);
        
        if ($convo->user_one_id != $userId && $convo->user_two_id != $userId) {
            abort(403);
        }

        $convo->messages()->where('sender_id', '!=', $userId)->whereNull('read_at')->update(['read_at' => now()]);

        $messages = $convo->messages()->orderBy('created_at', 'asc')->get()->map(function ($msg) use ($userId) {
            return [
                'id' => $msg->id,
                'text' => $msg->body,
                'isMe' => $msg->sender_id == $userId,
                'time' => $msg->created_at->format('h:i A')
            ];
        });

        return response()->json(['error' => false, 'data' => $messages]);
    }

    public function store(Request $request, $id)
    {
        $userId = $request->user()->id;
        $convo = Conversation::findOrFail($id);
        
        if ($convo->user_one_id != $userId && $convo->user_two_id != $userId) {
            abort(403);
        }

        $request->validate(['text' => 'required|string']);

        $message = $convo->messages()->create([
            'sender_id' => $userId,
            'body' => $request->text,
        ]);

        $data = [
            'id' => $message->id,
            'text' => $message->body,
            'isMe' => true,
            'time' => $message->created_at->format('h:i A'),
            'sender_id' => $userId
        ];

        broadcast(new MessageSent($convo->id, $data))->toOthers();

        $data['isMe'] = true;
        return response()->json(['error' => false, 'data' => $data]);
    }
    public function startConversation(Request $request)
    {
        $userId = $request->user()->id;
        $otherUserId = $request->provider_id;
        
        $request->validate(['provider_id' => 'required|integer']);

        $convo = Conversation::where(function ($query) use ($userId, $otherUserId) {
            $query->where('user_one_id', $userId)->where('user_two_id', $otherUserId);
        })->orWhere(function ($query) use ($userId, $otherUserId) {
            $query->where('user_one_id', $otherUserId)->where('user_two_id', $userId);
        })->first();

        if (!$convo) {
            $convo = Conversation::create([
                'user_one_id' => $userId,
                'user_two_id' => $otherUserId
            ]);
        }

        $otherUser = \Botble\RealEstate\Models\Account::find($otherUserId);

        return response()->json([
            'error' => false,
            'data' => [
                'id' => $convo->id,
                'name' => $otherUser->name ?? ($otherUser->first_name ?? 'Unknown'),
                'image' => $otherUser->avatar_url ?? 'https://i.pravatar.cc/150?u='.$otherUserId,
                'online' => true
            ]
        ]);
    }
}
