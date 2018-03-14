<?php

use Typemill\Controllers\SetupController;

$app->get('/api/v1/themes', SetupController::class . ':themes')->setName('themes');