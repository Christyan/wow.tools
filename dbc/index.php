<?php

require_once("../inc/config.php");

global $twig, $pdo;

// Map old URL to new url for backwards compatibility
if (!empty($_GET['bc'])) {
    $bcq = $pdo->prepare("SELECT description FROM wow_buildconfig WHERE hash = ?");
    $bcq->execute([$_GET['bc']]);
    $row = $bcq->fetch();
    if (!empty($row)) {
        $build = parseBuildName($row['description'])['full'];
        $newurl = str_replace("bc=" . $_GET['bc'], "build=" . $build, $_SERVER['REQUEST_URI']);
        $newurl = str_replace(".db2", "", $newurl);
        echo "<meta http-equiv='refresh' content='0; url=//" . $_SERVER['SERVER_NAME'] . $newurl . "'>";
        die();
    }
} elseif (!empty($_GET['dbc']) && strpos($_GET['dbc'], "db2") !== false) {
    $newurl = str_replace(".db2", "", $_SERVER['REQUEST_URI']);
    echo "<meta http-equiv='refresh' content='0; url=//" . $_SERVER['SERVER_NAME'] .  $newurl . "'>";
    die();
}

print $twig->render('dbc/index.html.twig');


