<?php

namespace Typemill\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use Typemill\Models\Folder;
use Typemill\Models\Write;
use Typemill\Models\WriteYaml;
use Typemill\Models\ProcessImage;
use Typemill\Models\ProcessFile;
use Typemill\Extensions\ParsedownExtension;
use \URLify;

class BlockApiController extends ContentController
{

	public function addBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
		}

		/* validate input */
		if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		$this->setHomepage($args = false);

		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to edit content.']), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;

		$blockMarkdown = $this->params['markdown'];

        # standardize line breaks
        $blockMarkdown = str_replace(array("\r\n", "\r"), "\n", $blockMarkdown);

        # remove surrounding line breaks
        $blockMarkdown = trim($blockMarkdown, "\n");		
		
		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		# if it is a new content-block
		if($this->params['block_id'] == 99999)
		{
			# set the id of the markdown-block (it will be one more than the actual array, so count is perfect) 
			$id = count($pageMarkdown);

			# add the new markdown block to the page content
			$pageMarkdown[] = $blockMarkdown;			
		}
		elseif(($this->params['block_id'] == 0) OR !isset($pageMarkdown[$this->params['block_id']]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'The ID of the content-block is wrong.']), 404);
		}
		else
		{
			# insert new markdown block
			array_splice( $pageMarkdown, $this->params['block_id'], 0, $blockMarkdown );			
			$id = $this->params['block_id'];
		}
	
		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
	
		/* set safe mode to escape javascript and html in markdown */
		$parsedown->setSafeMode(true);

		/* parse markdown-file to content-array */
		$blockArray = $parsedown->text($blockMarkdown);
		
		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		if($blockMarkdown == '[TOC]')
		{
			# if block is table of content itself, then generate the table of content
			$tableofcontent = $this->generateToc();

			# and only use the html-markup
			$blockHTML = $tableofcontent['html'];
		}
		else
		{
			# parse markdown-content-array to content-string
			$blockHTML = $parsedown->markup($blockArray);
			
			# if it is a headline
			if($blockMarkdown[0] == '#')
			{
				# then the TOC holds either false (if no toc used in the page) or it holds an object with the id and toc-markup
				$toc = $this->generateToc();
			}
		}

		return $response->withJson(array('content' => [ 'id' => $id, 'html' => $blockHTML ] , 'markdown' => $blockMarkdown, 'id' => $id, 'toc' => $toc, 'errors' => false));
	}

	protected function generateToc()
	{
		# we assume that page has no table of content
		$toc = false;

		# make sure $this->content is updated
		$content = $this->content;

		if($content == '')
		{
			$content = [];
		}
		
		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl());
		
		# if content is not an array, then transform it
		if(!is_array($content))
		{
			# turn markdown into an array of markdown-blocks
			$content = $parsedown->markdownToArrayBlocks($content);
		}
		
		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		# loop through mardkown-array and create html-blocks
		foreach($content as $key => $block)
		{
			# parse markdown-file to content-array
			$contentArray 	= $parsedown->text($block);
			
			if($block == '[TOC]')
			{
				# toc is true and holds the key of the table of content now
				$toc = $key;
			}

			# parse markdown-content-array to content-string
			$content[$key]	= ['id' => $key, 'html' => $parsedown->markup($contentArray)];
		}

		# if page has a table of content
		if($toc)
		{
			# generate the toc markup
			$tocMarkup = $parsedown->buildTOC($parsedown->headlines);

			# toc holds the id of the table of content and the html-markup now
			$toc = ['id' => $toc, 'html' => $tocMarkup];
		}

		return $toc;
	}

	public function updateBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
		}

		/* validate input */
		if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		$this->setHomepage($args = false);

		/* set item */
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to edit content.']), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;

		$blockMarkdown = $this->params['markdown'];

        # standardize line breaks
        $blockMarkdown = str_replace(array("\r\n", "\r"), "\n", $blockMarkdown);

        # remove surrounding line breaks
        $blockMarkdown = trim($blockMarkdown, "\n");

		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl());
		$parsedown->setVisualMode();

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		if(!isset($pageMarkdown[$this->params['block_id']]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'The ID of the content-block is wrong.']), 404);
		}
		elseif($this->params['block_id'] == 0)
		{
			# if it is the title, then delete the "# " if it exists
			$blockMarkdown = trim($blockMarkdown, "# ");
			
			# store the markdown-headline in a separate variable
			$blockMarkdownTitle = '# ' . $blockMarkdown;
			
			# add the markdown-headline to the page-markdown
			$pageMarkdown[0] = $blockMarkdownTitle;
			$id = 0;
		}
		else
		{
			# update the markdown block in the page content
			$pageMarkdown[$this->params['block_id']] = $blockMarkdown;
			$id = $this->params['block_id'];
		}

		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);

			# updated the content variable
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}

		/* parse markdown-file to content-array, if title parse title. */
		if($this->params['block_id'] == 0)
		{
			$blockArray		= $parsedown->text($blockMarkdownTitle);
		}
		else
		{
			/* set safe mode to escape javascript and html in markdown */
			$parsedown->setSafeMode(true);

			$blockArray 	= $parsedown->text($blockMarkdown);
		}
		
		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;
		
		if($blockMarkdown == '[TOC]')
		{
			# if block is table of content itself, then generate the table of content
			$tableofcontent = $this->generateToc();

			# and only use the html-markup
			$blockHTML = $tableofcontent['html'];
		}
		else
		{
			# parse markdown-content-array to content-string
			$blockHTML = $parsedown->markup($blockArray);
			
			# if it is a headline
			if($blockMarkdown[0] == '#')
			{
				# then the TOC holds either false (if no toc used in the page) or it holds an object with the id and toc-markup
				$toc = $this->generateToc();
			}
		}

		return $response->withJson(array('content' => [ 'id' => $id, 'html' => $blockHTML ] , 'markdown' => $blockMarkdown, 'id' => $id, 'toc' => $toc, 'errors' => false));
	}
	
	public function moveBlock(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to publish content.']), 403);
		}

		# validate input 
		# if(!$this->validateBlockInput()){ return $response->withJson($this->errors,422); }
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		$this->setHomepage($args = false);

		# set item 
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to delete content.']), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# make it more clear which content we have
		$pageMarkdown = $this->content;
		
		if($pageMarkdown == '')
		{
			$pageMarkdown = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

		# if content is not an array, then transform it
		if(!is_array($pageMarkdown))
		{
			# turn markdown into an array of markdown-blocks
			$pageMarkdown = $parsedown->markdownToArrayBlocks($pageMarkdown);
		}

		$oldIndex = ($this->params['old_index'] + 1);
		$newIndex = ($this->params['new_index'] + 1);
		
		if(!isset($pageMarkdown[$oldIndex]))
		{
			# if the block does not exists, return an error
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'The ID of the content-block is wrong.']), 404);
		}

		$extract = array_splice($pageMarkdown, $oldIndex, 1);
		array_splice($pageMarkdown, $newIndex, 0, $extract);
	
		# encode the content into json
		$pageJson = json_encode($pageMarkdown);

		# set path for the file (or folder)
		$this->setItemPath('txt');
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);

			# update this content
			$this->content = $pageMarkdown;
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}

		# we assume that toc is not relevant
		$toc = false;

		# needed for ToC links
		$relurl = '/tm/content/' . $this->settings['editor'] . '/' . $this->item->urlRel;

		# if the moved item is a headline
		if($extract[0][0] == '#')
		{
			$toc = $this->generateToc();
		}

		# if it is the title, then delete the "# " if it exists
		$pageMarkdown[0] = trim($pageMarkdown[0], "# ");

		return $response->withJson(array('markdown' => $pageMarkdown, 'toc' => $toc, 'errors' => false));
	}

	public function deleteBlock(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		$errors			= false;

		# minimum permission is that user is allowed to update his own content
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'mycontent', 'update'))
		{
			return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to delete this content.']), 403);
		}
		
		# set structure
		if(!$this->setStructure($draft = true)){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }
		
		$this->setHomepage($args = false);
		
		# set item
		if(!$this->setItem()){ return $response->withJson($this->errors, 404); }

		# if user has no right to delete content from others (eg admin or editor)
		if(!$this->c->acl->isAllowed($_SESSION['role'], 'content', 'update'))
		{
			# check ownership. This code should nearly never run, because there is no button/interface to trigger it.
			if(!$this->checkContentOwnership())
			{
				return $response->withJson(array('data' => false, 'errors' => ['message' => 'You are not allowed to delete this content.']), 403);
			}
		}

		# set the status for published and drafted
		$this->setPublishStatus();

		# set path
		$this->setItemPath($this->item->fileType);

		# read content from file
		if(!$this->setContent()){ return $response->withJson(array('data' => false, 'errors' => $this->errors), 404); }

		# get content
		$this->content;

		if($this->content == '')
		{
			$this->content = [];
		}

		# initialize parsedown extension
		$parsedown = new ParsedownExtension($this->uri->getBaseUrl());

		# if content is not an array, then transform it
		if(!is_array($this->content))
		{
			# turn markdown into an array of markdown-blocks
			$this->content = $parsedown->markdownToArrayBlocks($this->content);
		}

		# check if id exists
		if(!isset($this->content[$this->params['block_id']])){ return $response->withJson(array('data' => false, 'errors' => 'The ID of the content-block is wrong.'), 404); }

		$contentBlock = $this->content[$this->params['block_id']];

		# delete the block
		unset($this->content[$this->params['block_id']]);
		$this->content = array_values($this->content);

		$pageMarkdown = $this->content;
		
		# delete markdown from title
		if(isset($pageMarkdown[0]))
		{
			$pageMarkdown[0] = trim($pageMarkdown[0], "# ");
		}
		
		# encode the content into json
		$pageJson = json_encode($this->content);

		# set path for the file (or folder)
		$this->setItemPath('txt');		
	
		/* update the file */
		if($this->write->writeFile($this->settings['contentFolder'], $this->path, $pageJson))
		{
			# update the internal structure
			$this->setStructure($draft = true, $cache = false);
		}
		else
		{
			return $response->withJson(['errors' => ['message' => 'Could not write to file. Please check if the file is writable']], 404);
		}
		
		$toc = false;

		if($contentBlock[0] == '#')
		{
			$toc = $this->generateToc();
		}

		return $response->withJson(array('markdown' => $pageMarkdown, 'toc' => $toc, 'errors' => $errors));
	}

	public function getMediaLibImages(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$imagelist 		= $imageProcessor->scanMediaFlat();

		return $response->withJson(array('images' => $imagelist));
	}

	public function getMediaLibFiles(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$filelist 		= $fileProcessor->scanFilesFlat();

		return $response->withJson(array('files' => $filelist));
	}

	public function getImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$this->setStructure($draft = true, $cache = false);

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$imageDetails 	= $imageProcessor->getImageDetails($this->params['name'], $this->structure);

		if($imageDetails)
		{
			return $response->withJson(array('image' => $imageDetails));
		}
		
		#	return $response->withJson(array('image' => false, 'errors' => 'image name invalid or not found'));
		return $response->withJson(['errors' => ['message' => 'Image name invalid or not found.']], 404);
	}

	public function getFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$this->setStructure($draft = true, $cache = false);

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$fileDetails 	= $fileProcessor->getFileDetails($this->params['name'], $this->structure);

		if($fileDetails)
		{
			return $response->withJson(['file' => $fileDetails]);
		}

		return $response->withJson(['errors' => ['message' => 'file name invalid or not found']],404);
	}

	public function createImage(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		
		# do this shit in the model ...
		$imagename = explode('.', $this->params['name']);
		array_pop($imagename);
		$imagename = implode('-', $imagename);
		$name = URLify::filter(iconv(mb_detect_encoding($imagename, mb_detect_order(), true), "UTF-8", $imagename));

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}
		
		if($imageProcessor->createImage($this->params['image'], $name, $this->settings['images']))
		{
			return $response->withJson(array('errors' => false));	
		}

		return $response->withJson(array('errors' => ['message' => 'could not store image to temporary folder']));	
	}

	public function createFile(Request $request, Response $response, $args)
	{
		# get params from call
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mtype = finfo_file( $finfo, $this->params['file'] );
		finfo_close( $finfo );

		$allowedMimes = $this->getAllowedMtypes();
		if(!in_array($mtype, $allowedMimes))
		{
			return $response->withJson(array('errors' => ['message' => 'File-type is not allowed']));
		}

		# sanitize file name
		$filename = basename($this->params['name']);
		$filename = explode('.', $this->params['name']);
		array_pop($filename);
		$filename = implode('-', $filename);
		$name = URLify::filter(iconv(mb_detect_encoding($filename, mb_detect_order(), true), "UTF-8", $filename));

		$fileProcessor	= new ProcessFile();
		if(!$fileProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}
		
		if($fileProcessor->createFile($this->params['file'], $name))
		{
			return $response->withJson(array('errors' => false, 'name' => $name));
		}

		return $response->withJson(array('errors' => ['message' => 'could not store file to temporary folder']));
	}
	
	public function publishImage(Request $request, Response $response, $args)
	{
		$params 		= $request->getParsedBody();

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}
		
		$imageUrl 		= $imageProcessor->publishImage();
		if($imageUrl)
		{
			# replace the image placeholder in markdown with the image url
			$params['markdown']	= str_replace('imgplchldr', $imageUrl, $params['markdown']);
						
			$request 	= $request->withParsedBody($params);
		
			if($params['new'])
			{
				return $this->addBlock($request, $response, $args);
			}
			return $this->updateBlock($request, $response, $args);
		}

		return $response->withJson(array('errors' => ['message' => 'could not store image to media folder']));	
	}

	public function deleteImage(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		if(!isset($this->params['name']))
		{
			return $response->withJson(array('errors' => ['message' => 'image name is missing']));	
		}

		$imageProcessor	= new ProcessImage($this->settings['images']);
		if(!$imageProcessor->checkFolders())
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}

		$errors 		= $imageProcessor->deleteImage($this->params['name']);

		return $response->withJson(array('errors' => $errors));
	}

	public function deleteFile(Request $request, Response $response, $args)
	{
		# get params from call 
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');

		if(!isset($this->params['name']))
		{
			return $response->withJson(array('errors' => ['message' => 'file name is missing']));	
		}

		$fileProcessor	= new ProcessFile();

		$errors = false;
		if($fileProcessor->deleteFile($this->params['name']))
		{
			return $response->withJson(array('errors' => false));
		}

		return $response->withJson(array('errors' => ['message' => 'could not delete the file']));
	}

	public function saveVideoImage(Request $request, Response $response, $args)
	{
		/* get params from call */
		$this->params 	= $request->getParams();
		$this->uri 		= $request->getUri()->withUserInfo('');
		$class			= false;

		$imageUrl		= $this->params['markdown'];
		
		if(strpos($imageUrl, 'https://www.youtube.com/watch?v=') !== false)
		{
			$videoID 	= str_replace('https://www.youtube.com/watch?v=', '', $imageUrl);
			$videoID 	= strpos($videoID, '&') ? substr($videoID, 0, strpos($videoID, '&')) : $videoID;
			$class		= 'youtube';
		}
		if(strpos($imageUrl, 'https://youtu.be/') !== false)
		{
			$videoID 	= str_replace('https://youtu.be/', '', $imageUrl);
			$videoID	= strpos($videoID, '?') ? substr($videoID, 0, strpos($videoID, '?')) : $videoID;
			$class		= 'youtube';
		}
		
		if($class == 'youtube')
		{
			$videoURLmaxres = 'https://i1.ytimg.com/vi/' . $videoID . '/maxresdefault.jpg';
			$videoURL0 = 'https://i1.ytimg.com/vi/' . $videoID . '/0.jpg';
		}

		$ctx = stream_context_create(array(
			'https' => array(
				'timeout' => 1
				)
			)
		);
		
		$imageData		= @file_get_contents($videoURLmaxres, 0, $ctx);
		if($imageData === false)
		{
			$imageData	= @file_get_contents($videoURL0, 0, $ctx);
			if($imageData === false)
			{
				return $response->withJson(['errors' => ['message' => 'We did not find that video or could not get a preview image.']], 500);
			}
		}
		
		$imageData64			= 'data:image/jpeg;base64,' . base64_encode($imageData);
		$desiredSizes 			= $this->settings['images'];
		$desiredSizes['live']	= ['width' => 560, 'height' => 315];
		$imageProcessor			= new ProcessImage($desiredSizes);
		
		if(!$imageProcessor->checkFolders('images'))
		{
			return $response->withJson(['errors' => ['message' => 'Please check if your media-folder exists and all folders inside are writable.']], 500);
		}
		
		if(!$imageProcessor->createImage($imageData64, 'youtube-' . $videoID, $desiredSizes, $overwrite = true))
		{
			return $response->withJson(['errors' => ['message' => 'We could not create the image.']], 500);
		}

		$imageUrl 		= $imageProcessor->publishImage();
		if($imageUrl)
		{

			$this->params['markdown'] = '![' . $class . '-video](' . $imageUrl . ' "click to load video"){#' . $videoID. ' .' . $class . '}';

			$request 	= $request->withParsedBody($this->params);
			
			if($this->params['new'])
			{
				return $this->addBlock($request, $response, $args);
			}
			return $this->updateBlock($request, $response, $args);
		}
		
		return $response->withJson(array('errors' => ['message' => 'could not store the preview image']));	
	}

	private function getAllowedMtypes()
	{
		return array(
		   	'application/zip',
		   	'application/gzip',
		   	'application/vnd.rar',
			'application/vnd.visio',
			'application/vnd.ms-excel',
			'application/vnd.ms-powerpoint',
			'application/vnd.ms-word.document.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/vnd.apple.keynote',
			'application/vnd.apple.mpegurl',
			'application/vnd.apple.numbers',
			'application/vnd.apple.pages',
			'application/vnd.amazon.mobi8-ebook',
			'application/epub+zip',
			'application/pdf',
		   	'image/png',
		   	'image/jpeg',
		   	'image/jpg',
		   	'image/gif',
		   	'image/svg+xml',
		   	'font/*',
		   	'audio/mpeg',
		   	'audio/mp4',
		   	'audio/ogg',
		   	'video/mpeg',
		   	'video/mp4',
		   	'video/ogg',
		);
	}	
}