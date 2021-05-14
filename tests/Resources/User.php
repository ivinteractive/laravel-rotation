<?php

namespace IvInteractive\Rotation\Tests\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
	use HasFactory;

	protected $fillable = [
        'email',
        'password',
        'dob',
	];

    protected static function newFactory()
    {
        return \IvInteractive\Rotation\Tests\Database\Factories\UserFactory::new();
    }

    // public function getDobAttribute($value)
    // {
    //     return decrypt($value);
    // }

    // public function setDobAttribute($value)
    // {
    //     $this->attributes['dob'] = encrypt($value);
    // }
}
