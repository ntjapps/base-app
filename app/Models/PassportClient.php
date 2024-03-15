<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passport\Client;

class PassportClient extends Client
{
    use HasUuids;
}
