<?php

use Twig\Extension\AbstractExtension;

class TwigFileFilters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('filemtime', [$this, 'filemtime']),
            new \Twig\TwigFilter('fileExists', [$this, 'fileExists']),
        ];
    }

    public function filemtime($filepath)
    {
        $mtime = 0;
        if (file_exists(WORK_DIR . $filepath)) {
            $mtime = filemtime(WORK_DIR . $filepath); 
        }
        
        return $filepath . '?v=' . $mtime;
    }
    
    public function fileExists($type, $hash, $cdndir = "wow")
    {
        return doesFileExist($type, $hash, $cdndir);
    }
}
