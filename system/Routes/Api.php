<?php

use Typemill\Controllers\SettingsController;
use Typemill\Controllers\ContentController;
use Typemill\Controllers\ContentApiController;
use Typemill\Controllers\ArticleApiController;
use Typemill\Controllers\BlockApiController;
use Typemill\Controllers\MediaApiController;
use Typemill\Controllers\MetaApiController;
use Typemill\Middleware\RestrictApiAccess;

$app->get('/api/v1/themes', SettingsController::class . ':getThemeSettings')->setName('api.themes')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/clearcache', SettingsController::class . ':clearCache')->setName('api.clearcache')->add(new RestrictApiAccess($container['router']));

$app->post('/api/v1/article/markdown', ArticleApiController::class . ':getArticleMarkdown')->setName('api.article.markdown')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/html', ArticleApiController::class . ':getArticleHtml')->setName('api.article.html')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/publish', ArticleApiController::class . ':publishArticle')->setName('api.article.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article/unpublish', ArticleApiController::class . ':unpublishArticle')->setName('api.article.unpublish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article/discard', ArticleApiController::class . ':discardArticleChanges')->setName('api.article.discard')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/sort', ArticleApiController::class . ':sortArticle')->setName('api.article.sort')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article', ArticleApiController::class . ':createArticle')->setName('api.article.create')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/article', ArticleApiController::class . ':updateArticle')->setName('api.article.update')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article', ArticleApiController::class . ':deleteArticle')->setName('api.article.delete')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/baseitem', ArticleApiController::class . ':createBaseItem')->setName('api.baseitem.create')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/navigation', ArticleApiController::class . ':getNavigation')->setName('api.navigation.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/post', ArticleApiController::class . ':createPost')->setName('api.post.create')->add(new RestrictApiAccess($container['router']));

$app->get('/api/v1/metadefinitions', MetaApiController::class . ':getMetaDefinitions')->setName('api.metadefinitions.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/article/metaobject', MetaApiController::class . ':getArticleMetaobject')->setName('api.articlemetaobject.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/article/metadata', MetaApiController::class . ':getArticleMeta')->setName('api.articlemeta.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/metadata', MetaApiController::class . ':updateArticleMeta')->setName('api.articlemeta.update')->add(new RestrictApiAccess($container['router']));

$app->post('/api/v1/block', BlockApiController::class . ':addBlock')->setName('api.block.add')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/block', BlockApiController::class . ':updateBlock')->setName('api.block.update')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/block', BlockApiController::class . ':deleteBlock')->setName('api.block.delete')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/moveblock', BlockApiController::class . ':moveBlock')->setName('api.block.move')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/video', BlockApiController::class . ':saveVideoImage')->setName('api.video.save')->add(new RestrictApiAccess($container['router']));

$app->get('/api/v1/medialib/images', MediaApiController::class . ':getMediaLibImages')->setName('api.medialibimg.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/medialib/files', MediaApiController::class . ':getMediaLibFiles')->setName('api.medialibfiles.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/image', MediaApiController::class . ':getImage')->setName('api.image.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/image', MediaApiController::class . ':createImage')->setName('api.image.create')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/image', MediaApiController::class . ':publishImage')->setName('api.image.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/image', MediaApiController::class . ':deleteImage')->setName('api.image.delete')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/file', MediaApiController::class . ':getFile')->setName('api.file.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/file', MediaApiController::class . ':uploadFile')->setName('api.file.upload')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/file', MediaApiController::class . ':publishFile')->setName('api.file.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/file', MediaApiController::class . ':deleteFile')->setName('api.file.delete')->add(new RestrictApiAccess($container['router']));