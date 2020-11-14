<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $casts = [
        'tags' => 'array',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function logo()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
