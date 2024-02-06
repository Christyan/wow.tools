<?php

require_once(__DIR__ . "/../inc/config.php");

global $twig, $pdo;

$templateName = !empty($_GET['embed']) ? 'dbc/diff_embed.html.twig' : 'dbc/diff.html.twig';

// Map old URL to new url for backwards compatibility
if (!empty($_GET['old']) && strlen($_GET['old']) == 32 || !empty($_GET['new']) && strlen($_GET['new']) == 32) {
    $bcq = $pdo->prepare("SELECT description FROM wow_buildconfig WHERE hash = ?");

    $bcq->execute([$_GET['old']]);
    $oldrow = $bcq->fetch();

    $bcq->execute([$_GET['new']]);
    $newrow = $bcq->fetch();

    if (!empty($oldrow) && !empty($newrow)) {
        $oldbuild = parseBuildName($oldrow['description'])['full'];
        $newbuild = parseBuildName($newrow['description'])['full'];
        $newurl = str_replace($_GET['old'], $oldbuild, $_SERVER['REQUEST_URI']);
        $newurl = str_replace($_GET['new'], $newbuild, $newurl);
        $newurl = str_replace(".db2", "", $newurl);
        echo "<meta http-equiv='refresh' content='0; url=//" . $_SERVER['SERVER_NAME'] . $newurl . "'>";
        die();
    }
}

$tables = [];
$currentDB = null;
$versions = null;

foreach ($pdo->query("SELECT * FROM wow_dbc_tables ORDER BY name ASC") as $dbc) {
    $tables[$dbc['id']] = $dbc;
    if (!empty($_GET['dbc']) && $_GET['dbc'] == $dbc['name']) {
        $currentDB = $dbc;

        $vq = $pdo->prepare("SELECT * FROM wow_dbc_table_versions LEFT JOIN wow_builds ON wow_dbc_table_versions.versionid=wow_builds.id WHERE wow_dbc_table_versions.tableid = ? AND wow_dbc_table_versions.hasDefinition = 1 ORDER BY wow_builds.expansion DESC, wow_builds.major DESC, wow_builds.minor DESC, wow_builds.build DESC");
        $vq->execute([$currentDB['id']]);
        $versions = $vq->fetchAll();
    }
}

$canDiff = false;
if (!empty($currentDB) && !empty($_GET['old']) && !empty($_GET['new'])) {
    $canDiff = true;
}

print $twig->render($templateName, [
    'canDiff' => $canDiff,
    'tables' => $tables,
    'currentDB' => $currentDB,
    'versions' => $versions,
    'selectedDbc' => @$_GET['dbc'],
    'old' => @$_GET['old'],
    'new' => @$_GET['new'],
    'useHotfixes' => @$_GET['useHotfixes']
]);
