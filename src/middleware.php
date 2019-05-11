<?php

$app->add(new \Slim\Middleware\Session([
    'name' => 'pluralsight_session',
    'autorefresh' => true,
    'lifetime' => '1 hour'
]));