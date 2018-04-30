<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class);
    }
}