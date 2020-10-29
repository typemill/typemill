<?php

namespace Plugins\Adamhall;

use Typemill\Plugin;

class Adamhall extends Plugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'onTwigLoaded'      => 'onTwigLoaded',
            'onMetaLoaded'      => 'onMetaLoaded'
        );
    }

    public function onTwigLoaded()
    {
        $this->addEditorJS('/adamhall/js/adamhall.js');
    }

    public function onMetaLoaded($meta)
    {
        $meta = $meta->getData();

        # do something with the fields:
        $myTabInformation = $meta['mytab'];
    }
}