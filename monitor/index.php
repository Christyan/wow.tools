<?php

require_once("../inc/config.php");

global $twig, $pdo;

if (empty($_SESSION['rank'])) {
    $productq = $pdo->query("SELECT * FROM ngdp_products WHERE program LIKE 'wow%' ORDER BY name DESC");
} else {
    $productq = $pdo->query("SELECT * FROM ngdp_products ORDER BY name DESC");
}
$products = [];
while ($row = $productq->fetch()) {
    $products[] = array("name" => $row['name'], "product" => $row['program']);
}

print $twig->render('monitor/index.html.twig', [
    'products' => json_encode($products)
]);