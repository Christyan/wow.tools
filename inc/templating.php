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

$twig->addGlobal('title', prettyTitle($_SERVER['REQUEST_URI']));
$twig->addGlobal('meta_desc', generateMeta($_SERVER['REQUEST_URI'], true));
$twig->addGlobal('embed', !empty($_GET['embed']));
$twig->addGlobal('loggedin', !empty($_SESSION['loggedin']));
$twig->addGlobal('API_URL', API_URL);