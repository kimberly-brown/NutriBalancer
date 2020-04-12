<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PlanController;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UserController extends Controller
{

  /**
   * Show the sign up page.
   *
   * @return View
   */
  public function signUp()
  {
      return view('signup');
  }

  /**
   * Process preliminary user data (name, username, email, password)
   *
   * @return View
   */
  public function processSignup1(Request $request)
  {
    $password = $request->input('password');
    $confirmPassword = $request->input('confirmPassword');
    $request->validate([
      'name' => 'required',
      'username' => 'required|unique:users',
      'email' => 'required|unique:users|max:255',
      'password' => 'required|max:255',
    ]);

    if ($confirmPassword == $password) {
      return view('signup2',
      ['name'=>$request->input('name'),
       'username'=>$request->input('username'),
       'email'=>$request->input('email'),
       'password'=>Hash::make($request->input('password'))]);
    } else {
    }
    return redirect('processSignup2');
  }

  /**
   * Process preliminary user data (name, username, email, password)
   *
   * @return View
   */
  public function processSignup2(Request $request)
  {
    $credentials = $request->only('username', 'password');
    //if (Auth::attempt($credentials, true))
    //{
      $calorie_range = $request->input('calorie_range');
      $id = Auth::id();
      User::create([
        'name' => $request->input('name'),
        'username'=> $request->input('username'),
        'email' => $request->input('email'),
        'password' => $request->input('password'),
        'api_token' => Str::random(60),
        'min_cals' => (int) substr($calorie_range, 0, 5),
        'max_cals' => (int) substr($calorie_range, 6, 9),
        'meals_per_day' => $request->input('meals_per_day'),
        'snacks_per_day' => $request->input('snacks_per_day'),
        'theme_color' => '97d67e',
      ]);
      return redirect('login');
    //}
    //  return redirect('login');
  }

  /**
   * Log in
   *
   * @return View
   */
  public function login()
  {
    return view('login');
  }

  public function loginValidate(Request $request)
  {
    $username=$request->username;
    $password=$request->password;
    if (Auth::attempt(['username'=>$username,'password'=>$password], true))
    {

      return redirect('dashboard');
    }
      return redirect('login');
  }

  public function dashboard(Request $request) {
    //if (Auth::viaRemember()) {
      $id = Auth::id();
      $user = User::find($id);

      $sunday = [];
      $sundayMeals = explode(";;", $user->sunday);
      for ($x = 0; $x < count($sundayMeals); $x++) {
        if ($sundayMeals[$x] != '') {
          array_push($sunday, $this->decodeRecipe($sundayMeals[$x]));
        }
      }

      $monday = [];
      $mondayMeals = explode(";;", $user->monday);
      for ($x = 0; $x < count($mondayMeals); $x++) {
        if ($mondayMeals[$x] != '') {
          array_push($monday, $this->decodeRecipe($mondayMeals[$x]));
        }
      }

      $tuesday = [];
      $tuesdayMeals = explode(";;", $user->tuesday);
      for ($x = 0; $x < count($tuesdayMeals); $x++) {
        if ($tuesdayMeals[$x] != '') {
          array_push($tuesday, $this->decodeRecipe($tuesdayMeals[$x]));
        }
      }

      $wednesday = [];
      $wednesdayMeals = explode(";;", $user->wednesday);
      for ($x = 0; $x < count($wednesdayMeals); $x++) {
        if ($wednesdayMeals[$x] != '') {
          array_push($wednesday, $this->decodeRecipe($wednesdayMeals[$x]));
        }
      }

      $thursday = [];
      $thursdayMeals = explode(";;", $user->thursday);
      for ($x = 0; $x < count($thursdayMeals); $x++) {
        if ($thursdayMeals[$x] != '') {
          array_push($thursday, $this->decodeRecipe($thursdayMeals[$x]));
        }
      }

      $friday = [];
      $fridayMeals = explode(";;", $user->friday);
      for ($x = 0; $x < count($fridayMeals); $x++) {
        if ($fridayMeals[$x] != '') {
          array_push($friday, $this->decodeRecipe($fridayMeals[$x]));
        }
      }

      $saturday = [];
      $saturdayMeals = explode(";;", $user->saturday);
      for ($x = 0; $x < count($saturdayMeals); $x++) {
        if ($saturdayMeals[$x] != '') {
          array_push($saturday, $this->decodeRecipe($saturdayMeals[$x]));
        }
      }

      $shopping_list = $this->getShoppingList($id);

      // handle messages
      $message = "";
      $messageColor = "green";
      if ($user->dashboard_message != NULL) {
        $message = explode(";;", $user->dashboard_message)[1];
        $messageColor = explode(";;", $user->dashboard_message)[0];
      }
      $theme_color = "97d67e";
      if ($user->theme_color != NULL) {
        $theme_color = $user->theme_color;
      }
      return view('dashboard',
      ['name'=>$user->name,
      'id'=>$user->id,
      'monday' => $monday,
      'tuesday' => $tuesday,
      'wednesday' => $wednesday,
      'thursday' => $thursday,
      'friday' => $friday,
      'saturday' => $saturday,
      'sunday' => $sunday,
      'morning_faves' => explode(",", $user->morning_favorites),
      'morning_staples' => $this->staplesToArray($user->morning_staples),
      'evening_staples' => $this->staplesToArray($user->evening_staples),
      'evening_faves' => explode(",", $user->evening_favorites),
      'message' => $message,
      'message_color' => $messageColor,
      'theme_color' => $theme_color,
      'shopping_list'=>$shopping_list,
      ]);
    //}
  }

  public function staplesToArray($staplesString) {
    if ($staplesString == NULL) {
      return [];
    }
    $staples = [];
    $recipes = explode(";;", $staplesString);
    for ($i=0; $i<count($recipes)-1; $i++) {
      if ($recipes[$i] != '') {
        array_push($staples, $this->decodeRecipe($recipes[$i]));
      }
    }
    return $staples;
  }

  public function decodeRecipe($recipeString) {
    if ($recipeString == NULL) {
      return [];
    }
    $recipeArray = [];
    $recipe = explode("##", $recipeString);
    $name = $recipe[0];
    $ingredients = [];
    if (count($recipe) >= 2) {
      $ingredients = explode("@@", $recipe[1]);
    }
    $url = "";
    $state = "on";
    if (count($recipe) >= 3) {
      $url = $recipe[2];
    }
    if (count($recipe) >= 4) {
      $state = $recipe[3];
    }
    array_push($recipeArray, $name, $ingredients, $url, $state);
    return $recipeArray;
  }

  public function decodeDay($dayString) {
    if ($dayString == NULL) {
      return [];
    }
    $meals = [];
    $mealsEncoded = explode(";;", substr($dayString, 0, strlen($dayString)-1));
    foreach ($mealsEncoded as $meal) {
      array_push($meals, $this->decodeRecipe($meal));
    }
    return $meals;
  }

  public function decodeMealPlan($id) {
    $user = User::find($id);
    $mealPlanArray = [];
    array_push($mealPlanArray,
      $this->decodeDay($user->sunday),
      $this->decodeDay($user->monday),
      $this->decodeDay($user->tuesday),
      $this->decodeDay($user->wednesday),
      $this->decodeDay($user->thursday),
      $this->decodeDay($user->friday),
      $this->decodeDay($user->saturday),
    );
    return $mealPlanArray;
  }

  public static function getSnackSize($avgCalories, $meals_per_day, $snacks_per_day) {
    if ($snacks_per_day == 0) {
      return 0;
    } elseif ($meals_per_day == 1) {
      return .3 * $avgCalories;
    } elseif ($meals_per_day == 2) {
      return .2 * $avgCalories;
    } elseif ($meals_per_day == 3) {
      return .1 * $avgCalories;
    } else {
      return .05 * $avgCalories;
    }
  }

  public function settings($id) {
    $user = User::find($id);
    $avgCalories = ($user->max_cals + $user->min_cals) / 2;
    $snackSize = UserController::getSnackSize($avgCalories, $user->meals_per_day, $user->snacks_per_day);
    // get meal size
    $mealSize = ($avgCalories - $snackSize * $user->snacks_per_day) / $user->meals_per_day;
    $color = $user->theme_color;
    if ($color == NULL) {
      $color = "97d67e";
    }
    return view("settings", ['id' => $id, 'theme_color' => $color, 'min_cals' => $user->min_cals,
      'max_cals'=>$user->max_cals, 'meals_per_day'=>$user->meals_per_day, 'snacks_per_day'=>$user->snacks_per_day,
      'cals_per_snack' => (int) $snackSize, 'cals_per_meal' => (int) $mealSize]);
  }

  public function changeThemeColor($id, $color) {
    $user = User::find($id);
    $user->theme_color = $color;
    $user->save();
    return redirect(route('settings', ['id' => $id, 'theme_color'=>$color]));
  }

  public function getShoppingList($id) {
    $user = User::find($id);
    $mealPlan = $this->decodeMealPlan($id);
    $shopping_list = [];
    $nonHits = [];
    $allIngredients = [];
    $wholeNumberHits = [];
    $rangeMatches = [];
    // matches "1-2 cups", "1 or 2 cups", etc.
    $rangePattern = '/^(\d+)(\s)*(-|or)(\s)*(\d+)([\pL\d \.\-\,\:\/()~&"]+)/';
    $fractionPattern='/^(\d+)\/(\d+)\s*([\pL\d \.\-\,\:\/()~"&]+)/';
    $decimalPattern = '/^(\d+)\.(\d+)\s*([\pL\d \.\-\,\:\/()~"&]+)/';
    $wholeNumberPattern = '/^(\d+)\s*([\pL\d \.\-\,\:\/()~"&]+)/';
    $mixedNumberPattern = '/^(\d+)\s(\d+)\/(\d+)\s*([\pL\d \.\-\,\:\/()~"&]+)/';
    $startsWithAPattern = '/^[a](\s+)([\pL\d \.\-\,\:\/()~"&]+)/';
    $saltPattern = '/salt$/';
    $pepperPattern = '/black pepper$/';
    $waterPattern = '/water$/';
    $saltAndPepperPattern = '/salt\s*([\pL\d \.\-\,\:\/()&]+)pepper/';
    foreach ($mealPlan as $day) {
      foreach ($day as $meal) {
        if ((count($meal) >= 2) && ($meal[2] == "on")) {
          for ($i=0; $i<count($meal[1]); $i++) {
            $ingredientBefore = strtolower($meal[1][$i]);
            $ingredientAfter = explode("+", $ingredientBefore);
            //dd($ingredientAfter);
            foreach ($ingredientAfter as $ingredient) {
              array_push($allIngredients, $ingredient);
              if ($ingredient == '') {
                break;
              }
              $exploded = explode(" ", $ingredient);
              if ($exploded[0] == "juice") {
                $ingredient = str_replace("juice ", "", $ingredient);
              }
              $match = [];
              $qty = 0;
              $item = "";
                // ex. ["1-2 tsp", "1", "2", "tsp"]
              if (preg_match($rangePattern, $ingredient, $match)) {
                //array_push($rangeMatches, $ingredient);
                $qty = (int) $match[count($match)-2];
                [$item, $newQty] = $this->standardizeIngredient($match[count($match)-1], $qty);

                //ex. "1/2 cup" : ["1/2 cup", "1", "2", "cup"]
              } elseif (preg_match($fractionPattern, $ingredient, $match)) {
                $qty = ((int) $match[1]) / ((int) $match[2]);
                [$item, $newQty] = $this->standardizeIngredient($match[3], $qty);

                // ex "100.0g" : ["100.0g", "100", "0", "g"]
              } elseif (preg_match($decimalPattern, $ingredient, $match)) {
                //array_push($rangeMatches, $ingredient);
                $denominator = 10^strlen($match[2]);
                $qty = ((int) $match[1]) + (((int) $match[2]) / $denominator);
                [$item, $newQty] = $this->standardizeIngredient($match[3], $qty);
                // ex "1 cup" : ["1", "cup"]
              } elseif (preg_match($mixedNumberPattern, $ingredient, $match)) {
                //dd($ingredient);
                $qty = ((int) $match[1]) + (((int) $match[2]) / ((int) $match[3]));
                [$item, $newQty] = $this->standardizeIngredient($match[4], $qty);
              } elseif (preg_match($wholeNumberPattern, $ingredient, $match)) {
                //dd($ingredient);
                $qty = (int) $match[1];
                [$item, $newQty] = $this->standardizeIngredient($match[2], $qty);
                array_push($wholeNumberHits, $match[2]);
                // ex ["1 1/3 teaspoon cinnamon", "1", "1", "3", "teaspoon cinnamon"]
              } elseif (preg_match($startsWithAPattern, $ingredient, $match)) {
                $qty = 1;
                [$item, $newQty] = $this->standardizeIngredient($match[2], $qty);
              } else {
                $qty = -1;
                [$item, $newQty] = $this->standardizeIngredient($ingredient, $qty);

                //array_push($nonHits, $ingredient);
              }
              if (!(preg_match($saltPattern, $item, $match) ||
                    preg_match($pepperPattern, $item, $match) ||
                    preg_match($saltAndPepperPattern, $item, $match) ||
                    preg_match($waterPattern, $item, $match))) {
                if (array_key_exists($item, $shopping_list)) {
                  $shopping_list[$item]+=$newQty;
                } else {
                  $shopping_list[$item] = $newQty;
                }
              }
            }
          }
        }
      }
    }
    //dd($wholeNumberHits);
    //dd($allIngredients);
    //dd($nonHits);
    //dd($rangeMatches);
    //dd($shopping_list);
    ksort($shopping_list);
    $result = [];
    foreach ($shopping_list as $item => $qty) {
      if ($qty < 0) {
        array_push($result, $item);
      } else {
        array_push($result, round($qty, 2)." ".$item);
      }
    }
    return $result;
  }

  public function standardizeIngredient(String $ingredient, $qty) {
    $cleaned = trim(str_replace("of ", "", $ingredient));
    // remove things like (~1/3 cup)
    $aboutPattern = '/\(?(~|about)([\pL\d \.\-\,\:\/()"]+)\)?/';
    $cleaned = preg_replace($aboutPattern, "", $cleaned);
    $toTastePattern = '/(\s+)(\(|or|(to))([\pL\d \.\-\,\:\/()~"]+)taste\)?/';
    $cleaned = preg_replace($toTastePattern, "", $cleaned);
    //removed things like "(diced and seeded)"
    $verbedAndVerbedPattern = '/(\s)*(\(|,)?([\pL\d\.\-\,\:\/()~"]+) and ([\pL\d \.\-\,\:\/()~"]+)ed\)?/';
    $freshOrFrozenPattern = '/(\s)*(\(|,)?(fresh|frozen) or (fresh|frozen)\)?/';
    $storeOrHomePattern = '/(\s)*(\(|,)?(store|home)([\w -])*or([\w -])*(bought|made)\)?/';

    $cleaned = preg_replace($verbedAndVerbedPattern, "", $cleaned);
    $cleaned = preg_replace($freshOrFrozenPattern, "", $cleaned);
    $cleaned = preg_replace($storeOrHomePattern, "", $cleaned);
    $cleaned = str_replace("small ", "", $cleaned);
    $replaced = str_replace("several dashes", "tbsp", $cleaned);
    $replaced = str_replace("garnish with", "tbsp", $cleaned);
    $replaced = str_replace("dash", "tbsp", $replaced);
    if ($replaced != $cleaned) {
      $qty = 0.5;
      $cleaned = $replaced;
    }
    $cleaned = str_replace(".", "", $cleaned);
    $cleaned = str_replace("to make your own gather the following:", "", $cleaned);
    $cleaned = str_replace("extra virgin ", "", $cleaned);
    $cleaned = str_replace("extra-virgin ", "", $cleaned);
    $cleaned = str_replace("diced", "", $cleaned);
    $cleaned = str_replace(" to serve", "", $cleaned);
    $cleaned = str_replace(" rinsed several times", "", $cleaned);
    $cleaned = str_replace("minced", "", $cleaned);
    $cleaned = str_replace("cooled", "", $cleaned);
    $cleaned = str_replace("cooked", "", $cleaned);
    $cleaned = str_replace("generous ", "", $cleaned);
    $cleaned = str_replace("medium ", "", $cleaned);
    $cleaned = str_replace("large ", "", $cleaned);
    $cleaned = str_replace("peeled", "", $cleaned);
    $cleaned = str_replace("thinly", "", $cleaned);
    $cleaned = str_replace("cored", "", $cleaned);
    $cleaned = str_replace("stems trimmed", "", $cleaned);
    $cleaned = str_replace("trimmed", "", $cleaned);
    $cleaned = str_replace("cut", "", $cleaned);
    $cleaned = str_replace("torn ", "", $cleaned);
    $cleaned = str_replace("soaked", "", $cleaned);
    $cleaned = str_replace("drained", "", $cleaned);
    $cleaned = str_replace("sliced", "", $cleaned);
    $cleaned = str_replace("grated", "", $cleaned);
    $cleaned = str_replace(", washed and ", "", $cleaned);
    $cleaned = str_replace("finely chopped", "", $cleaned);
    $cleaned = str_replace("one-pound block", "lbs", $cleaned);
    $cleaned = str_replace("plus extra", "", $cleaned);
    $cleaned = str_replace("as needed", "", $cleaned);
    $cleaned = str_replace(",", "", $cleaned);
    $cleaned = str_replace("in water", "", $cleaned);
    $cleaned = str_replace("overnight", "", $cleaned);
    $cleaned = str_replace("garlic cloves", "cloves garlic", $cleaned);
    $cleaned = str_replace("garlic clove", "cloves garlic", $cleaned);
    $cleaned = str_replace(" for frying", "", $cleaned);
    $cleaned = str_replace("garlic chopped", "garlic", $cleaned);
    $cleaned = str_replace("(see above)", "", $cleaned);
    if (($cleaned == "oil") || ($cleaned == "vegetable oil") || ($cleaned == "olive oil")) {
      $cleaned = "tbsp ".$cleaned;
      $qty = 2;
    }
    $cleaned = trim($cleaned);
    $food = explode(" ", $cleaned);
    $unit = trim($food[0]);
    if ($unit == "half") {
      $qty = 0.5;
      $food[0] = "";
      $unit = trim($food[1]);
    } else if ($unit == "one") {
      $qty = 1;
      $food[0] = "";
      $unit = trim($food[1]);
    } else if ($unit == "two") {
      $qty = 1;
      $food[0] = "";
      $unit = trim($food[1]);
    }
    $newQty = $qty;
    if (($unit == "tablespoon") || ($unit == "tablespoons") || ($unit == "tbsp.") || ($unit == "tbs.")) {
      $food[0] = "tbsp";
      if ($qty == -1) {
        $qty = 1;
      }
    } elseif (($unit == "cup") || ($unit == "c.")) {
      $food[0] = "cups";
      if ($qty == -1) {
        $qty = 1;
      }
    } elseif (($unit == "teaspoon") || ($unit == "tsp.") || ($unit == "tsp") || ($unit == "teaspoons")) {
      $food[0] = "tbsp";
      if ($qty == -1) {
        $qty = 1;
      }
      $newQty = $qty / 3;
    } elseif ($unit == "clove") {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "cloves";
    } elseif ($unit == "handful") {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "handfuls";
    } else if (($unit == "pounds") || ($unit == "lbs.") || ($unit == "pound")) {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "lbs";
    } else if (($unit == "grams") || ($unit == "grams")) {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "g";
    } else if (($unit == "ounces") || ($unit == "oz.")) {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "oz";
    }
    $patched = implode(" ", $food);
    return [$patched, $newQty];
  }
}
