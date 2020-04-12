<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NutriBalancer</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet" type="text/css" >
    @yield('assets')
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script>
  $( function() {
    $( "#calorie_range" ).slider({
      range: true,
      min: 1000,
      max: 5000,
      values: [ 2000, 2500 ],
      slide: function( event, ui ) {
        $( "#amount" ).val(ui.values[0] + " - " + ui.values[1]);

      }
    });
    $( "#amount" ).val( $( "#calorie_range" ).slider( "values", 0 ) +
      " - " + $( "#calorie_range" ).slider( "values", 1 ) );
  } );
  </script>
    <meta charset="utf-8">
</head>

<body>
<div class="container">
    <div class="row">
        <div class="col-lg-8"> @yield('content') </div>
    </div>
</div>
</body>

</html>
