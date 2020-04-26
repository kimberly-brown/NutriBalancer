<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('signup', 'UserController@signUp');

Route::get('login', 'UserController@login');

Route::get('dashboard',
  array('as'=>'dashboard', 'uses'=>'UserController@dashboard'));

Route::get('settings/{id}/change_theme_color/{color}',
  array('as'=>'changeThemeColor', 'uses'=>'UserController@changeThemeColor'));

Route::get('settings/{id}', array('as' => 'settings', 'uses' => 'UserController@settings'));

Route::get('dashboard/{id}/generate', array('as' => 'generateNewPlan', 'uses' => 'PlanController@generateNewPlan'));
Route::get('dashboard/{id}/save_and_generate', array('as' => 'saveAndGenerate', 'uses' => 'PlanController@saveAndGenerate'));

Route::get('dashboard/{id}/modify_{type}_favorites/{viewIngredients}',
  array('as' => 'modifyFaves', 'uses' => 'PlanController@modifyFaves'));
Route::post('dashboard/{id}/add_staple', array('as' => 'addStaple', 'uses' => 'PlanController@addStaple'));
Route::get('dashboard/{id}/refresh_meal/{day}/{meal}', array('as'=>'refreshMeal', 'uses'=>'PlanController@refreshMeal'));
Route::get('dashboard/{id}/suppress_meal/{day}/{meal}', array('as'=>'suppressMeal', 'uses'=>'PlanController@suppressMeal'));
Route::get('dashboard/{id}/unsuppress_meal/{day}/{meal}', array('as'=>'unsuppressMeal', 'uses'=>'PlanController@unsuppressMeal'));
Route::get('{id}/{type}/view_ingredients/{viewIngredients}', array('as'=>'viewIngredients', 'uses'=>'PlanController@viewIngredients'));

Route::post('login_validate', array('uses' => 'UserController@loginValidate'));

Route::post('process_signup_1', array('uses' => 'UserController@processSignup1'));

Route::post('process_signup_2', array('uses' => 'UserController@processSignup2'));

// ADDING/ DELETING STAPLES
Route::post('add_favorite_food', array('as' => 'addFavoriteFood', 'uses' => 'PlanController@addFavoriteFood'));
Route::get('delete_favorite_food', array('as'=>'deleteFavoriteFood', 'uses'=>'PlanController@deleteFavoriteFood'));
Route::post('add_recipe', array('as' => 'addStapleRecipe', 'uses' => 'PlanController@addStapleRecipe'));
Route::get('delete_recipe', array('as' => 'deleteStapleRecipe', 'uses' => 'PlanController@deleteStapleRecipe'));
Route::get('{id}/clear_staple_{type}_recipes', array('as'=>'clearStapleRecipes', 'uses'=>'PlanController@clearStapleRecipes'));
Route::get('{id}/clear_favorite_{type}_foods', array('as'=>'clearFavoriteFoods', 'uses'=>'PlanController@clearFavoriteFoods'));
