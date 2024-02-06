<?php
require_once("../inc/config.php");

global $twig, $pdo;

$versionCacheByID = [];
foreach ($pdo->query("SELECT id, version FROM wow_builds") as $version) {
    $versionCacheByID[$version['id']] = $version['version'];
}

$tableCacheByID = [];
foreach ($pdo->query("SELECT id, displayname FROM wow_dbc_tables") as $table) {
    $tableCacheByID[$table['id']] = $table['displayname'];
}

$versionTableCache = [];
foreach ($pdo->query("SELECT versionid, tableid FROM wow_dbc_table_versions") as $tv) {
    $versionTableCache[$tv['versionid']][] = $tv['tableid'];
}

print $twig->render('dbc/dbstats.html.twig', [
    'versions' => $pdo->query("SELECT versionid, COUNT(*) as noDefCount FROM wow_dbc_table_versions WHERE hasDefinition = 0 GROUP BY versionid ORDER BY noDefCount DESC")->fetchAll(),
    'tables' => $pdo->query("SELECT tableid, COUNT(*) as noDefCount FROM wow_dbc_table_versions WHERE hasDefinition = 0 GROUP BY tableid ORDER BY noDefCount DESC")->fetchAll(),
    'versionCacheByID' => $versionCacheByID,
    'tableCacheByID' => $tableCacheByID
]);