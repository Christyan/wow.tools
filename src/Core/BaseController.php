<?php

namespace App\Core;

use App\Core\Twig\TwigDataConvertFilters;
use App\Core\Twig\TwigFileFilters;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class BaseController
{
    protected \PDO $pdo;
    private Environment $twig;
    
    public function __construct()
    {
        global $pdo;
        
        $this->pdo = $pdo;
        
        $loader = new FilesystemLoader(WORK_DIR . '/template');
        $this->twig = new Environment($loader, [
            'cache' => WORK_DIR . '/var/cache',
            'auto_reload' => defined('DEV')
        ]);

        $this->twig->addExtension(new TwigFileFilters());
        $this->twig->addExtension(new TwigDataConvertFilters());

        $globals = [
            'title' => prettyTitle($_SERVER['REQUEST_URI']),
            'meta_desc' => generateMeta($_SERVER['REQUEST_URI'], true),
            'embed' => !empty($_GET['embed']),
            'loggedin' => !empty($_SESSION['loggedin']),
            'user' => @$_SESSION['user'],
            'API_URL' => API_URL
        ];

        $this->twig->addGlobal('global', $globals);
    }

    public function render($template, array $context = [])
    {
        return $this->twig->render($template, $context);
    }
    
}