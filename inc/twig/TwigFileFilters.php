<?php

use Twig\Extension\AbstractExtension;

class TwigFileFilters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('filemtime', [$this, 'filemtime']),
            new \Twig\TwigFilter('doesFileExist', [$this, 'doesFileExist']),
            new \Twig\TwigFilter('basename', [$this, 'basename']),
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
    
    public function doesFileExist($type, $hash, $cdndir = "wow")
    {
        return doesFileExist($type, $hash, $cdndir);
    }
    
    public function basename ($path, $suffix = '')
    {
        return basename($path, $suffix = '');
    }
    
    
    
}
