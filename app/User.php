<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Plank\Mediable\Mediable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    protected $appends = ['is_admin'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function filePath( $folder = "" )
    {
        $path = md5( $this->id );
        if ( !empty( $folder ) ) {
            $path .= "/" . $folder;
        }

        return $path;
    }

    public function isAdmin()
    {
        return $this->getIsAdminAttribute();
    }

    public function getIsAdminAttribute()
    {
        return $this->role == "admin";
    }

}
