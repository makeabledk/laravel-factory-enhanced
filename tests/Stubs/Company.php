<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function divisions()
    {
        return $this->hasMany(Division::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}