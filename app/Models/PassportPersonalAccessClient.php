<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passport\PersonalAccessClient;

class PassportPersonalAccessClient extends PersonalAccessClient
{
    use HasUuids;
}
