<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dbc')]
class DbcController extends BaseController
{
    
    #[Route('/', name: 'dbc_index')]
    public function indexAction()
    {
        return new Response($this->render('dbc/index.html.twig'));
    }
    
    #[Route('/diff', name: 'dbc_diff')]
    public function diffAction()
    {
        $templateName = !empty($_GET['embed']) ? 'dbc/diff_embed.html.twig' : 'dbc/diff.html.twig';

        $tables = [];
        $currentDB = null;
        $versions = null;

        foreach ($this->pdo->query("SELECT * FROM wow_dbc_tables ORDER BY name ASC") as $dbc) {
            $tables[$dbc['id']] = $dbc;
            if (!empty($_GET['dbc']) && $_GET['dbc'] == $dbc['name']) {
                $currentDB = $dbc;

                $vq = $this->pdo->prepare("SELECT * FROM wow_dbc_table_versions LEFT JOIN wow_builds ON wow_dbc_table_versions.versionid=wow_builds.id WHERE wow_dbc_table_versions.tableid = ? AND wow_dbc_table_versions.hasDefinition = 1 ORDER BY wow_builds.expansion DESC, wow_builds.major DESC, wow_builds.minor DESC, wow_builds.build DESC");
                $vq->execute([$currentDB['id']]);
                $versions = $vq->fetchAll();
            }
        }

        $canDiff = false;
        if (!empty($currentDB) && !empty($_GET['old']) && !empty($_GET['new'])) {
            $canDiff = true;
        }

        return new Response($this->render($templateName, [
            'canDiff' => $canDiff,
            'tables' => $tables,
            'currentDB' => $currentDB,
            'versions' => $versions,
            'selectedDbc' => @$_GET['dbc'],
            'old' => @$_GET['old'],
            'new' => @$_GET['new'],
            'useHotfixes' => @$_GET['useHotfixes']
        ]));
    }
    
}