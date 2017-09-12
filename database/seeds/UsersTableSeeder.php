<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Add some default user accounts for testing.
     *
     * @return void
     */
    public function run()
    {
        User::create( [
            'name'           => "Admin",
            'email'          => "admin@admin.com",
            'password'       => bcrypt( 'admin123' ),
            'remember_token' => str_random( 10 ),
            'role'           => 'admin',
        ] );

        User::create( [
            'name'           => "Ryan Bombard",
            'email'          => "rsbombard@gmail.com",
            'password'       => bcrypt( 'admin123' ),
            'remember_token' => str_random( 10 ),
            'role'           => 'admin',
        ] );

    }
}
