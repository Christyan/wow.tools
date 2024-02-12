<?php

require_once("../inc/config.php");

global $twig, $pdo;

if (!empty($_GET['id'])) {
    $q = $pdo->prepare("SELECT json FROM wowdata.wdb_creatures WHERE id = ?");
    $q->execute([$_GET['id']]);

    $creature = json_decode($q->fetch(PDO::FETCH_ASSOC)['json'], true);
    if (empty($creature)) {
        die("Creature not found!");
    }

    $filedataid = null;
    if (!empty($creature['CreatureDisplayInfoID[0]'])) {
        $cdi = $pdo->prepare("SELECT filedataid FROM wowdata.creaturemodeldata WHERE id IN (SELECT ModelID FROM wowdata.creaturedisplayinfo WHERE ID = ?)");
        $cdi->execute([$creature['CreatureDisplayInfoID[0]']]);
        $cdirow = $cdi->fetch(PDO::FETCH_ASSOC);
        if (!empty($cdirow)) {
            $filedataid = $cdirow['filedataid'];
        }
    }

    print $twig->render('db/creatures.html.twig', [
        'creature' => $creature,
        'filedataid' => $filedataid 
    ]);
    
    die();
}

print $twig->render('db/creatures.html.twig');