<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mv')]
class ModelViewerController extends BaseController
{
    
    #[Route('/', name: 'mv_index')]
    public function indexAction()
    {
        $nonfilenamebuilds = $this->pdo->query("SELECT hash FROM wow_buildconfig WHERE description LIKE '%8.2%' OR description LIKE '%8.3%' OR description LIKE '%9.0%' OR description LIKE '%9.1%' OR description LIKE '%9.2%' OR description LIKE '%10.0%'")
            ->fetchAll(\PDO::FETCH_COLUMN);
        $staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

        return new Response($this->render('mv/index.html.twig', [
            'nonfilenamebuilds' => json_encode($nonfilenamebuilds),
            'staticBuild' => $staticBuild,
            'emscriptenBuildTime' => filemtime(WORK_DIR . "/public/mv/project.js"),
            'logoUrl' => SITE_URL . str_replace("embed=true", "", filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL))
        ]));
    }
    
}