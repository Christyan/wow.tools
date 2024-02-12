<?php

require_once("../inc/config.php");

global $twig, $pdo;

$templateName = !empty($_GET['embed']) ? 'db/items_embed.html.twig' : 'db/items.html.twig';

if (!empty($_GET['embed']) && !empty($_GET['spellid'])) {
}

$_GET['dbc'] = "itemsearchname";

foreach ($pdo->query("SELECT * FROM wow_dbc_tables WHERE name = 'itemsearchname' ORDER BY name ASC") as $dbc) {
    $tables[$dbc['id']] = $dbc;
    if (!empty($_GET['dbc']) && $_GET['dbc'] == $dbc['name']) {
        $currentDB = $dbc;
    }
}

$vq = $pdo->prepare("SELECT * FROM wow_dbc_table_versions LEFT JOIN wow_builds ON wow_dbc_table_versions.versionid=wow_builds.id WHERE wow_dbc_table_versions.tableid = ?  AND wow_dbc_table_versions.hasDefinition = 1 ORDER BY version DESC");
$vq->execute([$currentDB['id']]);
$version = $vq->fetch();

print $twig->render($templateName);