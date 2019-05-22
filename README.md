# Pluralsight Skill IQs Reader

Use this application to agregate multiple users data on one page. 
 
You can use it to follow your team members progress at Pluralsight

## Requirements

1) All users must set their profile as public and publish their skills by editing profile:
https://app.pluralsight.com/profile/edit 

2) You must get their pluralsight internel user id, ex. from browser console from one of the ajax requests
https://app.pluralsight.com/profile/data/skillmeasurements/6003f2f8-23e4-4711-aecd-e84f0a7e40a4

## Install the Application

Run this command from the directory in which you want to install your new Slim Framework application.

    composer install
    chmod u+x install.sh
    ./install.sh

Configure application:

    pluralsight[users] - pluralsight profile list

or

    pluralsight[userSheet] - pluralsight profile list google sheet
    
Optional configuration:
    
    pluralsight[order] - show these skills first and order them
    pluralsight[recent_to_show] - how many recent skills to show in recent page
    pluralsight[usersDetails] - names to show when admin users logged - when pluralsight[users] mode
    google[clientId] - sign in with google (app id)
    users - ofter these emails sign in with google they see names instead of ids

Run php server or setup your vhost configuration to /public
    
    php -S localhost:8080 -t public
   
Clear cache /refresh skill ex. setup in crontab on daily basis
    
    ./clear_cache.sh

## Google spreadsheet integration

Create and configure project for Google sign-in
https://developers.google.com/identity/sign-in/web/sign-in
Copy clientId to configuration:
    
    google[clientId]    

Enable Google Spreadsheet integration
https://console.developers.google.com/apis/api/sheets.googleapis.com/  
Insert document id to configuration:

    pluralsight[userSheet]

## Dev dependencies
PHP 7.x
   
## What's new
2019-05-22 

Integration with google spreadsheets to import user data when user logged

2019-05-11 

Sign in with google to see user details, session handling

2019-05-09 

Recently updated skills page

2019-04-30 

Style, avarages, recent skills, tools

2019-04-29 

Project start


