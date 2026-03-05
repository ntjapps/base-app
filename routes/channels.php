<?php

use App\Interfaces\PermissionConstants;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return $user->id === $id;
});

Broadcast::channel('all', function (User $user) {
    return $user?->id !== null ? true : false;
});

Broadcast::channel('whatsapp.messages', function (User $user) {
    return $user->hasPermissionTo(PermissionConstants::WHATSAPP_VIEW);
});

Broadcast::channel('userman.event', function (User $user) {
    return $user->hasPermissionTo(PermissionConstants::MENU_USERS);
});

Broadcast::channel('roleman.event', function (User $user) {
    return $user->hasPermissionTo(PermissionConstants::MENU_ROLES);
});

Broadcast::channel('settings.event', function (User $user) {
    return $user->hasPermissionTo(PermissionConstants::MENU_SETTINGS);
});
