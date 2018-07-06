<?php

use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentController;
use Typemill\Middleware\RestrictApiAccess;

$app->get('/api/v1/themes', SettingsController::class . ':getThemeSettings')->setName('api.themes')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/article', ContentController::class . ':updateArticle')->setName('api.article.update')->add(new RestrictApiAccess($container['router']));