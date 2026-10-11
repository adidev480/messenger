<?php

namespace App\Http\Controllers;

use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    use FileUploadTrait;
    function update(Request $request) {
       $request->validate([
            'avatar' => ['nullable', 'image', 'max:500'],
            'name' => ['required','string','max:50'],
            'user_id' => ['required','string','max:50', Rule::unique('users', 'user_name')->ignore(auth()->id()),],
            'email' => ['required','email','max:100']
       ]);



       $avatarPath = $this->uploadFile($request, 'avatar');
       $user = Auth::user();
       if($avatarPath) $user->avatar = $avatarPath;
       $user->name = $request->name;
       $user->user_name = $request->user_id;
       $user->email = $request->email;
       if($request->filled('current_password')){
        $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required', 'string', 'min:8','confirmed']
        ]);
        $user->password = bcrypt($request->password);
       }
       $user->save();
       notyf()->success('Updated Successfully!');
       return response(['message'=> 'Updated Successfully!'], 200);
    }
}
