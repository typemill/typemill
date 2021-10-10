<?php

use Typemill\Controllers\ControllerAuthorArticleApi;
use Typemill\Controllers\ControllerAuthorBlockApi;
use Typemill\Controllers\ControllerAuthorMetaApi;
use Typemill\Controllers\ControllerAuthorMediaApi;
use Typemill\Controllers\ControllerSettings;
use Typemill\Middleware\RestrictApiAccess;

$app->get('/api/v1/themes', ControllerSettings::class . ':getThemeSettings')->setName('api.themes')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/clearcache', ControllerSettings::class . ':clearCache')->setName('api.clearcache')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/users/getbynames', ControllerSettings::class . ':getUsersByNames')->setName('api.usersbynames')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/users/getbyemail', ControllerSettings::class . ':getUsersByEmail')->setName('api.usersbyemail')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/users/getbyrole', ControllerSettings::class . ':getUsersByRole')->setName('api.usersbyrole')->add(new RestrictApiAccess($container['router']));

$app->post('/api/v1/article/markdown', ControllerAuthorArticleApi::class . ':getArticleMarkdown')->setName('api.article.markdown')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/html', ControllerAuthorArticleApi::class . ':getArticleHtml')->setName('api.article.html')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/publish', ControllerAuthorArticleApi::class . ':publishArticle')->setName('api.article.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article/unpublish', ControllerAuthorArticleApi::class . ':unpublishArticle')->setName('api.article.unpublish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article/discard', ControllerAuthorArticleApi::class . ':discardArticleChanges')->setName('api.article.discard')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/rename', ControllerAuthorArticleApi::class . ':renameArticle')->setName('api.article.rename')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/sort', ControllerAuthorArticleApi::class . ':sortArticle')->setName('api.article.sort')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article', ControllerAuthorArticleApi::class . ':createArticle')->setName('api.article.create')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/article', ControllerAuthorArticleApi::class . ':updateArticle')->setName('api.article.update')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/article', ControllerAuthorArticleApi::class . ':deleteArticle')->setName('api.article.delete')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/baseitem', ControllerAuthorArticleApi::class . ':createBaseItem')->setName('api.baseitem.create')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/navigation', ControllerAuthorArticleApi::class . ':getNavigation')->setName('api.navigation.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/post', ControllerAuthorArticleApi::class . ':createPost')->setName('api.post.create')->add(new RestrictApiAccess($container['router']));

$app->get('/api/v1/metadefinitions', ControllerAuthorMetaApi::class . ':getMetaDefinitions')->setName('api.metadefinitions.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/article/metaobject', ControllerAuthorMetaApi::class . ':getArticleMetaobject')->setName('api.articlemetaobject.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/article/metadata', ControllerAuthorMetaApi::class . ':getArticleMeta')->setName('api.articlemeta.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/article/metadata', ControllerAuthorMetaApi::class . ':updateArticleMeta')->setName('api.articlemeta.update')->add(new RestrictApiAccess($container['router']));

$app->post('/api/v1/block', ControllerAuthorBlockApi::class . ':addBlock')->setName('api.block.add')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/block', ControllerAuthorBlockApi::class . ':updateBlock')->setName('api.block.update')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/block', ControllerAuthorBlockApi::class . ':deleteBlock')->setName('api.block.delete')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/moveblock', ControllerAuthorBlockApi::class . ':moveBlock')->setName('api.block.move')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/video', ControllerAuthorBlockApi::class . ':saveVideoImage')->setName('api.video.save')->add(new RestrictApiAccess($container['router']));

$app->get('/api/v1/medialib/images', ControllerAuthorMediaApi::class . ':getMediaLibImages')->setName('api.medialibimg.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/medialib/files', ControllerAuthorMediaApi::class . ':getMediaLibFiles')->setName('api.medialibfiles.get')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/image', ControllerAuthorMediaApi::class . ':getImage')->setName('api.image.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/image', ControllerAuthorMediaApi::class . ':createImage')->setName('api.image.create')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/image', ControllerAuthorMediaApi::class . ':publishImage')->setName('api.image.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/image', ControllerAuthorMediaApi::class . ':deleteImage')->setName('api.image.delete')->add(new RestrictApiAccess($container['router']));
$app->get('/api/v1/file', ControllerAuthorMediaApi::class . ':getFile')->setName('api.file.get')->add(new RestrictApiAccess($container['router']));
$app->post('/api/v1/file', ControllerAuthorMediaApi::class . ':uploadFile')->setName('api.file.upload')->add(new RestrictApiAccess($container['router']));
$app->put('/api/v1/file', ControllerAuthorMediaApi::class . ':publishFile')->setName('api.file.publish')->add(new RestrictApiAccess($container['router']));
$app->delete('/api/v1/file', ControllerAuthorMediaApi::class . ':deleteFile')->setName('api.file.delete')->add(new RestrictApiAccess($container['router']));