<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('min_cals');
            $table->integer('max_cals');
            $table->integer('meal_plan');
            $table->integer('meals_per_day');
            $table->integer('snacks_per_day');
            $table->string('username')->unique();
            $table->string('remember_token')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password');
            $table->string('old_meal_plans')->nullable();
            $table->string('morning_faves')->nullable();
            $table->string('evening_faves')->nullable();
            $table->string('morning_staples')->nullable();
            $table->string('evening_staples')->nullable();
            $table->string('theme_color')->nullable();
            $table->longText('intolerances')->nullable();
            $table->text('meal_toggles')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
