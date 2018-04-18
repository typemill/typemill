<?php

use Typemill\Controllers\SettingsController;

$app->get('/api/v1/themes', SettingsController::class . ':getThemeSettings')->setName('api.themes');