<?php

require_once("../inc/config.php");

global $twig, $pdo;

$nonfilenamebuilds = $pdo->query("SELECT hash FROM wow_buildconfig WHERE description LIKE '%8.2%' OR description LIKE '%8.3%' OR description LIKE '%9.0%' OR description LIKE '%9.1%' OR description LIKE '%9.2%' OR description LIKE '%10.0%'")->fetchAll(PDO::FETCH_COLUMN);
$staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

print $twig->render('mv/index.html.twig', [
    'nonfilenamebuilds' => json_encode($nonfilenamebuilds),
    'staticBuild' => $staticBuild,
    'emscriptenBuildTime' => filemtime(__DIR__ . "/project.js"),
    'logoUrl' => SITE_URL . str_replace("embed=true", "", filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL))
]);