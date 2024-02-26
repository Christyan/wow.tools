<?php

namespace App\Core\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigFileFilters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('filemtime', [$this, 'filemtime']),
            new TwigFilter('doesFileExist', [$this, 'doesFileExist']),
            new TwigFilter('basename', [$this, 'basename']),
        ];
    }

    public function filemtime($filepath)
    {
        $mtime = 0;
        if (file_exists(WORK_DIR . '/public' .  $filepath)) {
            $mtime = filemtime(WORK_DIR . '/public' . $filepath);
        }

        return $filepath . '?v=' . $mtime;
    }

    public function doesFileExist($type, $hash, $cdndir = "wow")
    {
        return doesFileExist($type, $hash, $cdndir);
    }

    public function basename($path, $suffix = '')
    {
        return basename($path, $suffix = '');
    }


}
