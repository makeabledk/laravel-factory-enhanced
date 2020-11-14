<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    public function imageable()
    {
        return $this->morphTo();
    }
}
