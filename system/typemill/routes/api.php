<?php

use Slim\Routing\RouteCollectorProxy;
use Typemill\Middleware\ApiAuthentication;
use Typemill\Middleware\ApiAuthorization;
use Typemill\Middleware\CorsHeadersMiddleware;
use Typemill\Controllers\ControllerApiGlobals;
use Typemill\Controllers\ControllerApiMedia;
use Typemill\Controllers\ControllerApiSystemSettings;
use Typemill\Controllers\ControllerApiSystemThemes;
use Typemill\Controllers\ControllerApiSystemPlugins;
use Typemill\Controllers\ControllerApiSystemExtensions;
use Typemill\Controllers\ControllerApiSystemLicense;
use Typemill\Controllers\ControllerApiSystemUsers;
use Typemill\Controllers\ControllerApiSystemVersions;
use Typemill\Controllers\ControllerApiImage;
use Typemill\Controllers\ControllerApiFile;
use Typemill\Controllers\ControllerApiAuthorArticle;
use Typemill\Controllers\ControllerApiAuthorBlock;
use Typemill\Controllers\ControllerApiAuthorMeta;
use Typemill\Controllers\ControllerApiAuthorShortcode;
use Typemill\Controllers\ControllerApiTestmail;

$app->group('/api/v1', function (RouteCollectorProxy $group) use ($acl) {

	# GLOBALS
	$group->get('/systemnavi', ControllerApiGlobals::class . ':getSystemnavi')->setName('api.systemnavi.get')->add(new ApiAuthorization($acl, 'account', 'view')); # member
	$group->get('/mainnavi', ControllerApiGlobals::class . ':getMainnavi')->setName('api.mainnavi.get')->add(new ApiAuthorization($acl, 'account', 'view')); # member

	# SYSTEM
	$group->get('/settings', ControllerApiSystemSettings::class . ':getSettings')->setName('api.settings.get')->add(new ApiAuthorization($acl, 'system', 'view')); # admin
	$group->post('/settings', ControllerApiSystemSettings::class . ':updateSettings')->setName('api.settings.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/license', ControllerApiSystemLicense::class . ':createLicense')->setName('api.license.create')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/themecss', ControllerApiSystemThemes::class . ':updateThemeCss')->setName('api.themecss.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/theme', ControllerApiSystemThemes::class . ':updateTheme')->setName('api.theme.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/plugin', ControllerApiSystemPlugins::class . ':updatePlugin')->setName('api.plugin.set')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/extensions', ControllerApiSystemExtensions::class . ':activateExtension')->setName('api.extension.activate')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/versioncheck', ControllerApiSystemVersions::class . ':checkVersions')->setName('api.versioncheck')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->post('/testmail', ControllerApiTestmail::class . ':send')->setName('api.testmail')->add(new ApiAuthorization($acl, 'system', 'update')); # admin
	$group->get('/users/getbynames', ControllerApiSystemUsers::class . ':getUsersByNames')->setName('api.usersbynames')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/users/getbyemail', ControllerApiSystemUsers::class . ':getUsersByEmail')->setName('api.usersbyemail')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/users/getbyrole', ControllerApiSystemUsers::class . ':getUsersByRole')->setName('api.usersbyrole')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->get('/userform', ControllerApiSystemUsers::class . ':getNewUserForm')->setName('api.user.form')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->post('/user', ControllerApiSystemUsers::class . ':createUser')->setName('api.user.create')->add(new ApiAuthorization($acl, 'user', 'update')); # admin
	$group->put('/user', ControllerApiSystemUsers::class . ':updateUser')->setName('api.user.update')->add(new ApiAuthorization($acl, 'account', 'update')); # member
	$group->delete('/user', ControllerApiSystemUsers::class . ':deleteUser')->setName('api.user.delete')->add(new ApiAuthorization($acl, 'account', 'delete')); # member

	# IMAGES
	$group->get('/pagemedia', ControllerApiImage::class . ':getPagemedia')->setName('api.image.pagemedia')->add(new ApiAuthorization($acl, 'mycontent', 'read'));
	$group->get('/images', ControllerApiImage::class . ':getImages')->setName('api.image.images')->add(new ApiAuthorization($acl, 'mycontent', 'read'));
	$group->post('/image', ControllerApiImage::class . ':saveImage')->setName('api.image.create')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->put('/image', ControllerApiImage::class . ':publishImage')->setName('api.image.publish')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->get('/image', ControllerApiImage::class . ':getImage')->setName('api.image.get')->add(new ApiAuthorization($acl, 'mycontent', 'read'));
	$group->delete('/image', ControllerApiImage::class . ':deleteImage')->setName('api.image.delete')->add(new ApiAuthorization($acl, 'mycontent', 'delete'));
	
	# FILES
	$group->get('/filerestrictions', ControllerApiFile::class . ':getFileRestrictions')->setName('api.file.getrestrictions')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->post('/filerestrictions', ControllerApiFile::class . ':updateFileRestrictions')->setName('api.file.updaterestrictions')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->post('/file', ControllerApiFile::class . ':uploadFile')->setName('api.file.upload')->add(new ApiAuthorization($acl, 'mycontent', 'create'));
	$group->put('/file', ControllerApiFile::class . ':publishFile')->setName('api.file.publish')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->get('/files', ControllerApiFile::class . ':getFiles')->setName('api.files.get')->add(new ApiAuthorization($acl, 'mycontent', 'read'));
	$group->get('/file', ControllerApiFile::class . ':getFile')->setName('api.file.get')->add(new ApiAuthorization($acl, 'mycontent', 'read'));
	$group->delete('/file', ControllerApiFile::class . ':deleteFile')->setName('api.file.delete')->add(new ApiAuthorization($acl, 'mycontent', 'read'));

	# ARTICLE
	$group->post('/article/sort', ControllerApiAuthorArticle::class . ':sortArticle')->setName('api.article.sort')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->post('/article/rename', ControllerApiAuthorArticle::class . ':renameArticle')->setName('api.article.rename')->add(new ApiAuthorization($acl, 'content', 'publish'));
	$group->post('/article/publish', ControllerApiAuthorArticle::class . ':publishArticle')->setName('api.article.publish')->add(new ApiAuthorization($acl, 'content', 'publish'));
	$group->delete('/article/unpublish', ControllerApiAuthorArticle::class . ':unpublishArticle')->setName('api.article.unpublish')->add(new ApiAuthorization($acl, 'content', 'unpublish'));
	$group->delete('/article/discard', ControllerApiAuthorArticle::class . ':discardArticleChanges')->setName('api.article.discard')->add(new ApiAuthorization($acl, 'content', 'update'));
	$group->delete('/article', ControllerApiAuthorArticle::class . ':deleteArticle')->setName('api.article.delete')->add(new ApiAuthorization($acl, 'content', 'delete'));
	$group->post('/article', ControllerApiAuthorArticle::class . ':createArticle')->setName('api.article.create')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->put('/draft', ControllerApiAuthorArticle::class . ':updateDraft')->setName('api.draft.update')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->post('/draft/publish', ControllerApiAuthorArticle::class . ':publishDraft')->setName('api.draft.publish')->add(new ApiAuthorization($acl, 'content', 'create')); # author
	$group->post('/post', ControllerApiAuthorArticle::class . ':createPost')->setName('api.post.create')->add(new ApiAuthorization($acl, 'content', 'create'));

	# BLOCKS
	$group->post('/block', ControllerApiAuthorBlock::class . ':addBlock')->setName('api.block.add')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->put('/block/move', ControllerApiAuthorBlock::class . ':moveBlock')->setName('api.block.move')->add(new ApiAuthorization($acl, 'mycontent', 'view'));
	$group->put('/block', ControllerApiAuthorBlock::class . ':updateBlock')->setName('api.block.update')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->delete('/block', ControllerApiAuthorBlock::class . ':deleteBlock')->setName('api.block.delete')->add(new ApiAuthorization($acl, 'mycontent', 'update'));
	$group->post('/video', ControllerApiImage::class . ':saveVideoImage')->setName('api.video.save')->add(new ApiAuthorization($acl, 'mycontent', 'view'));

	# SHORTCODE
	$group->get('/shortcodedata', ControllerApiAuthorShortcode::class . ':getShortcodeData')->setName('api.shortcodedata.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));

	# META
	$group->get('/meta', ControllerApiAuthorMeta::class . ':getMeta')->setName('api.meta.get')->add(new ApiAuthorization($acl, 'mycontent', 'view'));
	$group->post('/meta', ControllerApiAuthorMeta::class . ':updateMeta')->setName('api.metadata.update')->add(new ApiAuthorization($acl, 'mycontent', 'update'));

})->add(new CorsHeadersMiddleware($settings, $urlinfo))->add(new ApiAuthentication());

# api-routes from plugins
if(isset($routes['api']) && !empty($routes['api']))
{
	foreach($routes['api'] as $pluginRoute)
	{
		$method 	= $pluginRoute['httpMethod'] ?? false;
		$route		= $pluginRoute['route'] ?? false;
		$class		= $pluginRoute['class'] ?? false;
		$name 		= $pluginRoute['name'] ?? false;
		$resource 	= $pluginRoute['resource'] ?? false;
		$privilege 	= $pluginRoute['privilege'] ?? false;

		if($resources && $privilege)
		{
			# protected api requires authentication and authorization
			$app->{$method}($route, $class)->setName($name)->add(new ApiAuthorization($acl, $resource, $privilege))->add(new CorsHeadersMiddleware($settings, $urlinfo))->add(new ApiAuthentication());
		}
		else
		{
			# public api routes
			$app->{$method}($route, $class)->setName($name)->add(new CorsHeadersMiddleware($settings, $urlinfo));
		}
	}
}