<?php

use Twig\Extension\AbstractExtension;

class TwigFilemtime extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('filemtime', [$this, 'filemtime']),
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
}
