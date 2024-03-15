<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('all', function (User $user) {
    return $user?->id !== null ? true : false;
});
