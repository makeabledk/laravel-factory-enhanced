<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    public function employees()
    {
        return $this->belongsToMany(User::class, 'employees');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
