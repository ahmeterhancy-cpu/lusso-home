<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'bool', 'show_logo' => 'bool'];
}
