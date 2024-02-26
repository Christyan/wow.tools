<?php

namespace App\Controller;

use App\Core\BaseController;
use DI\Attribute\Inject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/monitor')]
class MonitorController extends BaseController
{
    
    #[Route('/', name: 'index')]
    public function indexAction()
    {
        if (empty($_SESSION['rank'])) {
            $productq = $this->pdo->query("SELECT * FROM ngdp_products WHERE program LIKE 'wow%' ORDER BY name DESC");
        } else {
            $productq = $this->pdo->query("SELECT * FROM ngdp_products ORDER BY name DESC");
        }
        $products = [];
        while ($row = $productq->fetch()) {
            $products[] = array("name" => $row['name'], "product" => $row['program']);
        }

        return new Response($this->render('monitor/index.html.twig', [
            'products' => json_encode($products)
        ]));
    }

    #[Route('/api', name: 'index')]
    public function monitorApiAction()
    {
        if (!isset($_GET['draw']) || !isset($_GET['order'][0]['column'])) {
            return new Response('', 400);
        }

        $query = "FROM ngdp_history INNER JOIN ngdp_urls on ngdp_urls.id=ngdp_history.url_id";

        if (isset($_SESSION['rank'])) {
            if (!empty($_GET['columns'][1]['search']['value'])) {
                $query .= " WHERE event = 'valuechange' AND ngdp_urls.url LIKE :prodSearch";
                $prodSearch = "%" . $_GET['columns'][1]['search']['value'] . "%";
            } else {
                $query .= " WHERE event = 'valuechange'";
            }
        } else {
            if (!empty($_GET['columns'][1]['search']['value']) && strpos($_GET['columns'][1]['search']['value'], "wow") !== false) {
                $query .= " WHERE event = 'valuechange' AND ngdp_urls.url LIKE :prodSearch";
                $prodSearch = "%" . $_GET['columns'][1]['search']['value'] . "%";
            } else {
                $query .= " WHERE event = 'valuechange' AND ngdp_urls.url LIKE '%wow%'";
            }
        }


        if (!empty($_GET['search']['value'])) {
            $query .= " AND CONCAT_WS(' ', ngdp_history.oldvalue, ngdp_history.newvalue) LIKE :search";
            $search = "%" . $_GET['search']['value'] . "%";
        }

        $orderby = '';
        if (!empty($_GET['order'])) {
            $orderby .= " ORDER BY ";
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderby .= "ngdp_history.timestamp";
                    break;
                case 1:
                    $orderby .= "ngdp_history.url_id";
                    break;
                case 2:
                    // no sorting by diff, yet
                    $orderby .= "ngdp_history.timestamp";
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

        if (!empty($prodSearch)) {
            $numrowsq->bindParam(":prodSearch", $prodSearch);
            $dataq->bindParam(":prodSearch", $prodSearch);
        }

        if (!empty($search)) {
            $numrowsq->bindParam(":search", $search);
            $dataq->bindParam(":search", $search);
        }
        $numrowsq->execute();
        $dataq->execute();

        $returndata['draw'] = (int)$_GET['draw'];
        $returndata['recordsFiltered'] = (int)$numrowsq->fetchColumn();
        $returndata['recordsTotal'] = $this->pdo->query("SELECT count(id) FROM ngdp_history WHERE event='valuechange'")->fetchColumn();
        $returndata['data'] = array();

        foreach ($dataq->fetchAll() as $row) {
            $urlex = explode("/", $row['url']);
            $product = $urlex[3];

            $before = [];
            $after = [];

            if (substr($row['url'], -4, 4) == "game" || substr($row['url'], -7, 7) == "install") {
                $before = json_decode(utf8_encode($row['oldvalue']), true);
                $after = json_decode(utf8_encode($row['newvalue']), true);
            } else {
                if(!empty($row['oldvalue'])){
                    $before = parseBPSV(explode("\n", $row['oldvalue']));
                }

                if(!empty($row['newvalue'])){
                    $after = parseBPSV(explode("\n", $row['newvalue']));
                }
            }

            if($before == null){
                $before = [];
            }

            if($after == null){
                $after = [];
            }

            $diffs = \CompareArrays::Diff($before, $after);

            if (empty($diffs)) {
                $difftext = "No changes found -- likely only a sequence number increase or an error occurred";
            } else {
                $diffs = \CompareArrays::Flatten($diffs);

                $difftext = "<table class='table table-condensed table-hover subtable' style='width: 100%; font-size: 11px;'>";
                $difftext .= "<thead><tr><th style='width: 20px'>&nbsp;</th><th style='width: 100px'>Name</th><th>Before</th><th>After</th><th>&nbsp;</th></thead>";
                foreach ($diffs as $name => $diff) {
                    switch ($diff->Type) {
                        case "added":
                            $icon = 'plus';
                            break;
                        case "modified":
                            $icon = 'pencil';
                            break;
                        case "removed":
                            $icon = 'times';
                            break;
                    }

                    $showUrl = false;

                    if ($this->hasCDNDir($product)) {
                        if (strpos($name, "BuildConfig") !== false || strpos($name, "CDNConfig") !== false) {
                            $showUrl = true;
                            $oldurl = $this->buildURL($product, "config", $diff->OldValue);
                            $newurl = $this->buildURL($product, "config", $diff->NewValue);
                        } elseif (strpos($name, "ProductConfig") !== false) {
                            $showUrl = true;
                            if (!empty($diff->OldValue)) {
                                $oldurl = $this->buildURL($product, "tpr/configs/data", $diff->OldValue);
                            } else {
                                $oldurl = '#';
                            }
                            if (!empty($diff->NewValue)) {
                                $newurl = $this->buildURL($product, "tpr/configs/data", $diff->NewValue);
                            } else {
                                $newurl = '#';
                            }
                        }
                    }

                    if ($showUrl) {
                        $difftext .= "<tr><td><i class='fa fa-" . $icon . "'></i></td><td>" . $name . "</td><td><a href='" . $oldurl . "' target='_BLANK'>" . $diff->OldValue . "</a></td><td><a href='" . $newurl . "' target='_BLANK'>" . $diff->NewValue . "</a></td><td><a style='cursor: pointer' data-toggle='modal' data-target='#previewModal' onClick='fillDiffModal(\"" . str_replace("http://blzddist1-a.akamaihd.net/", "", $oldurl) . "\", \"" . str_replace("http://blzddist1-a.akamaihd.net/", "", $newurl) . "\")'>Preview</a>";
                    } else {
                        $difftext .= "<tr><td><i class='fa fa-" . $icon . "'></i></td><td>" . $name . "</td><td>" . $diff->OldValue . "</td><td>" . $diff->NewValue . "</td><td></td></tr>";
                    }
                }
            }


            $difftext .= "</table>";

            $row['diff'] = print_r($diffs, true);


            $returndata['data'][] = array($row['timestamp'], $row['name'] . " (" . $product . ")", "" . $difftext . "");
        }

        return new JsonResponse($returndata);
    }

    #[Route('/diff', name: 'index')]
    public function monitorDiffAction()
    {
        if (empty($_GET['from']) || empty($_GET['to'])) {
            die("Not enough information to diff");
        }

        if (substr($_GET['from'], 0, 3) != "tpr" || !ctype_xdigit(substr($_GET['from'], -32))) {
            die("Invalid from URL");
        }

        if (substr($_GET['to'], 0, 3) != "tpr" || !ctype_xdigit(substr($_GET['to'], -32))) {
            die("Invalid to URL");
        }

        $fromFile = tempnam('/tmp/', 'MONDIFF');
        $toFile = tempnam('/tmp/', 'MONDIFF');

        $this->downloadFile("http://blzddist1-a.akamaihd.net/" . $_GET['from'], $fromFile);
        $this->downloadFile("http://blzddist1-a.akamaihd.net/" . $_GET['to'], $toFile);

        $diff = $this->getDiff($fromFile, $toFile);
        $parsedDiffs = $this->getParsedDiff($fromFile, $toFile);

        unlink($fromFile);
        unlink($toFile);
        
        return new Response($this->render('monitor/diff.html.twig', [
            'diff' => $diff,
            'parsedDiffs' => $parsedDiffs
        ]));
    }
    
    private function hasCDNDir($product)
    {
        $q = $this->pdo->prepare("SELECT cdndir FROM ngdp_products WHERE program = ?");
        $q->execute([$product]);

        if (empty($q->fetch())) {
            return false;
        } else {
            return true;
        }
    }

    private function buildURL($product, $type, $value)
    {
        $cdn = "http://blzddist1-a.akamaihd.net/";
        $q = $this->pdo->prepare("SELECT cdndir FROM ngdp_products WHERE program = ?");
        $q->execute([$product]);
        $cdndir = $q->fetch()['cdndir'];

        if($type == "tpr/configs/data"){
            return $cdn . $type . "/" . $value[0] . $value[1] . "/" . $value[2] . $value[3] . "/" . $value;
        }

        if (empty($cdndir) || empty($value)) {
            return false;
        } else {
            if ($type == "config") {
                return $cdn . $cdndir . "/config/" . $value[0] . $value[1] . "/" . $value[2] . $value[3] . "/" . $value;
            } elseif ($type == "data") {
                return $cdn . $cdndir . "/data/" . $value[0] . $value[1] . "/" . $value[2] . $value[3] . "/" . $value;
            }
        }
    }

    private function downloadFile($url, $out)
    {
        $fileHandle = fopen($out, 'w+');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $fileHandle);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $exec = curl_exec($curl);
        curl_close($curl);
        fclose($fileHandle);

        if ($exec) {
            return true;
        } else {
            return false;
        }
    }

    private function getDiff($fromFile, $toFile)
    {
        $cmd = "diff -u " . escapeshellarg($fromFile) . " " . escapeshellarg($toFile);
        $result = shell_exec($cmd);

        return $result;
    }

    private function getParsedDiff($fromFile, $toFile)
    {
        $fromFileContent = file_get_contents($fromFile);
        $toFileContent = file_get_contents($toFile);

        switch (substr($fromFileContent, 0, 5)) {
            case "# Bui":
            case "# CDN":
            case "# Pat":
                $from = parseConfig($fromFile);
                if (!empty($from['archives'])) {
                    $from['archives'] = array_fill_keys(explode(" ", $from['archives']), '');
                }

                if (!empty($from['patch-archives'])) {
                    $from['patch-archives'] = array_fill_keys(explode(" ", $from['patch-archives']), '');
                }

                if (!empty($from['archives-index-size'])) {
                    $from['archives-index-size'] = explode(" ", $from['archives-index-size']);
                }

                if (!empty($from['patch-archives-index-size'])) {
                    $from['patch-archives-index-size'] = explode(" ", $from['patch-archives-index-size']);
                }

                unset($from['original-filename']);
                break;
        }

        switch (substr($toFileContent, 0, 5)) {
            case "# Bui":
            case "# CDN":
            case "# Pat":
                $to = parseConfig($toFile);
                if (!empty($to['archives'])) {
                    $to['archives'] = array_fill_keys(explode(" ", $to['archives']), '');
                }

                if (!empty($to['patch-archives'])) {
                    $to['patch-archives'] = array_fill_keys(explode(" ", $to['patch-archives']), '');
                }

                if (!empty($to['archives-index-size'])) {
                    $to['archives-index-size'] = explode(" ", $to['archives-index-size']);
                }

                if (!empty($to['patch-archives-index-size'])) {
                    $to['patch-archives-index-size'] = explode(" ", $to['patch-archives-index-size']);
                }

                unset($to['original-filename']);
                break;
        }

        if (empty($from)) {
            $from = json_decode($fromFileContent, true);
        }

        if (empty($to)) {
            $to = json_decode($toFileContent, true);
        }

        if (!$from || !$to) {
            $diffs = "Unsupported";
        } else {
            $diffs = \CompareArrays::Diff($from, $to);
            if (!empty($diffs)) {
                $diffs = \CompareArrays::Flatten($diffs);
            }
        }

        return $diffs;
    }
    
}