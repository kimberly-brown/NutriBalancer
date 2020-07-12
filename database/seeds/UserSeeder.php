<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $meal_toggles="";
        for ($i=0; $i < 21; $i++) {
          $meal_toggles.="on,";
        }
        DB::table('users')->insert([
          'name' => 'Wendy',
          'username' => 'wendy123',
          'email'=> Str::random(10).'@gmail.com',
          'password'=> Hash::make('123456'),
          'meals_per_day' => 3,
          'snacks_per_day' => 1,
          'min_cals'=>1400,
          'max_cals'=>2000,
          'meal_toggles'=>$meal_toggles,
        ]);
    }
}
