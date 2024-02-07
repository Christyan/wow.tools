<?php

use Twig\Extension\AbstractExtension;

class TwigDataConvertFilters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('parseBuildName', [$this, 'parseBuildName']),
            new \Twig\TwigFilter('prettyBranch', [$this, 'prettyBranch']),
            new \Twig\TwigFilter('branchColor', [$this, 'branchColor']),
        ];
    }

    public function parseBuildName($buildname)
    {
        return parseBuildName($buildname);
    }
    
    public function prettyBranch($branch, $realPretty = true)
    {
        return prettyBranch($branch, $realPretty);
    }

    public function branchColor($branch)
    {
        return prettyBranch($branch, false, true);
    }
    
    
}
