<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use App\Meal;
use App\MealPlan;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * TODO: Add function comments + choose camelcase or underscores 4 variables.
 */
class PlanController extends Controller
{
  /**
   * TODO: incorporate food allergies + intolerances
   *
   * @param n: the number of recipes desired
   * @param q: the number of queries desired. MAX 5
   * @param minCals
   * @param maxCals
   * @param favorites: a list of foods to query recipes on
   * @param intolerances: a list of foods to avoid in recipes
   *
   * @return: an array of meal recipes
   */
  public function getNewMeals($n, $q, $minCals, $maxCals, $favorites, $id, $type) {
    $defaults = ["Bean", "Potato", "Apple", "Tofu", "Sandwich"];
    shuffle($defaults);
    $index = 0;
    $faves = array_slice($favorites, 0, count($favorites)-1);
    while (count($faves) < $q) {
      array_push($faves, $defaults[$index]);
      $index++;
    }
    shuffle($faves);
    $recipes = [];
    for ($i=0; $i<$q; $i++) {
      $hits = $this->getHits($faves[$i], $minCals, $maxCals, []);
      $recipes = array_merge($recipes, $hits);
    }
    $user = User::find($id);
    $staples = [];
    if ($type == "morning") {
      $staples = explode(";;", $user->morning_staples);
    } else {
      $staples = explode(";;", $user->evening_staples);
    }
    $i=0;
    while (count($recipes) < $n) {
      if ($i<count($staples)-1) {
        $staple_recipe = UserController::decodeMeal($staples[$i]);
        // ORDER: name, ingredients, url, state
        array_push($recipes, [
          "recipe"=> [
            "label"=>$staple_recipe[0],
            "ingredientLines" => $staple_recipe[1],
            "url" => $staple_recipe[2],
            "image" => "",
            "totalNutrients" => [],
            "totalDaily" => [],
            "yield" => 1, // TO CHANGE
          ],
        ]);
        $i++;
      } else {
        array_push($recipes, [
          "recipe"=> [
            "label"=>"Kale and Root Soup",
            "url" => "https://www.livekindly.co/kale-and-root-soup/",
            "ingredientLines" => [],
            "image" => "https://cdn.livekindly.co/wp-content/uploads/2017/04/soup-e1499103312137.png",
            "totalNutrients" => "",
          ],
        ]);
      }
    }
    shuffle($recipes);
    return array_slice($recipes, 0, $n);
  }

  public function getHits($query, $minCals, $maxCals, $intolerances) {
    $edamam_id = env("EDAMAM_ID", 0);
    $app_key = env("EDAMAM_KEY", 0);
    $req = Http::get('https://api.edamam.com/search?q='.$query.
      '&app_id='.$edamam_id.'&app_key='.$app_key.'&from=0&to=10'.
      '&health=vegan&calories='.$minCals.'-'.$maxCals);
    if ($req->clientError() or $req->serverError()) {
      return [];
    }
    $body = $req->getBody();
    $body->rewind();
    $cont = (string) $body->getContents();
    try {
      //dd(json_decode($cont, true)["hits"]);
      return json_decode($cont, true)["hits"];
    } catch (Exception $e) {
      return [];
    }
  }

  public function saveAndGenerate($id) {
    $user = User::find($id);
    $curr = $user->meal_plan;
    $user->old_meal_plans.=",".$curr.",";
    $user->save();
    $this->generateNewPlan($id);
    return redirect('dashboard');
  }

  public function generateNewPlan($id) {
    //dd(Meal::all());
    $user = User::find($id);
    $minMealCals = (int) ($user->min_cals / $user->meals_per_day - 200);
    $maxMealCals = (int) ($user->max_cals / $user->meals_per_day + 500);
    $breakfast_faves = explode(',', $user->morning_favorites);
    $evening_faves = explode(',', $user->evening_favorites);
    $breakfasts = $this->getNewMeals(7, 2, $minMealCals, $maxMealCals, $breakfast_faves, $id, "morning");
    $otherMeals = $this->getNewMeals(($user->meals_per_day-1) * 7, 3, $minMealCals,
      $maxMealCals, $evening_faves, $id, 'evening');
    /*
    $this->clearMealPlan($id)
    */
    $meals = [];
    for ($d=0; $d<7; $d++) {
      array_push($meals, $this->addMeal($breakfasts[$d]["recipe"]).",,");
    }

    for ($m=0; $m<($user->meals_per_day-1)*7;$m++) {
      $meal_id = $this->addMeal($otherMeals[$m]["recipe"]);
      $meals[$m % 7].= $meal_id.",,";
    }
    $plan = MealPlan::create([
      'plan' => implode(";;", $meals),
      'images' => "",
    ]);
    $user->meal_plan = $plan->id;
    $user->save();
    return redirect('dashboard');
  }

  public function addMeal($meal) {
    // check to see if the recipe name is already in the database
    $already_exists = DB::table('meals')->where('name', $meal["label"])->first();
    if ($already_exists == NULL) {
      $yield_int = 1;
      if (array_key_exists("yield", $meal)) {
        $yield_int = (int) $meal["yield"];
      }
      $nutrients = "";
      if (array_key_exists("totalNutrients", $meal) && array_key_exists("totalDaily", $meal)) {
        $nutrients = PlanController::encodeNutrients(
          $meal["totalNutrients"],
          $meal["totalDaily"],
          $yield_int,
        );
      }
      $ingredients = "";
      if (array_key_exists("ingredientLines", $meal)) {
        $ingredients = PlanController::encodeIngredients($meal["ingredientLines"]);
      }
      $image = "";
      $name = $meal["label"];
      $url = $meal["url"];
      if ($url == NULL) {
        $url = "";
      }
      if (array_key_exists("image", $meal)) {
        $image = $meal["image"];
      }

      //dd($ingredients);
      try {
        $mealObject = Meal::create([
          'name'=> $name,
          'url'=> $url,
          'image'=>$image,
          'ingredients'=> $ingredients,
          'nutrients'=> $nutrients,
          'yield' => $yield_int,
        ]);
        return $mealObject->id;
        $mealObject->save();
      } catch(\Illuminate\Database\QueryException $ex) {
        dd($ex->getMessage());
      }
    } else {
      return $already_exists->id;
    }
  }

  public function encodeIngredients($ingredients) {
    $processed = [];
    foreach ($ingredients as $ingredient) {
      [$qty, $name] = UserController::standardizeIngredient($ingredient);
      array_push($processed, $qty." ".$name);
    }
    return implode("@@", $processed)."@@";
  }

  public function encodeNutrients($totalNutrients, $totalDaily, $yield) {
    $nutrients = "";
    foreach ($totalNutrients as $name => $nutrient) {
      $label = $nutrient["label"];
      if (($label != "Energy") && ($label != "Water")) {
        if ((($label == "Fat") || ($label == "Saturated") || ($label == "Carbs")
        || ($label == "Fiber") || ($label == "Protein") || ($label == "Cholesterol")
        || ($label == "Sodium")) && ($yield != 0)) {
          $qty = (int) ($nutrient["quantity"] / $yield);
          $nutrients.=$nutrient["label"]."::".$qty."::".$nutrient["unit"]."::@@";
        } else {
          // retrieve percentages for vitamins
          if( isset($totalDaily[$name]) && ($yield != 0)) {
            $qty = (int) ($totalDaily[$name]["quantity"] / $yield);
            $nutrients.=$nutrient["label"]."::".$qty."::".$totalDaily[$name]["unit"]."::@@";
          }

        }
      }
    }
    //dd($nutrients);
    return $nutrients;
  }

  /**
   * TODO: take into account intolerances
   *
   * Generates a new meal
   *
   * @param id: the user's id
   * @param day: the day of the week, a string
   * @param meal: the index of the meal to replace with a new query
   */
  public function refreshMeal($id, $day, $meal) {
    //dd("DAY: ".$day.", ID: ".$id.", MEAL: ".$meal);
    $user = User::find($id);
    $avgCals = ($user->min_cals + $user->max_cals)/2;
    $snackSize = UserController::getSnackSize($avgCals,
      $user->meals_per_day, $user->snacks_per_day);
    $minMealCals = (int) ($avgCals - $snackSize * $user->snacks_per_day) / $user->meals_per_day;
    $maxMealCals = (int) $minMealCals + 500;
    $meals = [];
    // morning meal
    if ($meal == 0) {
      $favorites = explode(",", $user->morning_favorites);
      if ($favorites == NULL) {
        $favorites = [];
      }
      $meals = $this->getNewMeals(1, 1, (int) $minMealCals, (int) $maxMealCals,
        $favorites, $id, "morning");
    } else {
      $favorites = explode(",", $user->evening_favorites);
      if ($favorites == NULL) {
        $favorites = [];
      }
      $meals = $this->getNewMeals(1, 1, (int) $minMealCals, (int) $maxMealCals,
        $favorites, $id, "evening");
    }
    //dd($meals);
    $new = $this->addMeal($meals[0]["recipe"]);
    PlanController::replaceMeal($id, $day, $meal, $new);
    return redirect('dashboard');
  }

  /**
   * @param id: the user id
   * @param day: the index of the day (0-6)
   * @param meal: the index of the meal to replace (0th, 1st...)
   * @param new_meal: the id of the replacement meal
   */
  public static function replaceMeal($id, $day, $meal, $new_meal) {
    $user = User::find($id);
    $meal_plan = MealPlan::find($user->meal_plan);
    $plan_array = UserController::decodeMealPlan($meal_plan->id);
    $before = PlanController::encodeMealPlan($plan_array);
    $plan_array[$day][$meal] = $new_meal;
    // put the new meal plan back into string form + save to database

    $after = PlanController::encodeMealPlan($plan_array);
    $meal_plan->plan = PlanController::encodeMealPlan($plan_array);
    $meal_plan->save();
  }

  /**
   * @param plan_array: a 2D array of meal ids, grouped by day
   *
   * @return string representing the plan that can be stored in the database
   *
   * Ex. input: [[1, 2, 3], [4, 5, 6], [7, 8, 9]]
   *     output: "1,,2,,3,,;;4,,5,,6,,;;7,,8,,9,,;;"
   */
  public static function encodeMealPlan($plan_array) {
    $new_plan = [];
    for ($d=0; $d<7; $d++) {
      $day_string = implode(",,", $plan_array[$d]);
      array_push($new_plan, $day_string);
    }
    return implode(";;", $new_plan);
  }

  public function unsuppressMeal($id, $day, $meal) {
    $user = User::find($id);
    $toggles = explode(",", $user->meal_toggles);
    $index = $user->meals_per_day * $day + $meal;
    $toggles[$index] = "on";
    $user->meal_toggles = implode(",", $toggles);
    $user->save();
    return redirect('dashboard');
  }

  public function suppressMeal($id, $day, $meal) {
    $user = User::find($id);
    $toggles = explode(",", $user->meal_toggles);
    $index = $user->meals_per_day * $day + $meal;
    $toggles[$index] = "off";
    $user->meal_toggles = implode(",", $toggles);
    $user->save();
    return redirect('dashboard');
  }

  public function modifyFaves($id, $type, $viewIngredients) {
    $user = User::find($id);
    $staples = [];
    $urls = [];
    $recipes = "";
    $favorites = "";
    if ($type == "morning") {
      $recipes = explode(",", $user->morning_staples);
      $favorites = explode(",", $user->morning_faves);
    } else {
      $recipes = explode(",", $user->evening_staples);
      $favorites = explode(",", $user->evening_faves);
    }
    $count = 0;
    for ($i=0; $i<count($recipes); $i++) {
      $count++;
      if ($recipes[$i] != '') {
        $recipe = Meal::find((int) $recipes[$i]);
        $recipeArray = [$recipe->name];
        $ingredients = explode("@@", $recipe->ingredients);
        for ($j=0; $j < count($ingredients)-1; $j++) {
          if ($ingredients[$j] != "") {
            [$qty, $item] = UserController::separateQtyFromIngredient($ingredients[$j]);
            if ($qty == -1) {
              $ingredients[$j] = $item;
            }
          }
        }
        array_push($recipeArray, $ingredients);
        array_push($staples, $recipeArray);
        array_push($urls, $recipe->url);
      }
    }
    array_push($staples, ["", ""]);
    array_push($urls, "");
    $theme_color = "97d67e";
    if ($user->theme_color != NULL) {
      $theme_color = $user->theme_color;
    }
    return view ('modifyFaves', [
      'id' => $id,
      'favorites' => $favorites,
      'staple_recipes' => $staples,
      'type' => $type,
      'viewIngredients'=>$viewIngredients,
      'urls'=>$urls,
      'theme_color'=>$theme_color,
    ]);
  }

  public function addFavoriteFood(Request $request) {
    $newFood = (string) $request->newFood;
    $user = User::find($request->id);
    $oldFavorites = "";
    if ($request->type == "morning") {
      if ($user->morning_faves != NULL) {
        $oldFavorites = (string) $user->morning_faves;
      }
    } else {
      if ($user->evening_faves != NULL) {
        $oldFavorites = (string) $user->evening_faves;
      }
    }
    $oldFavoriteArray = explode(",", $oldFavorites);
    if (! in_array($newFood, $oldFavoriteArray)) {
      $newFavorites = $oldFavorites.$newFood.",";
      if ($request->type == "morning") {
        $user->morning_faves = $newFavorites;
      } else {
        $user->evening_faves = $newFavorites;
      }
      $user->save();
    }
    return redirect(route('modifyFaves', [
      'id'=>$request->id,
      'type'=>$request->type,
      'viewIngredients'=>-1,
    ]));
  }

  public function deleteFavoriteFood(Request $request) {
    $user = User::find($request->id);
    $oldFavorites = [];
    if ($request->type == "morning") {
      $oldFavorites = explode(",", $user->morning_faves);
    } else {
      $oldFavorites = explode(",", $user->evening_faves);
    }
    $newFavorites = [];
    for ($i=0; $i<count($oldFavorites)-1; $i++) {
      if ($i != $request->index) {
        array_push($newFavorites, $oldFavorites[$i]);
      }
    }

    if ($request->type == "morning") {
      $user->morning_faves = implode(",", $newFavorites).",";
    } else {
      $user->evening_faves = implode(",", $newFavorites).",";
    }
    $user->save();
    return redirect(route('modifyFaves', [
      'id'=>$request->id,
      'type'=>$request->type,
      'viewIngredients'=>-1,
    ]));
  }

  public function addStapleRecipe(Request $request) {
    $user = User::find($request->id);
    $ingredientList = explode(",", $request->ingredients);
    $meal_id = $this->addMeal([
      "label"=>$request->name,
      "url"=>$request->url,
      "yield"=>1,
      "image"=>"",
      "ingredientLines"=>explode(",", $request->ingredients),
    ]);
    if ($request->type == "morning") {
      $user->morning_staples.=$meal_id.",";
    } else {
      $user->evening_staples.=$meal_id.",";
    }
    $user->save();
    return redirect(route('modifyFaves', [
      'id'=>$request->id,
      'type'=>$request->type,
      'viewIngredients'=>$request->viewIngredients,
    ]));
  }

  public function deleteStapleRecipe(Request $request) {
    $user = User::find($request->id);
    $oldFavorites = [];
    if ($request->type == "morning") {
      $oldFavorites = explode(",", $user->morning_staples);
    } else {
      $oldFavorites = explode(",", $user->evening_staples);
    }
    $newFavorites = [];
    for ($i=0; $i<count($oldFavorites)-1; $i++) {
      if (($i != $request->index) && ($oldFavorites[$i] != '')) {
        array_push($newFavorites, $oldFavorites[$i]);
      }
    }

    if ($request->type == "morning") {
      $user->morning_staples = implode(",", $newFavorites).",";
    } else {
      $user->evening_staples = implode(",", $newFavorites).",";
    }
    $user->save();
    return redirect(route('modifyFaves', [
      'id'=>$request->id,
      'type'=>$request->type,
      'viewIngredients'=>-1,
    ]));
  }

  public function viewIngredients($id, $type, $index) {
    return redirect(route('modifyFaves', [
      'id'=>$id,
      'type'=>$type,
      'viewIngredients'=>$index,
    ]));
  }

  public function clearStapleRecipes($id, $type) {
    $user = User::find($id);
    if ($type == "morning") {
      $user->morning_staples = "";
    } else {
      $user->evening_staples = "";
    }
    $user->save();
    return redirect(route('modifyFaves', [
      'id'=>$id,
      'type'=>$type,
      'viewIngredients'=>-1,
    ]));
  }

  public function clearFavoriteFoods($id, $type) {
    $user = User::find($id);
    if ($type == "morning") {
      $user->morning_favorites = "";
    } else {
      $user->evening_favorites = "";
    }
    $user->save();
    return redirect(route('modifyFaves', [
      'id'=>$id,
      'type'=>$type,
      'viewIngredients'=>-1,
    ]));
  }
}
