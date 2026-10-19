<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    function user() {
        return $this->belongsTo(User::class, 'favourite_id', 'id');
    }
}
