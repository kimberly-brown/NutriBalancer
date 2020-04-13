<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
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
   * @return: an array of recipes
   */
  public function getRecipes($n, $q, $minCals, $maxCals, $favorites, $intolerances) {
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
    while (count($recipes) < $n) {
      // add staple recipes, then defaults
      array_push($recipes, [
        "label"=>"Add more staple recipes.",
        "url"=>"/dashboard",
      ]);
    }
    shuffle($recipes);
    return array_slice($recipes, 0, $n);
  }

  public function getHits($query, $minCals, $maxCals, $intolerances) {
    $req = Http::get('https://api.edamam.com/search?q='.$query.
      '&app_id=EDAMAM_ID&app_key=EDAMAM_KEY&from=0&to=10'.
      '&health=vegan&calories='.$minCals.'-'.$maxCals);
    if ($req->clientError() or $req->serverError()) {
      return [];
    }
    $body = $req->getBody();
    $body->rewind();
    $cont = (string) $body->getContents();
    try {
      return json_decode($cont, true)["hits"];
    } catch (Exception $e) {
      return [];
    }
  }

  public function generateNewPlan($id) {
    $user = User::find($id);
    $this->clearMealPlan($id);

    $minMealCals = (int) ($user->min_cals / $user->meals_per_day - 200);
    $maxMealCals = (int) ($user->max_cals / $user->meals_per_day + 500);

    $breakfast_faves = explode(',', $user->morning_favorites);
    $evening_faves = explode(',', $user->evening_favorites);

    $breakfasts = $this->getRecipes(7, 2, $minMealCals, $maxMealCals, $breakfast_faves, []);

    $otherMeals = $this->getRecipes(($user->meals_per_day-1) * 7, 3, $minMealCals,
      $maxMealCals, $evening_faves, []);
    for ($d=0; $d<7; $d++) {
      $this->addMeal($id, $d, $breakfasts[$d]["recipe"]);
    }

    for ($m=0; $m<($user->meals_per_day-1)*7;$m++) {
        $this->addMeal($id, $m%7, $otherMeals[$m]["recipe"]);
    }
    return redirect('dashboard');
  }

  public function addMeal($id, $d, $meal) {
    $user = User::find($id);
    if ($d == 0) {
      $user->sunday.=$this->encodeRecipe($meal);
    } elseif ($d == 1) {
      $user->monday.=$this->encodeRecipe($meal);
    } elseif ($d == 2) {
      $user->tuesday.=$this->encodeRecipe($meal);
    } elseif ($d == 3) {
      $user->wednesday.=$this->encodeRecipe($meal);
    } elseif ($d == 4) {
      $user->thursday.=$this->encodeRecipe($meal);
    } elseif ($d == 5) {
      $user->friday.=$this->encodeRecipe($meal);
    } else {
      $user->saturday.=$this->encodeRecipe($meal);
    }
    $user->save();
  }

  public function encodeRecipe($recipe) {
    $name = $recipe["label"];
    $url = $recipe["url"];
    $ingredients = $recipe["ingredientLines"];
    $ingredientsEncoded = implode("@@", $ingredients)."@@";
    return $name."##".$ingredientsEncoded."##on##".$url."##;;";
  }

  public function clearMealPlan($id) {
    $user = User::find($id);
    $user->sunday = "";
    $user->monday = "";
    $user->tuesday = "";
    $user->wednesday = "";
    $user->thursday = "";
    $user->friday = "";
    $user->saturday = "";
    $user->save();
  }

  /**
   * TODO: take into account intolerances
   *
   * @param id: the user's id
   * @param day: the day of the week, a string
   * @param meal: the index of the meal to replace with a new query
   */
  public function refreshMeal($id, $day, $meal) {
    $user = User::find($id);
    $avgCals = ($user->min_cals + $user->max_cals)/2;
    $snackSize = UserController::getSnackSize($avgCals,
      $user->meals_per_day, $user->snacks_per_day);
    $minMealCals = (int) ($avgCals - $snackSize * $user->snacks_per_day) / $user->meals_per_day;
    $maxMealCals = (int) $minMealCals + 500;
    $favorites = [];
    // morning meal
    if ($meal == 0) {
      $favorites = explode(",", $user->morning_favorites);
    } else {
      $favorites = explode(",", $user->evening_favorites);
    }
    if ($favorites == NULL) {
      $favorites = [];
    }
    $recipes = $this->getRecipes(1, 1, (int) $minMealCals, (int) $maxMealCals, $favorites, []);
    $new = $recipes[0]["recipe"];
    $newMeal = $this->encodeRecipe($new);
    PlanController::replaceMeal($id, $day, $meal, $newMeal);
    return redirect('dashboard');
  }

  public static function replaceMeal($id, $day, $meal, $newMeal) {
    $user = User::find($id);
    if ($day == "Sunday") {
      $meals = explode(";;", $user->sunday);
      $meals[$meal] = $newMeal;
      $user->sunday = implode(";;", $meals);
    } elseif ($day == "Monday") {
      $meals = explode(";;", $user->monday);
      $meals[$meal] = $newMeal;
      $user->monday = implode(";;", $meals);
    } elseif ($day == "Tuesday") {
      $meals = explode(";;", $user->tuesday);
      $meals[$meal] = $newMeal;
      $user->tuesday = implode(";;", $meals);
    } elseif ($day == "Wednesday") {
      $meals = explode(";;", $user->wednesday);
      $meals[$meal] = $newMeal;
      $user->wednesday = implode(";;", $meals);
    } elseif ($day == "Thursday") {
      $meals = explode(";;", $user->thursday);
      $meals[$meal] = $newMeal;
      $user->thursday = implode(";;", $meals);
    } elseif ($day == "Friday") {
      $meals = explode(";;", $user->friday);
      $meals[$meal] = $newMeal;
      $user->friday = implode(";;", $meals);
    } else {
      $meals = explode(";;", $user->saturday);
      $meals[$meal] = $newMeal;
      $user->saturday = implode(";;", $meals);
    }
    $user->save();
  }

  public function unsuppressMeal($id, $day, $meal) {
    $user = User::find($id);
    if ($day == "Sunday") {
      $meals = explode(";;", $user->sunday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->sunday = implode(";;", $meals).";;";
    } elseif ($day == "Monday") {
      $meals = explode(";;", $user->monday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->monday = implode(";;", $meals).";;";
    } elseif ($day == "Tuesday") {
      $meals = explode(";;", $user->tuesday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->tuesday = implode(";;", $meals).";;";
    } elseif ($day == "Wednesday") {
      $meals = explode(";;", $user->wednesday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->wednesday = implode(";;", $meals).";;";
    } elseif ($day == "Thursday") {
      $meals = explode(";;", $user->thursday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->thursday = implode(";;", $meals).";;";
    } elseif ($day == "Friday") {
      $meals = explode(";;", $user->friday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->friday = implode(";;", $meals).";;";
    } else {
      $meals = explode(";;", $user->saturday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "on";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->saturday = implode(";;", $meals).";;";
    }
    $user->save();
    return redirect(route('dashboard'));
  }

  public function suppressMeal($id, $day, $meal) {
    $user = User::find($id);
    if ($day == "Sunday") {
      $meals = explode(";;", $user->sunday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->sunday = implode(";;", $meals).";;";
    } elseif ($day == "Monday") {
      $meals = explode(";;", $user->monday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->monday = implode(";;", $meals).";;";
    } elseif ($day == "Tuesday") {
      $meals = explode(";;", $user->tuesday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->tuesday = implode(";;", $meals).";;";
    } elseif ($day == "Wednesday") {
      $meals = explode(";;", $user->wednesday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->wednesday = implode(";;", $meals).";;";
    } elseif ($day == "Thursday") {
      $meals = explode(";;", $user->thursday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->thursday = implode(";;", $meals).";;";
    } elseif ($day == "Friday") {
      $meals = explode(";;", $user->friday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->friday = implode(";;", $meals).";;";
    } else {
      $meals = explode(";;", $user->saturday);
      $currMeal = explode("##", $meals[$meal]);
      $currMeal[2] = "off";
      $meals[$meal] = implode("##", $currMeal)."##";
      $user->saturday = implode(";;", $meals).";;";
    }
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
      $recipes = explode(";;", $user->morning_staples);
      $favorites = explode(",", $user->morning_favorites);
    } else {
      $recipes = explode(";;", $user->evening_staples);
      $favorites = explode(",", $user->evening_favorites);
    }
    for ($i=0; $i<count($recipes)-1; $i++) {
      if ($recipes[$i] != '') {
        $recipe = explode("##", $recipes[$i]);
        $recipeArray = [$recipe[0]];
        array_push($recipeArray, explode("@@", $recipe[1]));
        array_push($staples, $recipeArray);
        array_push($urls, $recipe[2]);
      }
    }
    array_push($staples, ["", ""]);
    array_push($urls, "");
    return view ('modifyFaves', [
      'id' => $id,
      'favorites' => $favorites,
      'staple_recipes' => $staples,
      'type' => $type,
      'viewIngredients'=>$viewIngredients,
      'urls'=>$urls,
    ]);
  }

  public function addFavoriteFood(Request $request) {
    $newFood = (string) $request->newFood;
    $user = User::find($request->id);
    $oldFavorites = "";
    if ($request->type == "morning") {
      if ($user->morning_favorites != NULL) {
        $oldFavorites = (string) $user->morning_favorites;
      }
    } else {
      if ($user->evening_favorites != NULL) {
        $oldFavorites = (string) $user->evening_favorites;
      }
    }
    $oldFavoriteArray = explode(",", $oldFavorites);
    if (! in_array($newFood, $oldFavoriteArray)) {
      $newFavorites = $oldFavorites.$newFood.",";
      if ($request->type == "morning") {
        $user->morning_favorites = $newFavorites;
      } else {
        $user->evening_favorites = $newFavorites;
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
      $oldFavorites = explode(",", $user->morning_favorites);
    } else {
      $oldFavorites = explode(",", $user->evening_favorites);
    }
    $newFavorites = [];
    for ($i=0; $i<count($oldFavorites)-1; $i++) {
      if ($i != $request->index) {
        array_push($newFavorites, $oldFavorites[$i]);
      }
    }

    if ($request->type == "morning") {
      $user->morning_favorites = implode(",", $newFavorites).",";
    } else {
      $user->evening_favorites = implode(",", $newFavorites).",";
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
    $ingredientString = implode("@@", $ingredientList)."@@";
    $newRecipe = $request->name."##".$ingredientString."##";
    $url = $request->url;
    if ($url != "") {
      $newRecipe.=$url."##";
    }
    $newRecipe.=";;";
    if ($request->type == "morning") {
      $user->morning_staples.=$newRecipe;
    } else {
      $user->evening_staples.=$newRecipe;
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
      $oldFavorites = explode(";;", $user->morning_staples);
    } else {
      $oldFavorites = explode(";;", $user->evening_staples);
    }
    $newFavorites = [];
    for ($i=0; $i<count($oldFavorites)-1; $i++) {
      if (($i != $request->index) && ($oldFavorites[$i] != '')) {
        array_push($newFavorites, $oldFavorites[$i]);
      }
    }

    if ($request->type == "morning") {
      $user->morning_staples = implode(";;", $newFavorites).";;";
    } else {
      $user->evening_staples = implode(";;", $newFavorites).";;";
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
