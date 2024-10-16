<?php

namespace Typemill\Extensions;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaExtension implements EventSubscriberInterface
{
    private $rootpath;

    private $baseurl;

    function __construct($rootpath, $baseurl)
    {
        $this->rootpath = $rootpath;
        $this->baseurl = $baseurl;
    }

    public static function getSubscribedEvents()
    {
        return [
            'onShortcodeFound'          => 'onShortcodeFound',
        ];
    }

    public function onShortcodeFound($shortcode)
    {
        $shortcodeArray = $shortcode->getData();

        if(is_array($shortcodeArray) && $shortcodeArray['name'] == 'video' && isset($shortcodeArray['params']['path']))
        {
            $relUrl = $shortcodeArray['params']['path'];
            $relUrl = '/' . trim($relUrl, '/');

            # Convert the relative URL to an absolute file path
            $filePath = $this->rootpath . $relUrl;

            # check file exists 
            if(!file_exists($filePath))
            {
                $html = '<p style="color:red">File not found</p>';
            }
            else
            {
                # Get file extension using pathinfo()
                $fileInfo = pathinfo($filePath);
                $extension = strtolower($fileInfo['extension']);  // Get file extension and convert to lowercase
                $absUrl = $this->baseurl . $relUrl;
                
                # Determine the correct file type for the video tag
                $type = '';
                switch ($extension) {
                    case 'mp4':
                        $type = 'mp4';
                        break;
                    case 'webm':
                        $type = 'webm';
                        break;
                    case 'ogg':
                        $type = 'ogg';
                        break;
                    default:
                        $html = '<p style="color:red">Unsupported file type</p>';
                        return;  // Exit if file type is not supported
                }

                $width  = $shortcodeArray['params']['width'] ?? '500';
                if (!preg_match('/^(\d+)(px|%)?$/', $width))
                {
                    $width = '500';
                }

                $preload  = 'none';
                if(isset($shortcodeArray['params']['preload']) && ($shortcodeArray['params']['preload'] == 'auto' or $shortcodeArray['params']['preload'] == 'metadata'))
                {
                   $preload = $shortcodeArray['params']['preload'];
                }

                $poster = '';

                if(isset($shortcodeArray['params']['poster']))
                {
                    $relImgUrl = $shortcodeArray['params']['poster'];
                    $relImgUrl = '/' . trim($relImgUrl, '/');
                 
                    # Convert the relative URL to an absolute file path
                    $imgPath = $this->rootpath . $relImgUrl;

                    # check file exists 
                    if(file_exists($imgPath))
                    {
                        $absImgUrl = $this->baseurl . $relImgUrl;
                        $poster = ' poster="' . $absImgUrl . '"';
                    }
                }

                $html = '<video 
                            controls 
                            width = "' . $width . '" 
                            preload = "' . $preload . '"
                            ' . $poster . '
                            class = "center"
                        >
                          <source src="' . $absUrl . '" type="video/' . $type . '" />
                          Download the
                          <a href="' . $absUrl . '">' . $type . '</a>
                          video.
                        </video>';
            }

            $shortcode->setData($html);
        }
    }
}