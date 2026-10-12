<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MessengerController extends Controller
{
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
}
