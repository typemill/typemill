<?php

use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentController;
use Typemill\Middleware\RedirectIfUnauthenticated;
use Typemill\Middleware\RedirectIfAuthenticated;
use Typemill\Middleware\RedirectIfNoAdmin;

$app->get('/api/v1/themes', SettingsController::class . ':getThemeSettings')->setName('api.themes');
$app->put('/api/v1/article', ContentController::class . ':updateArticle')->setName('api.article.update')->add(new RedirectIfUnauthenticated($container['router'], $container['flash']));