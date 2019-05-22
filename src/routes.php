<?php

use Controller\AppController;

// Routes

$app->get('/', AppController::class . ':usersAction')->setName('home');

$app->get('/user/{id}', AppController::class . ':userAction')->setName('user');

$app->get('/recent', AppController::class . ':recentAction')->setName('recent');

$app->post('/tokensignin', AppController::class . ':tokenSignInAction');

$app->get('/tokensignout', AppController::class . ':tokenSignOutAction');

$app->get('/csv', AppController::class . ':usersAction')->setArgument('csv', 1)->setName('csv');

$app->get('/import', AppController::class . ':importAction')->setName('import');

$app->get('/importCheck', AppController::class . ':importAction')->setArgument('check', 1)->setName('import');
