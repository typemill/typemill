<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Middleware\ApiAuthentication;
use Typemill\Middleware\ApiAuthorization;
use Typemill\Controllers\ControllerApiGlobals;
use Typemill\Controllers\ControllerApiMedia;
use Typemill\Controllers\ControllerApiSystemSettings;
use Typemill\Controllers\ControllerApiSystemThemes;
use Typemill\Controllers\ControllerApiSystemPlugins;
use Typemill\Controllers\ControllerApiSystemExtensions;
use Typemill\Controllers\ControllerApiSystemLicense;
use Typemill\Controllers\ControllerApiSystemUsers;
use Typemill\Controllers\ControllerApiImage;
use Typemill\Controllers\ControllerApiFile;
use Typemill\Controllers\ControllerApiAuthorArticle;
use Typemill\Controllers\ControllerApiAuthorBlock;
use Typemill\Controllers\ControllerApiAuthorMeta;
use Typemill\Controllers\ControllerApiAuthorShortcode;

$app->group('/api/v1', function (RouteCollectorProxy $group) use ($acl) {

	# GLOBALS
	$group->get('/systemnavi', ControllerApiGlobals::class . ':getSystemnavi')->setName('api.systemnavi.get')->add(new ApiAuthorization($acl, 'account', 'view')); # member
	$group->get('/mainnavi', ControllerApiGlobals::class . ':getMainnavi')->setName('api.mainnavi.get')->add(new ApiAuthorization($acl, 'account', 'view')); # member

	# SYSTEM
	$group->get('/settings', ControllerApiSystemSettings::class . ':getSettings')->setName('api.settings.get')->add(new ApiAuthorization($acl, 'system', 'view')); # admin
	$group->post('/settings', ControllerApiSystemSettings::class . ':updateSettings')->setName('api.settings.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/license', ControllerApiSystemLicense::class . ':createLicense')->setName('api.license.create')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/theme', ControllerApiSystemThemes::class . ':updateTheme')->setName('api.theme.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/plugin', ControllerApiSystemPlugins::class . ':updatePlugin')->setName('api.plugin.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/extensions', ControllerApiSystemExtensions::class . ':activateExtension')->setName('api.extension.activate')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->get('/users/getbynames', ControllerApiSystemUsers::class . ':getUsersByNames')->setName('api.usersbynames')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/users/getbyemail', ControllerApiSystemUsers::class . ':getUsersByEmail')->setName('api.usersbyemail')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/users/getbyrole', ControllerApiSystemUsers::class . ':getUsersByRole')->setName('api.usersbyrole')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/userform', ControllerApiSystemUsers::class . ':getNewUserForm')->setName('api.user.form')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->post('/user', ControllerApiSystemUsers::class . ':createUser')->setName('api.user.create')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->put('/user', ControllerApiSystemUsers::class . ':updateUser')->setName('api.user.update')->add(new ApiAuthorization($acl, 'account', 'update')); # member
	$group->delete('/user', ControllerApiSystemUsers::class . ':deleteUser')->setName('api.user.delete')->add(new ApiAuthorization($acl, 'account', 'delete')); # member

	# IMAGES
	$group->post('/image', ControllerApiImage::class . ':saveImage')->setName('api.image.create')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->put('/image', ControllerApiImage::class . ':publishImage')->setName('api.image.publish')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
#	$group->get('/image', ControllerApiMedia::class . ':getImage')->setName('api.image.get');
#	$group->delete('/image', ControllerApiMedia::class . ':deleteImage')->setName('api.image.delete');
	
	# FILES
	$group->get('/filerestrictions', ControllerApiFile::class . ':getFileRestrictions')->setName('api.file.getrestrictions')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->post('/filerestrictions', ControllerApiFile::class . ':updateFileRestrictions')->setName('api.file.updaterestrictions')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->post('/file', ControllerApiFile::class . ':uploadFile')->setName('api.file.upload')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->put('/file', ControllerApiFile::class . ':publishFile')->setName('api.file.publish')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
#	$group->get('/api/v1/file', ControllerAuthorMediaApi::class . ':getFile')->setName('api.file.get')->add(new RestrictApiAccess($container['router']));
#	$app->delete('/api/v1/file', ControllerAuthorMediaApi::class . ':deleteFile')->setName('api.file.delete')->add(new RestrictApiAccess($container['router']));

	# ARTICLE
	$group->post('/article/sort', ControllerApiAuthorArticle::class . ':sortArticle')->setName('api.article.sort')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->post('/article/rename', ControllerApiAuthorArticle::class . ':renameArticle')->setName('api.article.rename')->add(new ApiAuthorization($acl, 'content', 'publish'));
	$group->post('/article/publish', ControllerApiAuthorArticle::class . ':publishArticle')->setName('api.article.publish')->add(new ApiAuthorization($acl, 'content', 'publish'));
	$group->delete('/article/unpublish', ControllerApiAuthorArticle::class . ':unpublishArticle')->setName('api.article.unpublish')->add(new ApiAuthorization($acl, 'content', 'unpublish'));
	$group->delete('/article/discard', ControllerApiAuthorArticle::class . ':discardArticleChanges')->setName('api.article.discard')->add(new ApiAuthorization($acl, 'content', 'edit'));
	$group->delete('/article', ControllerApiAuthorArticle::class . ':deleteArticle')->setName('api.article.delete')->add(new ApiAuthorization($acl, 'content', 'delete'));
	$group->post('/article', ControllerApiAuthorArticle::class . ':createArticle')->setName('api.article.create')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->put('/draft', ControllerApiAuthorArticle::class . ':updateDraft')->setName('api.draft.update')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->post('/draft/publish', ControllerApiAuthorArticle::class . ':publishDraft')->setName('api.draft.publish')->add(new ApiAuthorization($acl, 'content', 'create')); # author

	# BLOCKS
	$group->post('/block', ControllerApiAuthorBlock::class . ':addBlock')->setName('api.block.add')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->put('/block/move', ControllerApiAuthorBlock::class . ':moveBlock')->setName('api.block.move')->add(new ApiAuthorization($acl, 'mycontent', 'view'));
	$group->put('/block', ControllerApiAuthorBlock::class . ':updateBlock')->setName('api.block.update')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->delete('/block', ControllerApiAuthorBlock::class . ':deleteBlock')->setName('api.block.delete')->add(new ApiAuthorization($acl, 'mycontent', 'delete'));
	$group->post('/video', ControllerApiImage::class . ':saveVideoImage')->setName('api.video.save')->add(new ApiAuthorization($acl, 'mycontent', 'view'));

	# SHORTCODE
	$group->get('/shortcodedata', ControllerApiAuthorShortcode::class . ':getShortcodeData')->setName('api.shortcodedata.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));

	# META
	$group->get('/metadata', ControllerApiAuthorMeta::class . ':getMetaData')->setName('api.metadata.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));
	$group->get('/metadefinitions', ControllerApiAuthorMeta::class . ':getMetaDefinitions')->setName('api.definitions.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));
	$group->post('/metadata', ControllerApiAuthorMeta::class . ':updateMetaData')->setName('api.metadata.update')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->get('/meta', ControllerApiAuthorMeta::class . ':getMeta')->setName('api.meta.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));

})->add(new ApiAuthentication());