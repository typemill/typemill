<?php

use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentController;
use Typemill\Controllers\ContentApiController;
use Typemill\Middleware\RestrictApiAccess;

$app->get('/api/v1/themes', SettingsController::class . ':getThemeSettings')->setName('api.themes')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/publish', ContentApiController::class . ':publishArticle')->setName('api.article.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article/unpublish', ContentApiController::class . ':unpublishArticle')->setName('api.article.unpublish')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/article', ContentApiController::class . ':updateArticle')->setName('api.article.update')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article', ContentApiController::class . ':deleteArticle')->setName('api.article.delete')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/sort', ContentApiController::class . ':sortArticle')->setName('api.article.sort')->add(new RestrictApiAccess($container['router']));
// $app->post('/api/v1/block', ContentBackendController::class . ':createBlock')->setName('api.block.create')->add(new RestrictApiAccess($container['router']));
