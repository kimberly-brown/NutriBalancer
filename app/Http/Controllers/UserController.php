<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PlanController;
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

class UserController extends Controller
{

  /**
   * Show the sign up page.
   *
   * @return View
   */
  public function signUp()
  {
      return view('signup', ['error' => '']);
  }

  /**
   * Process preliminary user data (name, username, email, password)
   *
   * @return View
   */
  public function processSignup1(Request $request)
  {
    $validatedData = $request->validate([
      'name' => 'required',
      'username' => 'required|unique:users',
      'email' => 'required|unique:users|max:255',
      'password' => 'required|max:255|min:6|confirmed',
    ]);
    $same_username = DB::table('users')->where('username', $request->username)->first();
    $same_email = DB::table('users')->where('email', $request->email)->first();
    if ($same_username != NULL) {
      return view('signup', ['error'=>'Username taken']);
    } elseif ($same_email != NULL) {
      return view('signup', ['error'=>'Email already in use']);
    } else {
      return view('signup2', [
         'name'=>$request->input('name'),
         'username'=>$request->input('username'),
         'email'=>$request->input('email'),
         'password'=>Hash::make($request->input('password'))
       ]);
    }
  }

  /**
   * Process preliminary user data (name, username, email, password)
   *
   * @return View
   */
  public function processSignup2(Request $request)
  {
    $credentials = $request->only('username', 'password');
    // dd($request->input('password'));
    //if (Auth::attempt($credentials, true))
    //{
      $calorie_range = $request->input('calorie_range');
      $id = Auth::id();
      // TODO: Check to see if username or email is already taken
      $meal_toggles = "";
      for ($i=0; $i < 7 * $request->input('meals_per_day'); $i++) {
        $meal_toggles.="on,";
      }
      $user = User::create([
        'name' => $request->input('name'),
        'username'=> $request->input('username'),
        'email' => $request->input('email'),
        'password' => $request->input('password'),
        'min_cals' => (int) substr($calorie_range, 0, 5),
        'max_cals' => (int) substr($calorie_range, 6, 9),
        'meals_per_day' => $request->input('meals_per_day'),
        'meal_plan' => -1,
        'snacks_per_day' => $request->input('snacks_per_day'),
        'theme_color' => '97d67e',
        'meal_toggles'=>$meal_toggles,
      ]);
      $user->save();
      return redirect('login');
  }

  /**
   * Log in
   *
   * @return View
   */
  public function login()
  {
    return view('login', ['error'=>'']);
  }

  public function loginValidate(Request $request)
  {
    $username=$request->username;
    $password=$request->password;
    if (Auth::attempt(['username'=>$username,'password'=>$password], true)) {
      return redirect('dashboard');
    } else {
      return view('login', ['error' => 'Username or password is incorrect.']);
    }
    /*
    if (Auth::attempt(['username'=>$username,'password'=>$password], true))
    {

      return redirect('dashboard');
    }
      return redirect('login');

  */
}

  public function dashboard(Request $request) {
    //if (Auth::viaRemember()) {
      $id = Auth::id();
      $user = User::find($id);
      $day_order = ["Monday", "Tuesday", "Wednesday", "Thurdsay,", "Friday", "Saturday", "Sunday"];
      $meal_plan = MealPlan::find($user->meal_plan);
      $days = [];
      if ($meal_plan != NULL) {
        $days = explode(";;", $meal_plan->plan);
      }
      $meal_names = [];
      $meal_urls = [];
      $images = [];
      foreach ($days as $day) {
        $meal_ids = explode(",,", $day);
        foreach ($meal_ids as $m_id) {
          $meal = Meal::find($m_id);
          if ($meal != NULL) {
            $name = $meal->name;
            $name = str_replace("Recipes", "", $name);
            $name = str_replace("recipes", "", $name);
            $name = str_replace("recipe", "", $name);
            $name = str_replace("Recipe", "", $name);
            array_push($meal_names, $name);
            array_push($meal_urls, $meal->url);
            if ($meal->image != "") {
              if (!in_array([$meal->image, $name], $images)) {
                array_push($images, [$meal->image, $name]);
              }
            }
          } else {
          }
        }
      }
      while (count($meal_urls) < $user->meals_per_day * 7) {
        array_push($meal_urls, "");
      }
      while (count($meal_names) < $user->meals_per_day * 7) {
        array_push($meal_names, "");
      }
    $message = "";
    $messageColor = "green";
    $theme_color = "97d67e";
    if ($user->theme_color != NULL) {
      $theme_color = $user->theme_color;
    }
    $shopping_list = $this->getShoppingList($id);
    $nutrient_summary = $this->getNutrientSummary($user->meal_plan);
    shuffle($images);
    $meal_statuses = explode(",", $user->meal_toggles);
    return view('dashboard', [
      'name'=>$user->name,
      'id'=>$id,
      'meal_names' => $meal_names,
      'meal_urls' => $meal_urls,
      'images' => $images,
      'morning_faves' => explode(",", $user->morning_faves),
      'morning_staples' => $this->staplesToArray($user->morning_staples),
      'evening_staples' => $this->staplesToArray($user->evening_staples),
      'evening_faves' => explode(",", $user->evening_faves),
      'meals_per_day' => $user->meals_per_day,
      'message' => $message,
      'message_color' => $messageColor,
      'theme_color' => $theme_color,
      'shopping_list'=>$shopping_list,
      'nutrient_summary'=>UserController::getAverageNutrients($nutrient_summary),
      'day_order' => $day_order,
      'meal_statuses' => $meal_statuses,
    ]);
  }

  public function getShoppingList($id) {
    $user = User::find($id);
    if ($user->meal_plan == NULL) {
      return [];
    }
    $toggles = explode(",", $user->meal_toggles);
    //dd($toggles);
    $week = UserController::decodeMealPlan($user->meal_plan);
    //$meal_plan
    $shopping_list = [];
    $meal_count = 0;
    $raw_ingredients = [];
    foreach ($week as $day) {
      foreach ($day as $meal_id) {
        // check for meal being off or on
          $meal = Meal::find($meal_id);
          if ($meal != NULL) {
            $meal_count++;
            if ($toggles[$meal_count-1] == "on") {
              $ingredients = explode("@@", $meal->ingredients);
              foreach ($ingredients as $ingredient) {
                array_push($raw_ingredients, $ingredient);
                [$qty, $name] = UserController::separateQtyFromIngredient($ingredient);
                //dd($ingredient.", ".$qty.", ".$name);
                if (strlen(trim($name)) > 2) {
                  if (array_key_exists($name, $shopping_list)) {
                    $shopping_list[$name][0] += $qty;
                    array_push($shopping_list[$name][1], $meal_count);
                  } else {
                    $shopping_list[$name] = [$qty, [$meal_count]];
                  }
                }
              }
            }
        }
      }
    }
    //dd($shopping_list);
    //dd($raw_ingredients);
    ksort($shopping_list);
    $result = [];
    foreach ($shopping_list as $item => $info) {
      $qty = $info[0];
      if ($qty <= 0) {
        array_push($result, [$item, $info[1]]);
      } else {
        // add qty to ingredient string
        array_push($result, [round($qty, 2)." ".$item, $info[1]]);
      }
    }
    return $result;
  }

  public function staplesToArray($staplesString) {
    if ($staplesString == NULL) {
      return [];
    }
    $staples = [];
    $ids = explode(",", $staplesString);
    for ($i=0; $i < count($ids)-1; $i++) {
      $meal = Meal::find((int) $ids[$i]);
      if ($meal != NULL) {
        array_push($staples, [$meal->name]);
      }
    }
    return $staples;
  }

  /**
   * @param id: the id of the meal plan
   *
   * @Return an array, indexed by day of week.
   * Each index contains an array of nutrients pertaining to the summary for
   * that day
   */
  public function getNutrientSummary($id) {
    //$id = Auth::id();
    $meal_plan = UserController::decodeMealPlan($id);
    $nutrient_summary = [];
    for ($day=0; $day < count($meal_plan); $day++) {
      $day_summary = [];
      // map: name => [qty, unit]
      $nutrient_totals = [];
      for ($rec=0; $rec<count($meal_plan[$day]) - 1; $rec++) {
        //dd($meal_plan[$day]);
        $meal = Meal::find($meal_plan[$day][$rec]);
        if ($meal != NULL) {
          $nutrients = explode("@@", $meal->nutrients);
          for ($nut=0; $nut<count($nutrients)-1; $nut++) {
            $breakdown = explode("::", $nutrients[$nut]);
            if (array_key_exists($breakdown[0], $nutrient_totals)) {
              // add the quantity of nutrients
              $nutrient_totals[$breakdown[0]][0] += $breakdown[1];
            } else {
              $nutrient_totals[$breakdown[0]] = [$breakdown[1], $breakdown[2]];
            }
          }
        }
      }
      //dd($nutrient_totals);
      array_push($nutrient_summary, $nutrient_totals);
    }
    return $nutrient_summary;
  }

  public static function getAverageNutrients($nutrient_summary) {
    $labels = array_keys($nutrient_summary[0]);
    $qtys = array_fill(0, count($labels), 0);
    $units = array_fill(0, count($labels), "");
    for ($d=0; $d< count($nutrient_summary); $d++) {
      for ($nut=0; $nut< count($nutrient_summary[$d]); $nut++) {
        $qtys[$nut] += $nutrient_summary[$d][$labels[$nut]][0];
        $units[$nut] = $nutrient_summary[$d][$labels[$nut]][1];
      }
    }
    return [$labels, $qtys, $units];
  }

  public static function clearToggles($id) {
    $user = User::find($id);
    $meal_toggles = "";
    for ($i=0; $i < 7 * $user->meals_per_day; $i++) {
      $meal_toggles.="on,";
    }
    $user->meal_toggles = $meal_toggles;
    $user->save();
    return redirect('dashboard');
  }

  public static function toggleAll($id) {
    $user = User::find($id);
    $meal_toggles = "";
    for ($i=0; $i < 7 * $user->meals_per_day; $i++) {
      $meal_toggles.="off,";
    }
    $user->meal_toggles = $meal_toggles;
    $user->save();
    return redirect('dashboard');
  }

  /**
   * @param id: the id of the meal plan to decode
   */
  public static function decodeMealPlan($id) {

    $meal_plan_search = MealPlan::find($id);
    if ($meal_plan_search == NULL) {
      return [];
    }
    $meal_plan = $meal_plan_search->plan;
    $days_array = [];
    if ($meal_plan != NULL) {
      $days = explode(";;", $meal_plan);
      foreach ($days as $day) {
        $meals_array = [];
        $meals = explode(",,", $day);
        foreach ($meals as $meal) {
          array_push($meals_array, (int) $meal);
        }
        array_push($days_array, $meals_array);
      }
    }
    return $days_array;
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

  /**
   * @return [qty, "ingredient name"]
   */
  public static function separateQtyFromIngredient(String $ingredient) {
    $any_character = '[\pL\d \.\-\,\"\'\`é\:\/()~"&*]';
    $rangePattern = '/^(\d+)(\s)*(-|or|to)(\s)*(\d+)('.$any_character.'+)/';
    //$rangePattern = '/^(\d+)(\s)*(-|or|to)(\s)*(\d+)([\pL\d \"\.\-\,\:\/()~&"]+)/';
    $fractionPattern = '/^(\d+)\/(\d+)(\s)*('.$any_character.'+)/';
    //$fractionPattern='/^(\d+)\/(\d+)\s*([\pL\d \"\.\-\,\:\/()~"&]+)/';
    $decimalPattern = '/^(\d+)\.(\d+)(\s)*([\pL\d \"\.\-\,\:\/()~"&]+)/';
    $wholeNumberPattern = '/^(\d+)\s*('.$any_character.'+)/';
    //$wholeNumberPattern = '/^(\d+)\s*([\pL\d \.\-\,\"\'\`é\:\/()~"&]+)/';
    $mixedNumberPattern = '/^(\d+)\s(\d+)\/(\d+)\s*('.$any_character.'+)/';
    //$mixedNumberPattern = '/^(\d+)\s(\d+)\/(\d+)\s*([\pL\d \.\-\,\"\:\/()~"&]+)/';
    $startsWithAPattern = '/^[a](\s+)('.$any_character.'+)/';
    //$startsWithAPattern = '/^[a](\s+)([\pL\d \.\-\,\"\:\/()~"&]+)/';
    $startsWithNegOne = '/^(-1)(\s+)('.$any_character.'+)/';
    //$startsWithNegOne = '/^(-1)(\s+)([\pL\d \.*\-\,\"\:\/()~"&]+)/';

    $match = [];
    $qty = 0;
    $item = "";
    // ex. ["1-2 tsp", "1", "2", "tsp"]
    if (preg_match($rangePattern, $ingredient, $match)) {
      $qty = (int) $match[count($match)-2];
      $item = $match[count($match)-1];

      //ex. "1/2 cup" : ["1/2 cup", "1", "2", "cup"]
    } elseif (preg_match($fractionPattern, $ingredient, $match)) {
      $qty = ((int) $match[1]) / ((int) $match[2]);
      $item = $match[3];
      // ex "100.0g" : ["100.50g", "100", "50", "g"]
    } elseif (preg_match($decimalPattern, $ingredient, $match)) {
      //$denominator = 10^strlen($match[2]);
      $trailing = 0;
      if (strlen($match[2]) > 3) {
        $to_string = substr((string) $match[2], 0, 2);
        $trailing = (int) $to_string;
      } else {
        $trailing = $match[2];
      }
      $denominator = 10^strlen($match[2]);
      $qty = ((int) $match[1]) + (((int) $match[2]) / $denominator);
      $item = $match[3];
      //dd($item);
      // ex ["1 1/2 cups", "1", "1", "2" "cups"]
    } elseif (preg_match($mixedNumberPattern, $ingredient, $match)) {
      $qty = ((int) $match[1]) + (((int) $match[2]) / ((int) $match[3]));
      $item = $match[4];

      // ex ["1 cup", "1", "cup"]
    } elseif (preg_match($wholeNumberPattern, $ingredient, $match)) {
      $qty = (int) $match[1];
      $item = $match[2];

      // ex ["a pound of greens", "a", "pound of greens"]
    } elseif (preg_match($startsWithAPattern, $ingredient, $match)) {
      $qty = 1;
      $item = $match[2];
    } elseif (preg_match($startsWithNegOne, $ingredient, $match)) {
      $qty = -1;
      $item = $match[count($match)-1];
    } else {
      $qty = -1;
      $item = $ingredient;
    }
    return [$qty, $item];
  }

  /**
   * @param ingredientBefore : the ingredient string (qty and name)
   *  Ex. "1 cup of blueberries"
   *
   * @return [qty, ingredient] : the ingredient with a standardized/ simplified
   *  name or size, to allow for grouping grocery items
   *  Ex. [1, "cup blueberries"]
   */
  public static function standardizeIngredient(String $ingredientBefore) {
    //$encoded = utf8_decode($ingredientBefore);
    //$encoded = str_replace("Â¼", "1/4", $ingredientBefore);
    //$encoded = str_replace("Â½", "1/2", $encoded);
    //$encoded = str_replace("â½", "1/2", $encoded);
    $encoded = str_replace("½", "1/2", $ingredientBefore);
    $encoded = str_replace("¾", "1/2", $encoded);
    $encoded = str_replace("¼", "1/2", $encoded);
    $encoded = str_replace("º", " degrees", $encoded);
    $encoded = str_replace("É", "E", $encoded);
    $encoded = str_replace("ç", "c", $encoded);
    $encoded = str_replace('"', "", $encoded);
    //$encoded = str_replace("Â¾", "3/4", $encoded);
    //$encoded = str_replace("Â±", "+", $encoded);
    //$encoded = str_replace("Ã©", "e", $encoded);
    //$encoded = str_replace("ã©", "e", $encoded);
    $encoded = str_replace("é", "e", $encoded);
    $encoded = str_replace("Â", "", $encoded);
    $encoded = str_replace("ñ", "n", $encoded);
    $encoded = str_replace("â", "n", $encoded);
    $encoded = str_replace("*", "", $encoded);

    //$decoded_ingredient = str_replace("?", "", $decoded_ingredient);
    [$qty, $ingredient] = UserController::separateQtyFromIngredient($encoded);
    $cleaned = strtolower($ingredient);
    $saltPattern = '/salt$/';
    $pepperPattern = '/black pepper$/';
    $waterPattern = '/water$/';
    $saltAndPepperPattern = '/salt\s*([\pL\d \.\-\,\:\/()&]+)pepper/';
    $forTheIngredientPattern = '/for the(.)*:/';

    $cleaned = trim(str_replace("of ", "", $cleaned));
    // remove things like (~1/3 cup)
    $aboutPattern = '/\((~|about)([\pL\d \.\-\,\:\/()"]+)\)/';
    $cleaned = preg_replace($aboutPattern, "", $cleaned);
    $toTastePattern = '/(\s+)(\(|or|(to))([\pL\d \.\-\,\:\/()~"]+)taste\)?/';
    $cleaned = preg_replace($toTastePattern, "", $cleaned);
    //removed things like "(diced and seeded)"
    //$verbedAndVerbedPattern = '/(\s)*(\(|,)?([\pL\d\.\-\,\:\/()~"]+) and ([\pL\d \.\-\,\:\/()~"]+)ed\)?/';
    $freshOrFrozenPattern = '/(\s)*(\(|,)?(fresh|frozen) or (fresh|frozen)\)?/';
    $storeOrHomePattern = '/(\s)*(\(|,)?(store|home)([\w -])*or([\w -])*(bought|made)\)?/';
    $forBlankMinutesPattern = '/for([\pL\d\.\-\,\:\/()~" ]+)minutes/';
    $forBlankMinutesPattern = '/for([\pL\d\.\-\,\:\/()~" ]+)hours/';
    $intoPiecesPattern = '/into([\pL\d\.\-\,\:\/()~" ]+)(strips|pieces|wedges)/';
    $dashSmallPattern = '/-(\s)*(small|medium|large)$/';

    //$cleaned = preg_replace($verbedAndVerbedPattern, "", $cleaned);
    $cleaned = preg_replace($freshOrFrozenPattern, "", $cleaned);
    $cleaned = preg_replace($storeOrHomePattern, "", $cleaned);
    $cleaned = preg_replace($forBlankMinutesPattern, "", $cleaned);
    $cleaned = preg_replace($intoPiecesPattern, "", $cleaned);
    $cleaned = preg_replace($dashSmallPattern, "", $cleaned);


    $replaced = str_replace("several dashes", "tbsp", $cleaned);
    $replaced = str_replace("garnish with", "tbsp", $cleaned);
    $replaced = str_replace("dash ", "tbsp ", $replaced);
    $replaced = str_replace("pinch ", "tbsp ", $replaced);
    if ($replaced != $cleaned) {
      $qty = 0.5;
      $cleaned = $replaced;
    }
    // numbers spelled out
    $cleaned = str_replace("one ", "1 ", $cleaned);
    $cleaned = str_replace("two ", "2 ", $cleaned);
    $cleaned = str_replace("three ", "3 ", $cleaned);
    $cleaned = str_replace("twelve", "12", $cleaned);
    // specific things to trash
    $cleaned = str_replace("to make your own gather the following:", "", $cleaned);
    $cleaned = str_replace("depending on your preference", "", $cleaned);
    $cleaned = str_replace("several times", "", $cleaned);
    $cleaned = str_replace("for serving", "", $cleaned);
    $cleaned = str_replace("to grease", "", $cleaned);
    $cleaned = str_replace("(see note)", "", $cleaned);
    $cleaned = str_replace("(see above)", "", $cleaned);
    $cleaned = str_replace("(firmly packed)", "", $cleaned);
    $cleaned = str_replace("sea salt", "salt", $cleaned);
    $cleaned = str_replace("coarse salt", "salt", $cleaned);
    $cleaned = str_replace("stems trimmed", "", $cleaned);
    $cleaned = str_replace(", washed and ", "", $cleaned);
    $cleaned = str_replace("1-pound block", "lbs", $cleaned);
    $cleaned = str_replace("plus extra", "", $cleaned);
    $cleaned = str_replace("as needed", "", $cleaned);
    $cleaned = str_replace("as desired", "", $cleaned);
    $cleaned = str_replace("plus more for", "", $cleaned);
    $cleaned = str_replace("drizzling", "", $cleaned);
    $cleaned = str_replace("if available", "", $cleaned);
    $cleaned = str_replace("thick as a pencil", "", $cleaned);
    $cleaned = str_replace("paper thin", "", $cleaned);
    $cleaned = str_replace("approximately", "", $cleaned);
    // punctuation
    $cleaned = str_replace(",", "", $cleaned);
    $cleaned = str_replace(".", "", $cleaned);
    // pluralize
    $cleaned = str_replace("a bunch ", "bunches ", $cleaned);
    $cleaned = str_replace("bunch ", "bunches ", $cleaned);
    $cleaned = str_replace("banana ", "bananas ", $cleaned);
    $cleaned = str_replace("potato ", "potatoes ", $cleaned);
    if ($qty == 1) {
      $cleaned = str_replace("avocado", "avocados", $cleaned);
    }
    $cleaned = str_replace("avocado ", "avocados ", $cleaned);
    $cleaned = str_replace("tomato ", "tomatoes ", $cleaned);
    // cooking preperations
    $cleaned = str_replace("squeezed out", "", $cleaned);
    $cleaned = str_replace("extra water", "", $cleaned);
    $cleaned = str_replace("ends off", "", $cleaned);
      $cleaned = str_replace("crosswise", "", $cleaned);
    $cleaned = str_replace("and into", "", $cleaned);
    $cleaned = str_replace("to serve", "", $cleaned);
    $cleaned = str_replace("in water", "", $cleaned);
    $cleaned = str_replace("overnight", "", $cleaned);
    $cleaned = str_replace("for frying", "", $cleaned);
    $cleaned = str_replace("into slices", "", $cleaned);
    $cleaned = str_replace("leftover", "", $cleaned);
    $cleaned = str_replace("into 1-inch", "", $cleaned);
    $cleaned = str_replace("to smaller", "", $cleaned);
    $cleaned = str_replace("until soft and tender", "", $cleaned);
    $cleaned = str_replace("in half", "", $cleaned);
    $cleaned = str_replace("patted dry", "", $cleaned);
    $cleaned = str_replace("in puree", "", $cleaned);
    $cleaned = str_replace("rind discarded", "", $cleaned);
    $cleaned = str_replace("into chunks", "", $cleaned);
    $cleaned = str_replace("for brushing", "", $cleaned);
    $cleaned = str_replace("drizzle", "", $cleaned);
    // unnecessary food adjectives
    $cleaned = str_replace("extra virgin", "", $cleaned);
    $cleaned = str_replace("extra-virgin", "", $cleaned);
    $cleaned = str_replace("ripe ", " ", $cleaned);
    $cleaned = str_replace("fresh ", " ", $cleaned);
    $cleaned = str_replace("medium", "", $cleaned);
    $cleaned = str_replace("small ", " ", $cleaned);
    $cleaned = str_replace("large ", " ", $cleaned);
    $cleaned = str_replace("generous ", "", $cleaned);
    $cleaned = str_replace("good-quality", "", $cleaned);
    $cleaned = str_replace("best quality", "", $cleaned);
    $cleaned = str_replace("good ", " ", $cleaned);
    $cleaned = str_replace("very ", " ", $cleaned);
    $cleaned = str_replace("about ", " ", $cleaned);
    $cleaned = str_replace("loosely packed", "", $cleaned);
    // cooking adjectives
    $cleaned = str_replace("ground", "", $cleaned);
    $cleaned = str_replace("toasted", "", $cleaned);
    $cleaned = str_replace("chopped", "", $cleaned);
    $cleaned = str_replace(" picked", " ", $cleaned);
    $cleaned = str_replace("scrubbed", "", $cleaned);
    $cleaned = str_replace("discarded", "", $cleaned);
    $cleaned = str_replace("diced", "", $cleaned);
    $cleaned = str_replace("minced", "", $cleaned);
    $cleaned = str_replace("cooled", "", $cleaned);
    $cleaned = str_replace("warmed", "", $cleaned);
    $cleaned = str_replace("quartered", "", $cleaned);
    $cleaned = str_replace(" cooked", " ", $cleaned);
    $cleaned = str_replace("peeled", "", $cleaned);
    $cleaned = str_replace("cored", "", $cleaned);
    $cleaned = str_replace("trimmed", "", $cleaned);
    $cleaned = str_replace("cut ", " ", $cleaned);
    $cleaned = str_replace("torn ", " ", $cleaned);
    $cleaned = str_replace("soaked", "", $cleaned);
    $cleaned = str_replace("drained", "", $cleaned);
    $cleaned = str_replace("rinsed", "", $cleaned);
    $cleaned = str_replace("sliced", "", $cleaned);
    $cleaned = str_replace("grated", "", $cleaned);
    $cleaned = str_replace("shredded", "", $cleaned);
    $cleaned = str_replace("thawed", "", $cleaned);
    $cleaned = str_replace("pitted", "", $cleaned);
    $cleaned = str_replace("cubed", "", $cleaned);
    $cleaned = str_replace("washed", "", $cleaned);
    $cleaned = str_replace("skinned", "", $cleaned);
    $cleaned = str_replace("slivered", "", $cleaned);
    $cleaned = str_replace("halved", "", $cleaned);
    $cleaned = str_replace("juiced", "", $cleaned);
    $cleaned = str_replace("cold ", " ", $cleaned);
    $cleaned = str_replace(" cold", " ", $cleaned);
    $cleaned = str_replace("prepared", "", $cleaned);
    // other adjectives
    $cleaned = str_replace("well ", " ", $cleaned);
    $cleaned = str_replace("scant ", " ", $cleaned);
    $cleaned = str_replace("few ", " ", $cleaned);
    $cleaned = str_replace("from ", " ", $cleaned);
    // nouns to get rid of
    $cleaned = str_replace("slices ", " ", $cleaned);
    $cleaned = str_replace("pieces ", " ", $cleaned);
    $cleaned = str_replace("up ", " ", $cleaned);
    // adverbs
    $cleaned = str_replace("finely", "", $cleaned);
    $cleaned = str_replace("coarsely ", " ", $cleaned);
    $cleaned = str_replace("thinly ", " ", $cleaned);
    $cleaned = str_replace("roughly ", " ", $cleaned);
    $cleaned = str_replace("freshly ", " ", $cleaned);
    $cleaned = str_replace("preferably ", " ", $cleaned);
    $cleaned = str_replace("thickly ", " ", $cleaned);
    // spelling
    $cleaned = str_replace("galic", "garlic", $cleaned);
    $cleaned = str_replace("garlic cloves", "cloves garlic", $cleaned);
    $cleaned = str_replace("garlic clove", "cloves garlic", $cleaned);
    $cleaned = str_replace("sun flower", "sunflower", $cleaned);
    $cleaned = str_replace("sun flour", "sunflower", $cleaned);
    $cleaned = str_replace("sunflour", "sunflower", $cleaned);
    $cleaned = str_replace("vinager", "vinegar", $cleaned);
    // grammar
    $cleaned = str_replace("or not", "", $cleaned);
    $cleaned = str_replace("a bit", "", $cleaned);
    $cleaned = str_replace("big ", " ", $cleaned);
    // quantity notation
    $cleaned = str_replace("x ", "", $cleaned);
    $cleaned = str_replace(" oz", "oz", $cleaned);
    $cleaned = str_replace("ounce", "oz", $cleaned);
    $cleaned = str_replace("-oz", "oz", $cleaned);
    $cleaned = str_replace(" ozs", " oz", $cleaned);
    $cleaned = str_replace(" ounces", "oz", $cleaned);
    $cleaned = str_replace("-pound", " lbs", $cleaned);
    $cleaned = str_replace(" pounds", " lbs", $cleaned);
    $cleaned = str_replace(" ml", "ml", $cleaned);
    $cleaned = str_replace("ounces", "oz", $cleaned);
    $cleaned = str_replace("grams ", "g ", $cleaned);
    $cleaned = str_replace("gram ", "g", $cleaned);
    $cleaned = str_replace("teaspoon/", "tsp ", $cleaned);
    $cleaned = str_replace("tablespoon", "tbsp", $cleaned);
    $cleaned = str_replace("tblsp", "tbsp", $cleaned);
    $cleaned = str_replace(" t ", " tbsp ", $cleaned);

    if (preg_match($saltPattern, $cleaned, $match) ||
        preg_match($pepperPattern, $cleaned, $match) ||
        preg_match($saltAndPepperPattern, $cleaned, $match) ||
        preg_match($waterPattern, $cleaned, $match) ||
        preg_match($forTheIngredientPattern, $cleaned, $match)) {
        return ["", 0];
    }

    // cleanup
    $cleaned = str_replace("()", "", $cleaned);
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
    if (($unit == "tablespoon") || ($unit == "tablespoons") || ($unit == "tbsp.") || ($unit == "tbsp")
        || ($unit == "tbs.") || ($unit == "tbs") || ($unit == "tbsps") || ($unit == "t")) {

        $food[0] = "tbsp";
        if (($qty == -1) || ($qty == 0)) {
          $qty = 1;
        }
    } elseif (($unit == "cup") || ($unit == "c.") || ($unit == "c") || ($unit == "cs")) {
      $food[0] = "cups";
      if (($qty == -1) || ($qty == 0)) {
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
    } else if (($unit == "pounds") || ($unit == "lbs.") || ($unit == "pound")
              || ($unit == "lb")) {
      if ($qty == -1) {
        $qty = 1;
      }
      $food[0] = "lbs";
    } else if (($unit == "grams") || ($unit == "grams") || ($unit == "gram")) {
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
    if (($food[count($food)-1] == "and") || ($food[count($food)-1] == "or") ||
        ($food[count($food)-1] == "-") || ($food[count($food)-1] == "&")) {
        $food[count($food)-1] = "";
    }
    if (($food[0] == "and") || ($food[0] == "or") ||
        ($food[0] == "-") || ($food[0] == "&")) {
        $food[0] = "";
    }
    if (($food[count($food)-1] == "oil") && ($qty == -1)) {
      $qty = 2;
      $food[0] = "tbsp ".$food[0];
    }
    if (($food[0] == "juice") && ($qty == -1) && (count($food) > 1)) {
      $qty = $food[1];
      $food[0] = "";
      $food[1] = "";
    }
    $patched = implode(" ", $food);
    return [$qty, trim($patched)];
  }
}
