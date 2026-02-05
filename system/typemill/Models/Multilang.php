<?php

namespace Typemill\Models;

use Typemill\Models\StorageWrapper;

class Multilang
{
	private $storageLocation = '\Typemill\Models\Storage';

	private $storage;

	private $langFolder;

	private $DS;

	public function __construct($storageLocation = null)
	{
		if($storageLocation)
		{
			$this->storageLocation = $storageLocation;
		}

		$this->storage 				= new StorageWrapper($this->storageLocation);

		$this->langFolder 			= 'multilang';

		$this->DS 					= DIRECTORY_SEPARATOR;
	}

	public function addMultilangDefinitions($metadefinitions, $settings, $project)
	{		
		$fields = [];

		# Add base language first
		$fields[$settings['baseprojectid']] = [
			'type' 			=> 'text',
			'label' 		=> 'URL: ' . $settings['baseprojectlabel'] . ' (Base Language)',
			'maxlength' 	=> 60,
			'description' 	=> 'Url to the base language ' . $settings['baseprojectlabel'] . ' (read only, change the slug in the meta tab)',
			'disabled' 		=> 'disabled',
			'active' 		=> ($project == $settings['baseprojectid']) ? true : false,
			'base'			=> true
		];

		# Add all other languages
		foreach ($settings['projectinstances'] as $languagecode => $languagelabel)
		{
			# Skip base language if it was accidentally added to multilanguages
			if ($languagecode === $settings['baseprojectid'])
			{
				continue;
			}
			$fields[$languagecode] = [
				'type' 			=> 'text',
				'label' 		=> 'URL: ' . $languagelabel,
				'maxlength' 	=> 60,
				'description' 	=> 'Add the url to the ' . $languagelabel . ' version',
				'active' 		=> ($project == $languagecode) ? true : false,
				'base'			=> false
			];
		}

		$metadefinitions['lang']['fields'] = $fields;

		return $metadefinitions;
	}

    public function getMultilangDefinitions($settings, $pageId, $multilangIndex)
    {
        $fields = [];

        $baseLang       = $settings['baseprojectid'];
        $baseLabel      = $settings['baseprojectlabel'];


        $fields[$baseLang] = [
            'type'     		=> 'text',
            'label'   		=> 'url: ' . $baseLabel . ' (Base Language)',
            'maxlength'		=> 60,
            'disabled'		=> true,
            'base'			=> true
        ];

        foreach ($settings['projectinstances'] as $languagecode => $languagelabel)
        {
            if ($languagecode === $baseLang)
            {
                continue;
            }
            $fields[$languagecode] = [
                'type'        => 'text',
                'label'       => 'url: ' . $languagelabel,
                'maxlength'   => 60,
            ];
        }

        return ['fields' => $fields];
    }

	public function getMultilangData($pageid, $multilangIndex)
	{
	    if (!isset($multilangIndex[$pageid]))
	    {
	        return null;
	    }

	    return $multilangIndex[$pageid];
	}

	public function deleteMultilangIndex()
	{
		$multilangIndex = $this->storage->deleteFile('dataFolder', $this->langFolder, 'index.txt');

		return $multilangIndex;
	}

	public function getMultilangIndex()
	{
		$multilangIndex = $this->storage->getFile('dataFolder', $this->langFolder, 'index.txt', 'unserialize');

		if($multilangIndex)
		{
			return $multilangIndex;
		}

		return false;
	}

	# generate the baseic index with the basic navigation
	public function generateMultilangBaseIndex($meta, $draftNavi, $settings, $parentId = null, &$multilangIndex = [])
	{
		if(!$meta)
		{
		    $meta = new Meta();
		}

	    foreach ($draftNavi as $item)
	    {
	        // Load or create metadata
	        $metadata = $meta->getMetaData($item);
	        if (!isset($metadata['meta']['pageid']))
	        {
	            $metadata = $meta->addMetaDefaults($metadata, $item, false, false);
	            if(!isset($metadata['meta']['pageid']))
	            {
	            	continue;
	            }
	        }

	        $pageId = $metadata['meta']['pageid'];

	        // Initialize entry for this page
	        $pageIndex = [];
	        $pageIndex[$settings['baseprojectid']] = $item->urlRelWoF;

	        foreach ($settings['projectinstances'] as $langcode => $langlabel)
	        {
	            // Skip base language if someone added it to multilanguages by mistake
	            if ($langcode === $settings['baseprojectid'])
	            {
	                continue;
	            }
	            $pageIndex[$langcode] = '';
	        }

	        $pageIndex['parent'] = $parentId;

	        // Store in index
	        $multilangIndex[$pageId] = $pageIndex;

	        // Recurse into children if available
	        if (!empty($item->folderContent))
	        {
	            $this->generateMultilangBaseIndex($meta, $item->folderContent, $settings, $pageId, $multilangIndex);
	        }
	    }

	    return $multilangIndex;
	}

	# add a single project with the project navigation to the base index
	public function addProjectToIndex($lang, $meta, $draftNavi, &$multilangIndex = [])
	{
		if(!$meta)
		{
		    $meta = new Meta();
		}

		if($draftNavi)
		{
		    foreach ($draftNavi as $item)
		    {
		        // Load or create metadata
		        $metadata = $meta->getMetaData($item);
		        if (!isset($metadata['meta']['translation_for']))
		        {
		            continue;
		        }

		        $pageId = $metadata['meta']['translation_for'];

		        if(isset($multilangIndex[$pageId]))
		        {
		        	$multilangIndex[$pageId][$lang] = $item->urlRelWoF;
		        }

		        // Recurse into children if available
		        if (!empty($item->folderContent))
		        {
		            $this->addProjectToIndex($lang, $meta, $item->folderContent, $multilangIndex);
		        }
		    }
		}

	    return $multilangIndex;
	}

	public function storeMultilangIndex($multilangIndex)
	{
	    return $this->storage->writeFile('dataFolder', $this->langFolder, 'index.txt', $multilangIndex, 'serialize');
	}

	public function updateMultilangIndex($pageid, $data)
	{
	    $multilangIndex = $this->getMultilangIndex();

	    $multilangIndex[$pageid] = $data;

	    return $this->storage->writeFile('dataFolder', $this->langFolder, 'index.txt', $multilangIndex, 'serialize');
	}

	# NOT IN USE
	protected function getParentSegments($pageId, $lang, $multilangIndex)
	{
	    if (!isset($multilangIndex[$pageId]))
	    {
	        return [];
	    }

	    $entry      = $multilangIndex[$pageId];
	    $parentId   = $entry['parent'] ?? null;

	    $segments = [];

	    if ($parentId)
	    {
	        $segments = $this->getParentSegments($parentId, $lang, $multilangIndex);
	    }

	    // return false if slug missing for this language
	    $slug = $entry[$lang] ?: false;
	    $segments[] = $slug;

	    return $segments;
	}
}