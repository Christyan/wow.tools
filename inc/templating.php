<?php

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once('twig/TwigFileFilters.php');
require_once('twig/TwigDataConvertFilters.php');

$loader = new FilesystemLoader(__DIR__ . '/../template');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/../var/cache',
    'auto_reload' => defined('DEV')
]);

$twig->addExtension(new TwigFileFilters());
$twig->addExtension(new TwigDataConvertFilters());

$globals = [
    'title' => prettyTitle($_SERVER['REQUEST_URI']),
    'meta_desc' => generateMeta($_SERVER['REQUEST_URI'], true),
    'embed' => !empty($_GET['embed']),
    'loggedin' => !empty($_SESSION['loggedin']),
    'user' => @$_SESSION['user'],
    'API_URL' => API_URL
];

$twig->addGlobal('global', $globals);