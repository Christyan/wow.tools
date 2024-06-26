<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/builds')]
class BuildsController extends BaseController
{
    
    #[Route('/', name: 'index')]
    public function indexAction()
    {
        global $allowedproducts;
        
        if (!empty($_GET['api']) && $_GET['api'] == "buildinfo") {


            if (empty($_GET['versionid']) || !filter_var($_GET['versionid'], FILTER_VALIDATE_INT)) {
                die("Invalid build ID!");
            }

            $query = $this->pdo->prepare("SELECT
                wow_versions.id as versionid,
                wow_versions.cdnconfig,
                wow_versions.buildconfig,
                wow_versions.patchconfig,
                wow_versions.complete as versioncomplete,
                wow_versions.product as versionproduct,
                wow_buildconfig.id as buildconfigid,
                wow_buildconfig.description,
                wow_buildconfig.product,
                wow_buildconfig.encoding,
                wow_buildconfig.encoding_cdn,
                wow_buildconfig.root,
                wow_buildconfig.root_cdn,
                wow_buildconfig.install,
                wow_buildconfig.install_cdn,
                wow_buildconfig.download,
                wow_buildconfig.download_cdn,
                wow_buildconfig.size,
                wow_buildconfig.size_cdn,
                wow_buildconfig.unarchivedcount,
                wow_buildconfig.unarchivedcomplete,
                wow_buildconfig.complete as buildconfigcomplete,
                wow_buildconfig.builton,
                wow_cdnconfig.archivecount,
                wow_cdnconfig.archivecomplete,
                wow_cdnconfig.indexcomplete,
                wow_cdnconfig.patcharchivecount,
                wow_cdnconfig.patcharchivecomplete,
                wow_cdnconfig.patchindexcomplete,
                wow_cdnconfig.complete as cdnconfigcomplete,
                wow_patchconfig.patch,
                wow_patchconfig.complete as patchconfigcomplete
                FROM wow_versions
                LEFT OUTER JOIN wow_buildconfig ON wow_versions.buildconfig=wow_buildconfig.hash
                LEFT OUTER JOIN wow_cdnconfig ON wow_versions.cdnconfig=wow_cdnconfig.hash
                LEFT OUTER JOIN wow_patchconfig ON wow_versions.patchconfig=wow_patchconfig.hash
                WHERE wow_versions.id = ?
            ");

            $query->execute([$_GET['versionid']]);

            $build = $query->fetch(\PDO::FETCH_ASSOC);

            if (empty($build)) {
                die("Version not found!");
            }

            return new Response($this->render('builds/build.html.twig', [
                'build' => $build,
                'allowedproducts' => $allowedproducts
            ]));

            die();
        } elseif (!empty($_GET['api']) && $_GET['api'] == "configdump") {
            if (!empty($_GET['config']) && strlen($_GET['config']) == 32 && ctype_xdigit($_GET['config'])) {
                echo "<pre>";
                echo file_get_contents(__DIR__ . "/../tpr/wow/config/" . $_GET['config'][0] . $_GET['config'][1] . "/" . $_GET['config'][2] . $_GET['config'][3] . "/" . $_GET['config']);
                echo "</pre>";
            } else {
                die("Invalid config!");
            }

            die();
        }


        // TODO: Read build-creator from config to flag these as custom in DB
        $customBuilds = ["0310d05306d08dd35b7dec587f7d6d9c", "409f5126361b17f3ac9c93228161fc1f", "a69219b6def10fe7114c378593974b28", "2a3a7d9fae49c5f7c09ef3b3fb50cad5", "7bab690ff8dbcdc57cdde8872fdea20e", "fbf8a2348df9e3747bbbc0190e26d437", "e349cc6ae70544baed8a919c4e9524df"];

        $query = "SELECT
            wow_versions.id as versionid,
            wow_versions.cdnconfig,
            wow_versions.buildconfig,
            wow_versions.patchconfig,
            wow_versions.releasetime as releasetime,
            wow_versions.complete as versioncomplete,
            wow_versions.product as versionproduct,
            wow_buildconfig.id as buildconfigid,
            wow_buildconfig.description,
            wow_buildconfig.product,
            wow_buildconfig.complete as buildconfigcomplete,
            wow_buildconfig.builton,
            wow_cdnconfig.archivecomplete,
            wow_cdnconfig.indexcomplete,
            wow_cdnconfig.patcharchivecomplete,
            wow_cdnconfig.patchindexcomplete,
            wow_cdnconfig.complete as cdnconfigcomplete,
            wow_patchconfig.patch,
            wow_patchconfig.complete as patchconfigcomplete
            FROM wow_versions
            LEFT OUTER JOIN wow_buildconfig ON wow_versions.buildconfig=wow_buildconfig.hash
            LEFT OUTER JOIN wow_cdnconfig ON wow_versions.cdnconfig=wow_cdnconfig.hash
            LEFT OUTER JOIN wow_patchconfig ON wow_versions.patchconfig=wow_patchconfig.hash
            ORDER BY wow_buildconfig.description DESC
        ";
        $res = $this->pdo->query($query);
        $allbuilds = $res->fetchAll();

        return new Response($this->render('builds/index.html.twig', [
            'allbuilds' => $allbuilds
        ]));
    }

    #[Route('/diff', name: 'build_diff')]
    public function buildDiffAction()
    {
        if (empty($_GET['from']) || empty($_GET['to'])) {
            die("From and to buildconfig hashes required");
        }

        $cdn = "wow";
        if(!empty($_GET['tpr']) && $_GET['tpr'] == "wowdev"){
            $cdn = "wowdev";
        }

        $fromBuild = getBuildConfigByBuildConfigHash($_GET['from'], $cdn);
        $toBuild = getBuildConfigByBuildConfigHash($_GET['to'], $cdn);

        if (empty($fromBuild) || empty($toBuild)) {
            die("Invalid builds!");
        }

        if(!empty($_GET['overrideTo'])){
            $toBuild['root_cdn'] = $_GET['overrideTo'];
        }

        $fromBuildName = parseBuildName($fromBuild['description'])['full'];
        $toBuildName = parseBuildName($toBuild['description'])['full'];

        $encrypted = $this->pdo->query("SELECT filedataid FROM wow_encrypted WHERE keyname NOT IN (SELECT keyname FROM wow_tactkey WHERE keybytes IS NOT NULL)")
            ->fetchAll(\PDO::FETCH_COLUMN);
        $encryptedbutnot = $this->pdo->query("SELECT filedataid FROM wow_encryptedbutnot")
            ->fetchAll(\PDO::FETCH_COLUMN);
        
        return new Response($this->render('builds/diff.html.twig', [
            'encrypted' => json_encode($encrypted),
            'encryptedbutnot' => json_encode($encryptedbutnot),
            'build_diff_url' => '/diff/diff_api?from=' . $fromBuild['root_cdn'] . '&to=' . $toBuild['root_cdn'] . '&cb=' . strtotime("now") . '&start=0&cdnDir=' . $cdn,
            'fromBuildName' => $fromBuildName,
            'toBuildName' => $toBuildName
        
        ]));
    }
}