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
            new \Twig\TwigFilter('prettyBuild', [$this, 'prettyBuild']),
            new \Twig\TwigFilter('humanBytes', [$this, 'humanBytes']),
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
    
    public function prettyBuild($build)
    {
        return prettyBuild($build);
    }
    
    public function humanBytes(float $Bytes, int $Precision = 2): string
    {
        return humanBytes($Bytes, $Precision);
    }
    
    
}