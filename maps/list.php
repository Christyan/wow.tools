<?php

require_once("../inc/config.php");

global $twig, $pdo;

$versionCacheByBuild = [];
foreach ($pdo->query("SELECT id, version, build FROM wow_builds ORDER BY build DESC") as $version) {
    $versionCacheByBuild[$version['build']] = $version['version'];
}

$versionCacheByID = [];
foreach ($pdo->query("SELECT id, version, build FROM wow_builds ORDER BY build DESC") as $version) {
    $versionCacheByID[$version['id']] = $version;
}


$mapCacheByID = [];
foreach ($pdo->query("SELECT id, name, internal, firstseen FROM wow_maps_maps ORDER BY firstseen ASC") as $map) {
    $mapCacheByID[$map['id']] = $map;
}

$mapConfigCache = [];
foreach ($pdo->query("SELECT * FROM wow_maps_config") as $mapConfig) {
    $mapConfigCache[$mapConfig['versionid']][$mapConfig['mapid']] = $mapConfig;
}

$versionMapCacheByMap = [];
foreach ($pdo->query("SELECT map_id as mapid, versionid FROM wow_maps_versions") as $mapVersion) {
    $versionMapCacheByMap[$mapVersion['mapid']][] = $mapVersion['versionid'];
}

function cmp_by_build($a, $b)
{
    return $a["build"] - $b["build"];
}

foreach ($mapCacheByID as $mapid => &$map) {
    $map['mapVersions'] = [];
    foreach ($versionMapCacheByMap[$mapid] as $versionmap) {
        $map['mapVersions'][] = $versionCacheByID[$versionmap];
    }
    usort($map['mapVersions'], "cmp_by_build");
}

print $twig->render('maps/list.html.twig', [
    'mapCacheByID' => $mapCacheByID,
    'versionCacheByBuild' => $versionCacheByBuild
]);