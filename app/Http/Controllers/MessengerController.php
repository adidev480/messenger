<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessengerController extends Controller
{
    use FileUploadTrait;

    function index() : View {
        return view('messenger.index');
    }

    /** Search user profiles */
    function search(Request $request){
        $getRecords = null;
        $query = $request['query'];
        $records = User::where('id', '!=', Auth::id())
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('user_name', 'LIKE', "%{$query}%");
                    })
                    ->paginate(10);

        if($records->total() < 1) {
            $getRecords .= "<p class='text-center'>Nothing to show.</p>";
        }

        foreach($records as $record) {
            $getRecords .= view('messenger.components.search-item', compact('record'))->render();
        }

        return response()->json([
            'records' => $getRecords,
            'last_page' => $records->lastPage()
        ]);
    }

    //fetch userby id
    function fetchIdInfo(Request $request) {
        $fetch = User::where('id', $request['id'])->first();

        return response()->json([
            'fetch' => $fetch
        ]);
    }

    // send message
    function sendMessage(Request $request) {
        $request->validate([
            'message' => [],
            'id' => ['required', 'integer'],
            'temporaryMsgId' => ['required'],
            'attachment' => ['nullable', 'max:1024', 'image']

        ]);

        $attachmentPath = $this->uploadFile($request, 'attachment');
        $message = new Message();
        $message->from_id = Auth::user()->id;
        $message->to_id = $request->id;
        $message->body = $request->message;
        if($attachmentPath) $message->attachment = json_encode($attachmentPath);
        $message->save();

        return response()->json([
            'message' => $message->attachment ? $this->messageCard($message, true) : $this->messageCard($message),
            'tempID' => $request->temporaryMsgId
        ]);
    }

    function messageCard($message, $attachment = false) {
        return view("messenger.components.message-card", compact('message', 'attachment'))->render();
    }

    // fetch messages
    function fetchMessages(Request $request) {
       $messages = Message::where('from_id', Auth::user()->id)->where('to_id', $request->id)
            ->orWhere('from_id', $request->id)->where('to_id', Auth::user()->id)
            ->latest()->paginate(20);

       $response = [
            'last_page' => $messages->lastPage(),
            'last_message' => $messages->last(),
            'messages' => ''
       ];

       if(count($messages) < 1) {
            $response['messages'] = "<div class='d-flex justify-content-center align-items-center h-100'><p>Say 'hi' to start conversation</p>";
            return response()->json($response);
       }

       $allMessages = '';
       foreach($messages->reverse() as $message){
            $allMessages .= $this->messageCard($message, $message->attachment ? true : false);
       }

       $response['messages'] = $allMessages;

       return response()->json($response);

    }

}
