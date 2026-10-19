<?php

use App\Http\Controllers\MessengerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/messenger', function () {
    return view('messenger');
})->middleware(['auth', 'verified'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    //Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::group(['middleware' => 'auth'], function(){
    Route::get('messenger', [MessengerController::class, 'index'])->name('home');
    Route::post('profile', [UserProfileController::class, 'update'])->name('profile.update');
    // search route
    Route::get('messenger/search', [MessengerController::class, 'search'])->name('messenger.search');
    Route::get('messenger/id-info', [MessengerController::class, 'fetchIdInfo'])->name('messenger.id-info');
    // send message
    Route::post('messenger/send-message', [MessengerController::class, 'sendMessage'])->name('messenger.send-message');
    // fetch message
    Route::get('messenger/fetch-messages', [MessengerController::class, 'fetchMessages'])->name('messenger.fetch-messages');
    // fetch contacts
    Route::get('messenger/fetch-contacts', [MessengerController::class, 'fetchContacts'])->name('messenger.fetch-contacts');
    // update contacts
    Route::get('messenger/update-contact-item', [MessengerController::class, 'updateContactItem'])->name('messenger.update-contact-item');
    // make seen
    Route::post('messenger/make-seen', [MessengerController::class, 'makeSeen'])->name('messenger.make-seen');
    // make favourite
    Route::post('messenger/favourite', [MessengerController::class, 'favourite'])->name('messenger.favourite');
    // fetch favourite
    Route::post('messenger/fetch-favourite', [MessengerController::class, 'fetchFavouritesList'])->name('messenger.fetch-favourite');
});
