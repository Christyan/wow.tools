<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
class IndexController extends BaseController
{
    
    #[Route('', name: 'index')]
    public function indexAction()
    {
        return new Response($this->render('index/index.html.twig'));
    }

    #[Route('404', name: 'notfound')]
    public function notFoundAction()
    {
        return new Response($this->render('error/404.html.twig'));
    }
    
}