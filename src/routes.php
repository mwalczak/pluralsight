<?php

use Controller\AppController;

// Routes

$app->get('/', AppController::class . ':usersAction')->setName('home');

$app->get('/user/{id}', AppController::class . ':userAction')->setName('user');
