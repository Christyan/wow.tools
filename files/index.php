<?php

require_once("../inc/config.php");

global $twig, $pdo;

$buildq = $pdo->prepare("SELECT hash, description FROM `wow_buildconfig` WHERE product = ? ORDER BY id DESC LIMIT 1;");
$lfproducts = array("wow", "wowt", "wow_classic", "wow_classic_era", "wow_classic_era_ptr");
$lfbuilds = [];
foreach ($lfproducts as $lfproduct) {
    $buildq->execute([$lfproduct]);
    $lfbuilds[$lfproduct] = $buildq->fetch(PDO::FETCH_ASSOC);
}

print $twig->render('files/index.html.twig', [
    'buildfilterid' => @$_SESSION['buildfilterid'],
    'builds' => $pdo->query("SELECT description, root_cdn FROM wow_buildconfig ORDER BY wow_buildconfig.description DESC")->fetchAll(),
]);