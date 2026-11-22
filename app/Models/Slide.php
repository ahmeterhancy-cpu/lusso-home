<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use \App\Concerns\LogsActivity;

    protected $guarded = [];

    protected $casts = ['is_active' => 'bool', 'show_logo' => 'bool'];

    /** Dikey (portre) görsel mi? Vitrinde bu görseller kırpılmadan sağda tam boy gösterilir. */
    public function isTall(): bool
    {
        if (! isset($this->tallCache)) {
            $file = $this->image ? public_path('medya/'.ltrim($this->image, '/')) : null;
            $size = $file && is_file($file) ? @getimagesize($file) : false;
            $this->tallCache = $size && $size[1] > $size[0] * 1.05;
        }

        return $this->tallCache;
    }

    private ?bool $tallCache = null;
}
