<?php

namespace App\Core\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TwigDataConvertFilters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dump', [$this, 'dump']),
            new TwigFilter('rawurlencode', [$this, 'rawurlencode']),
            new TwigFilter('rawurldecode', [$this, 'rawurldecode']),
            new TwigFilter('parseBuildName', [$this, 'parseBuildName']),
            new TwigFilter('prettyBranch', [$this, 'prettyBranch']),
            new TwigFilter('branchColor', [$this, 'branchColor']),
            new TwigFilter('prettyBuild', [$this, 'prettyBuild']),
            new TwigFilter('humanBytes', [$this, 'humanBytes']),
        ];
    }

    public function dump($data)
    {
        return var_export($data);
    }

    public function rawurlencode(string $string)
    {
        return rawurlencode($string);
    }

    public function rawurldecode(string $string)
    {
        return rawurldecode($string);
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
