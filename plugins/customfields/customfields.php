<?php

namespace Plugins\Customfields;

use Typemill\Plugin;

class Customfields extends Plugin
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
        $this->addEditorJS('/customfields/js/customfields.js');
    }

    public function onMetaLoaded($meta)
    {
        $meta = $meta->getData();

        # do something with the fields:
        $myTabInformation = $meta['mytab'];
    }
}