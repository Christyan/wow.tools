<?php

require_once("../inc/config.php");

global $twig, $pdo;

if (!empty($_GET['id'])) {
    $q = $pdo->prepare("SELECT json FROM wowdata.wdb_quests WHERE id = ?");
    $q->execute([$_GET['id']]);

    $quest = json_decode($q->fetch(PDO::FETCH_ASSOC)['json'], true);
    if (empty($quest)) {
        die("Creature not found!");
    }

    echo json_encode($quest);
    die();
}

print $twig->render('db/quests.html.twig');