<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
