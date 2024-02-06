<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once('twig/TwigFilemtime.php');

$loader = new FilesystemLoader(__DIR__ . '/../template');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/../var/cache',
    'auto_reload' => defined('DEV')
]);

$twig->addExtension(new TwigFilemtime());

$globals = [
    'title' => prettyTitle($_SERVER['REQUEST_URI']),
    'meta_desc' => generateMeta($_SERVER['REQUEST_URI'], true),
    'embed' => !empty($_GET['embed']),
    'loggedin' => !empty($_SESSION['loggedin']),
    'API_URL' => API_URL
];

$twig->addGlobal('global', $globals);