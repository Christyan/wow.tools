<?php

namespace App\Controller;

use App\Core\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/db')]
class DbController extends BaseController
{
    
    #[Route('/', name: 'db_index')]
    public function indexAction()
    {
        return new Response($this->render('db/index.html.twig'));
    }

    #[Route('/creatures', name: 'db_creatures')]
    public function creaturesAction()
    {
        if (!empty($_GET['id'])) {
            $q = $this->pdo->prepare("SELECT json FROM wowdata.wdb_creatures WHERE id = ?");
            $q->execute([$_GET['id']]);

            $creature = json_decode($q->fetch(\PDO::FETCH_ASSOC)['json'], true);
            if (empty($creature)) {
                die("Creature not found!");
            }

            $filedataid = null;
            if (!empty($creature['CreatureDisplayInfoID[0]'])) {
                $cdi = $this->pdo->prepare("SELECT filedataid FROM wowdata.creaturemodeldata WHERE id IN (SELECT ModelID FROM wowdata.creaturedisplayinfo WHERE ID = ?)");
                $cdi->execute([$creature['CreatureDisplayInfoID[0]']]);
                $cdirow = $cdi->fetch(\PDO::FETCH_ASSOC);
                if (!empty($cdirow)) {
                    $filedataid = $cdirow['filedataid'];
                }
            }

            return new Response($this->render('db/creatures.html.twig', [
                'creature' => $creature,
                'filedataid' => $filedataid
            ]));
        }

        return new Response($this->render('db/creatures.html.twig'));
    }

    #[Route('/creature/api', name: 'db_creatures_api')]
    public function creatureApiAction()
    {
        if (!empty($_GET['type']) && $_GET['type'] == "bycdi" && !empty($_GET['id'])) {
            $q = $this->pdo->prepare('SELECT id, name, json FROM wowdata.wdb_creatures WHERE json LIKE ?');
            $q->execute(["%CreatureDisplayInfoID[_]\":\"" . $_GET['id'] . "%"]);

            header("Content-Type: application/json");

            $res = [];
            while ($row = $q->fetch(\PDO::FETCH_ASSOC)) {
                $res[] = array("id" => $row['id'], "name" => $row['name'], json_decode($row['json'], true));
            }

            echo json_encode($res);

            die();
        }

        if (!empty($_GET['id'])) {
            $q = $this->pdo->prepare("SELECT json FROM wowdata.wdb_creatures WHERE id = ?");
            $q->execute([$_GET['id']]);

            header("Content-Type: application/json");

            $row = $q->fetch(\PDO::FETCH_ASSOC);
            if (empty($row)) {
                die(json_encode(["error" => "Creature not found!"]));
            }

            $creature = json_decode($row['json'], true);

            if (!empty($creature['CreatureDisplayInfoID[0]'])) {
                $cdi = $this->pdo->prepare("SELECT filedataid FROM wowdata.creaturemodeldata WHERE id IN (SELECT ModelID FROM wowdata.creaturedisplayinfo WHERE ID = ?)");
                $cdi->execute([$creature['CreatureDisplayInfoID[0]']]);
                $cdirow = $cdi->fetch(\PDO::FETCH_ASSOC);
                if (!empty($cdirow)) {
                    $creature['filedataid'] = $cdirow['filedataid'];
                }
            }

            echo json_encode($creature);

            die();
        }

        $query = "FROM wowdata.wdb_creatures ";

        if (!empty($_GET['search']['value'])) {
            if (substr($_GET['search']['value'], 0, 8) == "addedin:") {
                $searchBuild = str_replace("addedin:", "", $_GET['search']['value']);
                if (is_numeric($searchBuild)) {
                    $query .= " WHERE firstseenbuild = " . $searchBuild;
                }
            } elseif (substr($_GET['search']['value'], 0, 6) == "field:") {
                $searchJSON = str_replace("field:", "", trim($_GET['search']['value']));
                $searchExploded = explode("=", $searchJSON);
                if (count($searchExploded) == 2) {
                    $query .= " WHERE JSON_CONTAINS(json, :jsonVal, :jsonKey)";
                    $jsonSearch = [];
                    $jsonSearch['key'] = "$." . $searchExploded[0];
                    $jsonSearch['value'] = "\"" . $searchExploded[1] . "\"";
                }
            } elseif (substr($_GET['search']['value'], 0, 3) == "id:") {
                $searchID = str_replace("id:", "", trim($_GET['search']['value']));

                if(!is_numeric($searchID))
                    die("Invalid ID");

                $query .= " WHERE id = " . $searchID;
            } else {
                $query .= " WHERE id LIKE :search1 OR name LIKE :search2";
                $search = "%" . $_GET['search']['value'] . "%";
            }
        }

        $orderby = '';
        if (!empty($_GET['order'])) {
            $orderby .= " ORDER BY ";
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderby .= "wdb_creatures.id";
                    break;
                case 1:
                    $orderby .= "wdb_creatures.name";
                    break;
                case 2:
                    $orderby .= "wdb_creatures.firstseenbuild";
                    break;
                case 3:
                    $orderby .= "wdb_creatures.lastupdatedbuild";
                    break;
            }

            switch ($_GET['order'][0]['dir']) {
                case "asc":
                    $orderby .= " ASC";
                    break;
                case "desc":
                    $orderby .= " DESC";
                    break;
            }
        }

        $start = (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
        $length = (int)filter_input(INPUT_GET, 'length', FILTER_SANITIZE_NUMBER_INT);

        $numrowsq = $this->pdo->prepare("SELECT COUNT(1) " . $query);
        $dataq = $this->pdo->prepare("SELECT * " . $query . $orderby . " LIMIT " . $start . ", " . $length);

        if (!empty($search)) {
            $numrowsq->bindParam(":search1", $search);
            $numrowsq->bindParam(":search2", $search);
            $dataq->bindParam(":search1", $search);
            $dataq->bindParam(":search2", $search);
        }

        if (!empty($jsonSearch)) {
            $numrowsq->bindParam(":jsonKey", $jsonSearch['key']);
            $numrowsq->bindParam(":jsonVal", $jsonSearch['value']);
            $dataq->bindParam(":jsonKey", $jsonSearch['key']);
            $dataq->bindParam(":jsonVal", $jsonSearch['value']);
        }

        $dataq->execute();
        $numrowsq->execute();

        if(isset($_GET['draw'])){
            $returndata['draw'] = (int)$_GET['draw'];
        }

        $returndata['recordsFiltered'] = (int)$numrowsq->fetchColumn();
        $returndata['recordsTotal'] = $this->pdo->query("SELECT count(id) FROM wowdata.wdb_creatures")->fetchColumn();
        $returndata['data'] = array();

        foreach ($dataq->fetchAll() as $row) {
            $returndata['data'][] = array($row['id'], $row['name'], $row['firstseenbuild'], $row['lastupdatedbuild']);
        }

        return new JsonResponse($returndata);
    }

    #[Route('/quests', name: 'db_quests')]
    public function questsAction()
    {
        if (!empty($_GET['id'])) {
            $q = $this->pdo->prepare("SELECT json FROM wowdata.wdb_quests WHERE id = ?");
            $q->execute([$_GET['id']]);

            $quest = json_decode($q->fetch(\PDO::FETCH_ASSOC)['json'], true);
            if (empty($quest)) {
                die("Creature not found!");
            }

            return new JsonResponse($quest);
        }

        return new Response($this->render('db/quests.html.twig'));
    }

    #[Route('/quest/api', name: 'db_creatures_api')]
    public function questApiAction()
    {
        if (!empty($_GET['id'])) {
            $q = $this->pdo->prepare("SELECT json FROM wowdata.wdb_quests WHERE id = ?");
            $q->execute([$_GET['id']]);

            header("Content-Type: application/json");

            $row = $q->fetch(\PDO::FETCH_ASSOC);
            if (empty($row)) {
                die(json_encode(["error" => "Quest not found!"]));
            }

            $quest = json_decode($row['json'], true);

            echo json_encode($quest);

            die();
        }

        $query = "FROM wowdata.wdb_quests ";

        if (!empty($_GET['search']['value'])) {
            $query .= " WHERE id LIKE :search1 OR name LIKE :search2";
            $search = "%" . $_GET['search']['value'] . "%";
        }

        $orderby = '';
        if (!empty($_GET['order'])) {
            $orderby .= " ORDER BY ";
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderby .= "wdb_quests.id";
                    break;
                case 1:
                    $orderby .= "wdb_quests.name";
                    break;
                case 2:
                    $orderby .= "wdb_quests.firstseenbuild";
                    break;
                case 3:
                    $orderby .= "wdb_quests.lastupdatedbuild";
                    break;
            }

            switch ($_GET['order'][0]['dir']) {
                case "asc":
                    $orderby .= " ASC";
                    break;
                case "desc":
                    $orderby .= " DESC";
                    break;
            }
        }

        $start = (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT);
        $length = (int)filter_input(INPUT_GET, 'length', FILTER_SANITIZE_NUMBER_INT);

        $numrowsq = $this->pdo->prepare("SELECT COUNT(1) " . $query);
        $dataq = $this->pdo->prepare("SELECT * " . $query . $orderby . " LIMIT " . $start . ", " . $length);

        if (!empty($search)) {
            $numrowsq->bindParam(":search1", $search);
            $numrowsq->bindParam(":search2", $search);
            $dataq->bindParam(":search1", $search);
            $dataq->bindParam(":search2", $search);
        }

        $numrowsq->execute();
        $dataq->execute();

        if(isset($_GET['draw'])){
            $returndata['draw'] = (int)$_GET['draw'];
        }

        $returndata['recordsFiltered'] = (int)$numrowsq->fetchColumn();
        $returndata['recordsTotal'] = $this->pdo->query("SELECT count(id) FROM wowdata.wdb_quests")->fetchColumn();
        $returndata['data'] = array();

        foreach ($dataq->fetchAll() as $row) {
            $returndata['data'][] = array($row['id'], $row['name'], $row['firstseenbuild'], $row['lastupdatedbuild']);
        }

        return new JsonResponse($returndata);
    }
    
}