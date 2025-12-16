<?php

namespace Typemill\Extensions;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Typemill\Models\Meta;
use Typemill\Models\StorageWrapper;

class MultilangExtension implements EventSubscriberInterface
{
    private $storageSetting;

    function __construct($storageSetting)
    {
        $this->storageSetting = $storageSetting;
    }

    public static function getSubscribedEvents()
    {
        # another very simple option: delete the index file
        return [
            'onPageDeleted'     => 'onPageDeleted',
            'onPageRenamed'     => 'onPageRenamed',
            'onPageSorted'      => 'onPageSorted',
        ];
    }

    public function onPageDeleted($data)
    {
        # make it short for now...
        $this->deleteIndex();
    }

    public function onPageRenamed($data)
    {
        # make it short for now...
        $this->deleteIndex();
    }


    public function onPageSorted($data)
    {
        $pagedata = $data->getData();  // Assume $pagedata is an object

        $parent_id_from  = $pagedata['parent_id_from'] ?? false;
        $parent_id_to  = $pagedata['parent_id_to'] ?? false;

        # only if moved to another folder and url has changed
        if($parent_id_from && $parent_id_to && ($parent_id_from != $parent_id_to))
        {
            # make it short for now...
            $this->deleteIndex();
        }
    }

    private function deleteIndex()
    {
        $storage = new StorageWrapper($this->storageSetting);

        if($storage->deleteFile('dataFolder', 'multilang', 'index.txt'))
        {
            return true;
        }

        return false;        
    }
}