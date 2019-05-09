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

Configure your product feed url in settings.php
    
    'users' => [
        '6003f2f8-23e4-4711-aecd-e84f0a7e40a4',
    ]

Run php server or setup your vhost configuration to /public
    
    php -S localhost:8080 -t public
   
Clear cache /refresh skill ex. setup in crontab on daily basis
    
    ./clear_cache.sh

   
## Dev dependencies
PHP 7.x
   
## What's new
2019-05-09 

Recently updated skills page

2019-04-30 

Style, avarages, recent skills, tools

2019-04-29 

Project start


