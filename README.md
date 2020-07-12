## About NutriBalancer
NutriBalancer is an application that leverages the Edamam API to generate unique weekly meal plans for users, offering
them a recipe for each meal of the day, and searching for those recipes based on their favorite foods. Users can refresh
meals in order to get another suggestion. They are given a shopping list of all the ingredients they need to buy for the week. If they "suppress" a recipe, it will not appear on the shopping list.

Users can also add their own staple recipes.

## Pre-requisites
In addition to everything in composer.json:
- MySQL 8+

## Setup Instructions
Not complete.

First-time setup:

1. 'php artisan migrate:refresh'
2. 'composer dump-autoload'
3. 'php artisan db:seed'

Start the server: 'php artisan serve'


## Contributing

If you are interested in contributing to this project, please shoot me an email at kimberlydawnbrown242@gmail.com!


## Security Vulnerabilities

Please do not rely on this app for any kind of security. The app is not authenticating users- it is passing user id's around in the URLs.
Consider using a fake email and making a unique password for just this application. I am working on making the app more secure. 


## Known Bugs
The hovering highlight on the "modify favorites" page is visually glitchy.
