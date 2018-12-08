<?php

namespace Typemill\Models;

use Typemill\Extensions\ParsedownExtension;

class WriteExport extends Write {

    public function updateExport(
            $folderName, $exportFileName, $requestFileName, $data, $baseHTML) 
    {
        $export = '<!DOCTYPE html><head></head><body>' . "\n";
        $export .= $this->generateOneHTML($data);
        $export .= '</html>';

        $this->writeFile($folderName, $exportFileName, $export);
        $this->writeFile($folderName, $requestFileName, time());
    }

    public function generateOneHTML($data)
    {

        $htmlset = '';

        foreach ($data as $item)
        {

            $level = count($item->keyPathArray);

            if ($item->elementType == 'folder')
            {
                $localhtmlset = $this->addHTMLSet(
                        $htmlset, $this->basePath .
                        DIRECTORY_SEPARATOR .
                        'content' .
                        $item->path .
                        DIRECTORY_SEPARATOR .
                        'index.md'
                );
                $localhtmlset = $this->clean($localhtmlset, $level, 'folder');
                $htmlset .= $localhtmlset;
                $htmlset .= $this->generateOneHTML($item->folderContent, $currentlevel);
            }
            else
            {
                $localhtmlset = $this->addHTMLSet(
                        $htmlset, $this->basePath .
                        DIRECTORY_SEPARATOR .
                        'content' .
                        $item->path
                );
                $localhtmlset = $this->clean($localhtmlset, $level, 'file');
                $htmlset .= $localhtmlset;
            }
        }

        return $htmlset;
    }

    public function addHTMLSet($htmlset, $url) {
        $contentMD = file_exists($url) ? file_get_contents($url) : NULL;

        $parsedown = new ParsedownExtension();
        $parsedown->setSafeMode(true);

        $contentArray = $parsedown->text($contentMD);

        $contentHTML = $parsedown->markup($contentArray);

        return $contentHTML;
    }

    public function clean($localhtmlset, $level, $folderorfile) {

        $level = $level;

        $texttochange = $localhtmlset;

        // Correct Headings
        if (( $level === 2 && $folderorfile === 'file') ||
                ( $level === 3 && $folderorfile === 'folder')) {
            $texttochange = str_replace("<h6", "<p><strong>", $texttochange);
            $texttochange = str_replace("</h6>", "</p></strong>", $texttochange);
            $texttochange = str_replace("<h5", "<h6", $texttochange);
            $texttochange = str_replace("</h5>", "</h6>", $texttochange);
            $texttochange = str_replace("<h4", "<h5", $texttochange);
            $texttochange = str_replace("</h4>", "</h5>", $texttochange);
            $texttochange = str_replace("<h3", "<h4", $texttochange);
            $texttochange = str_replace("</h3>", "</h4>", $texttochange);
            $texttochange = str_replace("<h2", "<h3", $texttochange);
            $texttochange = str_replace("</h2>", "</h3>", $texttochange);
            $texttochange = str_replace("<h1", "<h2", $texttochange);
            $texttochange = str_replace("</h1>", "</h2>", $texttochange);
        }

        // Correct Imagefolder
        $texttochange = str_replace('src="/media', 'src="../media', $texttochange);

        return $texttochange;
    }

}
