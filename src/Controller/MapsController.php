<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/maps')]
class MapsController extends BaseController
{
    
    #[Route('/', name: 'maps_index')]
    public function indexAction()
    {
        return new Response($this->render('maps/index.html.twig'));
    }

    #[Route('/{map}/{version}/{zoom}/{lat}/{lng}', name: 'maps_map', requirements: ['lng' => '\d+\.\d+'])]
    public function mapAction(string $map)
    {
        return $this->indexAction();
    }
    
}