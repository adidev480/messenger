<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\Message;
use App\Models\User;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MessengerController extends Controller
{
    use FileUploadTrait;

    function index() : View {
        $favouriteList = Favourite::with('user:id,name,avatar')->where('user_id', Auth::user()->id)->get();
        return view('messenger.index', compact('favouriteList'));
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
        $favourite = Favourite::where(['user_id' => Auth::user()->id, 'favourite_id' => $fetch->id])->exists();
        $sharedPhotos = Message::where('from_id', Auth::user()->id)->where('to_id', $request->id)->whereNotNull('attachment')
        ->orWhere('from_id', $request->id)->where('to_id', Auth::user()->id)->whereNotNull('attachment')
        ->latest()->get();

        $content = '';
        foreach($sharedPhotos as $photo){
            $content .= view('messenger.components.gallery-item', compact('photo'))->render();
        }

        return response()->json([
            'fetch' => $fetch,
            'favourite' => $favourite,
            'shared_photos' => $content
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

    // fetch contacts from database
    function fetchContacts(Request $request) {

        $users = Message::join('users', function($join) {
            $join->on('messages.from_id', '=', 'users.id')
            ->orOn('messages.to_id', '=', 'users.id');
        })
        ->where(function($q) {
            $q->where('messages.from_id', Auth::user()->id)
            ->orWhere('messages.to_id', Auth::user()->id);
        })
        ->where('users.id','!=',Auth::user()->id)
        ->select('users.*', DB::raw('MAX(messages.created_at) max_created_at'))
        ->orderBy('max_created_at', 'desc')
        ->groupBy('users.id')
        ->paginate(7);

        if(count($users) > 0) {
            $contacts = "";
            foreach($users as $user) {
                $contacts .= $this->getContactItem($user);
            }
        }else {
            $contacts = "<p>Your contact list is empty!</p>";
        }

        return response()->json([
            'contacts' => $contacts,
            'last_page' =>  $users->lastPage()
        ]);


    }

    function getContactItem($user){
        $lastMessage = Message::where('from_id', Auth::user()->id)->where('to_id', $user->id)
        ->orWhere('from_id', $user->id)->where('to_id', Auth::user()->id)
        ->latest()->first();

        $unseenCounter = Message::where('from_id', $user->id)->where('to_id', Auth::user()->id)->where('seen', 0)
        ->count();

        // $avatar = $user->avatar;

        return view('messenger.components.contact-list-item', compact('lastMessage','unseenCounter','user'))->render();


    }

    // update contact item
    function updateContactItem(Request $request) {
        // get user data
        $user = User::where('id', $request->user_id)->first();
        if(!$user) {
            return response()->json([
                'message' => 'user not found'
            ], 401);
        }

        $contactItem = $this->getContactItem($user);
        return response()->json([
            'contact_item' => $contactItem
        ], 200);
    }

    // Make Seen
    function makeSeen(Request $request){
        Message::where('from_id', $request->id)
        ->where('to_id', Auth::user()->id)
        ->where('seen', 0)->update(['seen' => 1]);

        return true;
    }

    //add/remove to favourite list
    function favourite(Request $request){
        $query = Favourite::where(['user_id' => Auth::user()->id, 'favourite_id' => $request->id]);
        $favouriteStatus = $query->exists();
        if(!$favouriteStatus){
            $star = new Favourite();
            $star->user_id = Auth::user()->id;
            $star->favourite_id = $request->id;
            $star->save();
            return response(['status' => 'added']);
        }else{
            $query->delete();
            return response(['status' => 'removed']);
        }
    }

    //fetch favourite item list
    function fetchFavouritesList() {
        $list = Favourite::with('user:id,name,avatar')->where('user_id', Auth::user()->id)->get();
        $favourites = "";
        foreach($list as $item){
            $favourites .= view('messenger.components.favourite-item', compact('item'));
        }

        return response()->json([
            'favourite_list' => $favourites
        ]);
    }

}
