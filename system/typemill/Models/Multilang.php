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


# DELETE (part of navigation model now)
    public function checkMultilangSettings($settings): bool
    {    	
        if (
            empty($settings['multilang']) ||
            empty($settings['baselangcode']) ||
            empty($settings['baselanglabel']) ||
            empty($settings['multilanguages']) ||
            !is_array($settings['multilanguages'])
        ) {
            return false;
        }
        return true;
    }

	public function addMultilangDefinitions($metadefinitions, $settings)
	{
		if ($this->checkMultilangSettings($settings))
		{
			$fields = [];

			// Add base language first
			$fields[$settings['baselangcode']] = [
				'type' => 'text',
				'label' => 'URL: ' . $settings['baselanglabel'] . ' (Base Language)',
				'maxlength' => 60,
				'description' => 'Url to the base language ' . $settings['baselanglabel'] . ' (read only, change the slug in the meta tab)',
				'disabled' => 'disabled'
			];

			// Add all other languages
			foreach ($settings['multilanguages'] as $languagecode => $languagelabel) {
				// Skip base language if it was accidentally added to multilanguages
				if ($languagecode === $settings['baselangcode']) {
					continue;
				}
				$fields[$languagecode] = [
					'type' => 'text',
					'label' => 'URL: ' . $languagelabel,
					'maxlength' => 60,
					'description' => 'Add the url to the ' . $languagelabel . ' version'
				];
			}

			$metadefinitions['lang']['fields'] = $fields;
		}

		return $metadefinitions;
	}

    public function getMultilangDefinitions($settings, $pageId, $multilangIndex)
    {
        $fields = [];

        $baseLang       = $settings['baselangcode'];
        $baseLabel      = $settings['baselanglabel'];

        $fields[$baseLang] = [
            'type'        => 'text',
            'label'       => 'Slug: ' . $baseLabel . ' (Base Language)',
            'maxlength'   => 60,
            'disabled'    => false
        ];

        foreach ($settings['multilanguages'] as $languagecode => $languagelabel) {
            if ($languagecode === $baseLang) {
                continue;
            }
            $fields[$languagecode] = [
                'type'        => 'text',
                'label'       => 'Slug: ' . $languagelabel,
                'maxlength'   => 60,
            ];
        }

        return ['fields' => $fields];
    }

# NOW IN NAVIGATION
	public function getLanguagesFromSettings($settings)
	{
		return array_keys($settings['multilanguages']);
	}

# NOW PART OF NAVIGATION
	public function getLangFromUrl($settings, $url)
	{
		$lang 				= null;
		if($this->checkMultilangSettings($settings))
		{
			$languages = $this->getLanguagesFromSettings($settings);
			$segments = explode('/', trim($url, '/'));
			$firstSegment = $segments[0] ?? null;
			if ($firstSegment && in_array($firstSegment, $languages))
			{
					$lang = $firstSegment;
			}
		}

		return $lang;
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

	public function generateMultilangIndex($meta, $draftNavi, $settings, $parentId = null, &$multilangIndex = [])
	{
		if(!$meta)
		{
		    $meta = new Meta();
		}

	    foreach ($draftNavi as $item)
	    {
	        // Load or create metadata
	        $metadata = $meta->getMetaData($item);
	        if (!isset($metadata['pageid']))
	        {
	            $metadata = $meta->addMetaDefaults($metadata, $item, false, false);
	        }
	        $pageId = $metadata['meta']['pageid'];

	        // Initialize entry for this page
	        $pageIndex = [];
	        $pageIndex[$settings['baselangcode']] = $item->slug;

	        foreach ($settings['multilanguages'] as $langcode => $langlabel)
	        {
	            // Skip base language if someone added it to multilanguages by mistake
	            if ($langcode === $settings['baselangcode'])
	            {
	                continue;
	            }
	            $pageIndex[$langcode] = false;
	        }

	        $pageIndex['parent'] = $parentId;

	        // Store in index
	        $multilangIndex[$pageId] = $pageIndex;

	        // Recurse into children if available
	        if (!empty($item->folderContent))
	        {
	            $this->generateMultilangIndex($meta, $item->folderContent, $settings, $pageId, $multilangIndex);
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

	public function getMultilangData($pageid, $multilangIndex)
	{
	    if (!isset($multilangIndex[$pageid]))
	    {
	        return null;
	    }

	    $entry = $multilangIndex[$pageid];
	    $entry['path'] = [];

	    foreach ($multilangIndex[$pageid] as $lang => $slug)
	    {
	        if ($lang === 'parent')
	        {
	            continue;
	        }

	        $segments = $this->getParentSegments($pageid, $lang, $multilangIndex);
	        $entry['path'][$lang] = $segments; // <-- array with slugs or false
	    }

	    return $entry;
	}

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