<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use \App\Concerns\LogsActivity;

    protected $guarded = [];

    protected $casts = ['is_active' => 'bool', 'show_logo' => 'bool'];
}
