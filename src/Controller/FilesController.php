<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Repository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/files')]
class FilesController extends BaseController
{

    #[Route('/', name: 'files_index')]
    public function indexAction()
    {
        $buildq = $this->pdo->prepare("SELECT hash, description FROM `wow_buildconfig` WHERE product = ? ORDER BY id DESC LIMIT 1;");
        $lfproducts = array("wow", "wowt", "wow_classic", "wow_classic_era", "wow_classic_era_ptr");
        $lfbuilds = [];
        foreach ($lfproducts as $lfproduct) {
            $buildq->execute([$lfproduct]);
            $lfbuilds[$lfproduct] = $buildq->fetch(\PDO::FETCH_ASSOC);
        }
        
        return new Response($this->render('files/index.html.twig', [
            'buildfilterid' => @$_SESSION['buildfilterid'],
            'builds' => $this->pdo->query("SELECT description, root_cdn FROM wow_buildconfig ORDER BY wow_buildconfig.description DESC")->fetchAll(),
        ]));
    }

    #[Route('/api', name: 'files_api')]
    public function fileApiAction()
    {
        global $memcached; // TODO
        
        if (!empty($_GET['tree']) && isset($_GET['depth'])) {
            $treeQ = $this->pdo->prepare("SELECT DISTINCT(SUBSTRING_INDEX(filename, '/', :depth)) as entry, filename FROM wow_rootfiles WHERE filename LIKE :start AND filename LIKE :filter GROUP BY entry ASC");

            $treeQ->bindParam(":depth", $_GET['depth']);

            if (empty($_GET['start'])) {
                $treeQ->bindValue(":start", "%");
            } else {
                $treeQ->bindValue(":start", $_GET['start'] . "/%");
            }

            if (empty($_GET['filter'])) {
                $treeQ->bindValue(":filter", "%");
            } else {
                $treeQ->bindValue(":filter", "%" . $_GET['filter'] . "%");
            }

            $treeQ->execute();
            echo json_encode($treeQ->fetchAll(\PDO::FETCH_ASSOC));
            die();
        }

        $profiling = false;
        if ($profiling) {
            $this->pdo->exec('set @@session.profiling_history_size = 300;');
            $this->pdo->exec('set profiling=1');
            $returndata['profiletimings'][] = microtime(true);
        }

        if (!isset($_SESSION)) {
            // session_start();
        }

        if (!empty($_GET['src']) && $_GET['src'] == "mv") {
            $mv = true;
        } else {
            $mv = false;
        }

        if (!empty($_GET['src']) && $_GET['src'] == "dbc") {
            $dbc = true;
        } else {
            $dbc = false;
        }

        $keys = array();
        $tactq = $this->pdo->query("SELECT id, keyname, keybytes FROM wow_tactkey");
        while ($tactrow = $tactq->fetch()) {
            $keys[$tactrow['keyname']] = $tactrow['keybytes'];
        }

        if (isset($_GET['switchbuild'])) {
            if (empty($_GET['switchbuild'])) {
                session_start();
                $_SESSION['buildfilterid'] = null;
                session_write_close();
                return;
            } else {
                if (strlen($_GET['switchbuild']) != 32 || !ctype_xdigit($_GET['switchbuild'])) {
                    die("Invalid contenthash!");
                }
                $selectBuildFilterQ = $this->pdo->prepare("SELECT id FROM wow_buildconfig WHERE root_cdn = ? GROUP BY root ORDER BY id ASC");
                $selectBuildFilterQ->execute([$_GET['switchbuild']]);
                $filteredBuildID = $selectBuildFilterQ->fetchColumn();
                if (!empty($filteredBuildID)) {
                    session_start();
                    $_SESSION['buildfilterid'] = $filteredBuildID;
                    session_write_close();
                }
            }
            die();
        }

        $query = "FROM wow_rootfiles ";


        $joinparams = [];
        $clauseparams = [];
        $clauses = [];
        $joins = [];

        if (!empty($_SESSION['buildfilterid']) && !$mv && !$dbc) {
            $query .= "JOIN wow_rootfiles_builds_erorus ON ORD(MID(wow_rootfiles_builds_erorus.files, 1 + FLOOR(wow_rootfiles.id / 8), 1)) & (1 << (wow_rootfiles.id % 8)) ";
            array_push($clauses, " wow_rootfiles_builds_erorus.build = ? ");
            $clauseparams[] = $_SESSION['buildfilterid'];
        }

        $staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

        if($mv || (!empty($_SESSION['user']) && $_SESSION['user'] == "marlamin")){
            $selectBuildFilterQ = $this->pdo->prepare("SELECT id FROM wow_buildconfig WHERE root_cdn = ? GROUP BY root ORDER BY id ASC");
            $selectBuildFilterQ->execute([$staticBuild]);
            $filteredBuildID = $selectBuildFilterQ->fetchColumn();

            $query .= "JOIN wow_rootfiles_builds_erorus ON ORD(MID(wow_rootfiles_builds_erorus.files, 1 + FLOOR(wow_rootfiles.id / 8), 1)) & (1 << (wow_rootfiles.id % 8)) ";
            array_push($clauses, " wow_rootfiles_builds_erorus.build = ? ");
            $clauseparams[] = $filteredBuildID;
        }

        if (!empty($_GET['search']['value'])) {
            $criteria = array_filter(explode(",", $_GET['search']['value']), 'strlen');

            $i = 0;
            foreach ($criteria as &$c) {
                $c = strtolower($c);
                if ($c == "unnamed") {
                    array_push($clauses, " (wow_rootfiles.filename IS NULL) ");
                } elseif ($c == "communitynames") {
                    array_push($clauses, " (wow_rootfiles.filename IS NOT NULL AND verified = 0) ");
                } elseif ($c == "unverified") {
                    array_push($clauses, " (wow_rootfiles.lookup != '' AND verified = 0) ");
                } elseif ($c == "unshipped") {
                    array_push($clauses, " wow_rootfiles.id NOT IN (SELECT filedataid FROM wow_rootfiles_chashes) ");
                } elseif ($c == "encryptedbutnot") {
                    array_push($clauses, " wow_rootfiles.id IN (SELECT filedataid FROM wow_encryptedbutnot) ");
                } elseif ($c == "encrypted") {
                    if (in_array("unkkey", $criteria)) {
                        array_push($clauses, " wow_rootfiles.id IN (SELECT filedataid FROM wow_encrypted WHERE keyname NOT IN (SELECT keyname FROM wow_tactkey WHERE keybytes IS NOT NULL) AND active = 1) ");
                        unset($criteria[array_search("unkkey", $criteria)]);
                    } else if (in_array("haskey", $criteria)){
                        array_push($clauses, " wow_rootfiles.id IN (SELECT filedataid FROM wow_encrypted WHERE keyname IN (SELECT keyname FROM wow_tactkey WHERE keybytes IS NOT NULL) AND active = 1) ");
                        unset($criteria[array_search("haskey", $criteria)]);
                    } else {
                        array_push($clauses, " wow_rootfiles.id IN (SELECT filedataid FROM wow_encrypted WHERE active = 1) ");
                    }
                } elseif (substr($c, 0, 10) == "encrypted:") {
                    array_push($joins, " INNER JOIN wow_encrypted ON wow_rootfiles.id = wow_encrypted.filedataid AND keyname = ? ");
                    $joinparams[] = str_replace("encrypted:", "", $c);
                } elseif (substr($c, 0, 6) == "chash:") {
                    array_push($joins, " JOIN wow_rootfiles_chashes ON wow_rootfiles_chashes.filedataid=wow_rootfiles.id AND contenthash = ? ");
                    $joinparams[] = str_replace("chash:", "", $c);
                } elseif (substr($c, 0, 5) == "fdid:") {
                    array_push($clauses, " (wow_rootfiles.id = ?) ");
                    $clauseparams[] = str_replace("fdid:", "", $c);
                } elseif (substr($c, 0, 5) == "type:") {
                    array_push($clauses, " (type = ?) ");
                    $clauseparams[] = str_replace("type:", "", $c);
                } elseif (substr($c, 0, 5) == "skit:") {
                    array_push($joins, " INNER JOIN `wowdata`.soundkitentry ON `wowdata`.soundkitentry.id=wow_rootfiles.id AND `wowdata`.soundkitentry.entry = ? ");
                    $joinparams[] = str_replace("skit:", "", $c);
                } elseif (substr($c, 0, 6) == "range:") {
                    $explRange = explode("-", str_replace("range:", "", $c));
                    if (count($explRange) == 2) {
                        array_push($clauses, " (wow_rootfiles.ID BETWEEN ? AND ?) ");
                        $clauseparams[] = $explRange[0];
                        $clauseparams[] = $explRange[1];
                    }
                } else if (substr($c, 0, 3) == "vo:") {
                    array_push($joins, " INNER JOIN `wowdata`.soundkitentry ON `wowdata`.soundkitentry.id=wow_rootfiles.id AND `wowdata`.soundkitentry.entry IN (SELECT COALESCE(NULLIF(SoundKit0, 0), NULLIF(SoundKit1, 0)) AS value FROM `wowdata`.broadcasttext WHERE Text LIKE ? OR Text1 LIKE ?)");
                    $joinparams[] = "%" . str_replace("vo:", "", $c) . "%";
                    $joinparams[] = "%" . str_replace("vo:", "", $c) . "%";
                } else {
                    // Point slashes the correct way :)
                    $c = trim($c);
                    $subquery = "";

                    $search = "";
                    if (!empty($c)) {
                        if ($c[0] != '^') {
                            $search .= "%";
                        }

                        $search .= str_replace(["^","$"], "", $c);

                        if ($c[strlen($c) - 1] != '$') {
                            $search .= "%";
                        }
                    }

                    if ($mv) {
                        $subquery = "wow_rootfiles.id = ?";
                        $clauseparams[] = $c . "%";
                        $types = array();
                        if ($_GET['showADT'] == "true") {
                            $types[] = "adt";
                        }
                        if ($_GET['showWMO'] == "true") {
                            $types[] = "wmo";
                        }
                        if ($_GET['showM2'] == "true") {
                            $types[] = "m2";
                        }
                        if (!empty($c)) {
                            $subquery .= " OR wow_rootfiles.filename LIKE ? AND type IN ('" . implode("','", $types) . "')";
                            $clauseparams[] = $search;
                        } else {
                            $subquery .= " OR type IN ('" . implode("','", $types) . "')";
                        }
                        if (!empty($c) && $_GET['showWMO'] == "true") {
                            $subquery .= " AND wow_rootfiles.filename IS NOT NULL AND wow_rootfiles.filename NOT LIKE '%_lod1.wmo' AND wow_rootfiles.filename NOT LIKE '%_lod2.wmo'";
                        }
                        if ($_GET['showADT'] == "true") {
                            $subquery .= " AND wow_rootfiles.filename NOT LIKE '%_obj0.adt' AND wow_rootfiles.filename NOT LIKE '%_obj1.adt' AND wow_rootfiles.filename NOT LIKE '%_tex0.adt' AND wow_rootfiles.filename NOT LIKE '%_tex1.adt' AND wow_rootfiles.filename NOT LIKE '%_lod.adt'";
                        }

                        array_push($clauses, " (" . $subquery . ")");
                    } elseif ($dbc) {
                        array_push($clauses, " (wow_rootfiles.filename LIKE ? AND type = 'db2')");
                        $clauseparams[] = $search;
                    } else {
                        $clauseparams[] = $search;
                        $clauseparams[] = $search;
                        $clauseparams[] = $search;
                        array_push($clauses, " (wow_rootfiles.id LIKE ? OR lookup LIKE ? OR wow_rootfiles.filename LIKE ?) ");
                    }
                }
                $i++;
            }
        } else {
            if ($mv) {
                $types = array();
                if ($_GET['showADT'] == "true") {
                    $types[] = "adt";
                }
                if ($_GET['showWMO'] == "true") {
                    $types[] = "wmo";
                }
                if ($_GET['showM2'] == "true") {
                    $types[] = "m2";
                }

                if(!empty($clauses)){
                    $query .= " AND type IN ('" . implode("','", $types) . "')";
                }else{
                    $query .= " WHERE type IN ('" . implode("','", $types) . "')";
                }
                if (!empty($_GET['search']['value']) && $_GET['showWMO'] == "true") {
                    $query .= " AND wow_rootfiles.filename NOT LIKE '%_lod1.wmo' AND wow_rootfiles.filename NOT LIKE '%_lod2.wmo'";
                }
                if ($_GET['showADT'] == "true") {
                    $query .= " AND wow_rootfiles.filename NOT LIKE '%_obj0.adt' AND wow_rootfiles.filename NOT LIKE '%_obj1.adt' AND wow_rootfiles.filename NOT LIKE '%_tex0.adt' AND wow_rootfiles.filename NOT LIKE '%_tex1.adt' AND wow_rootfiles.filename NOT LIKE '%_lod.adt'";
                }
            }
        }

        $query .= implode(" ", $joins);
        if (count($clauses) > 0) {
            $query .= " WHERE " . implode(" AND ", $clauses);
        }

        $orderby = '';
        if (!empty($_GET['order'])) {
            $orderby .= " ORDER BY ";
            switch ($_GET['order'][0]['column']) {
                case 0:
                    $orderby .= "wow_rootfiles.id";
                    break;
                case 1:
                    $orderby .= "wow_rootfiles.filename";
                    break;
                case 2:
                    $orderby .= "wow_rootfiles.lookup";
                    break;
                case 3:
                    $orderby .= "wow_rootfiles.firstseen";
                    break;
                case 4:
                    $orderby .= "wow_rootfiles.type";
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

        $params = array_merge($joinparams, $clauseparams);

        try {
            $qmd5 = md5($query . implode('|', $params));
            $returndata['rfq'] = $query;
            if (!($returndata['recordsFiltered'] = $memcached->get("query." . $qmd5))) {
                $returndata['rfcachehit'] = false;
                $numrowsq = $this->pdo->prepare("SELECT COUNT(wow_rootfiles.id) " . $query);
                $numrowsq->execute($params);
                $returndata['recordsFiltered'] = (int)$numrowsq->fetchColumn();
                if (!$memcached->set("query." . $qmd5, $returndata['recordsFiltered'])) {
                    $returndata['mc1error'] = $memcached->getResultMessage();
                }
            } else {
                $returndata['rfcachehit'] = true;
            }

            $returndata['fullq'] = "SELECT wow_rootfiles.* " . $query . $orderby . " LIMIT " . $start . ", " . $length;
            $dataq = $this->pdo->prepare("SELECT wow_rootfiles.* " . $query . $orderby . " LIMIT " . $start . ", " . $length);
            $dataq->execute($params);
        } catch (Exception $e) {
            $returndata['data'] = [];
            $returndata['query'] = $query;
            $returndata['params'] = $params;
            $returndata['error'] = "I'm currently working on this functionality right now and broke it. Hopefully back soon. <3";
            echo json_encode($returndata);
            die();
        }


        if (empty($_GET['draw'])) {
            http_response_code(400);
            die();
        }

        $returndata['draw'] = (int)$_GET['draw'];

        if (!($returndata['recordsTotal'] = $memcached->get("files.total"))) {
            $returndata['rtcachehit'] = false;
            $returndata['recordsTotal'] = $this->pdo->query("SELECT count(id) FROM wow_rootfiles")->fetchColumn();
            if (!$memcached->set("files.total", $returndata['recordsTotal'])) {
                $returndata['mc2error'] = $memcached->getResultMessage();
            }
        } else {
            $returndata['rtcachehit'] = true;
        }

        $returndata['staticBuild'] = $staticBuild;
        $returndata['data'] = array();

        $staticBuildName = $this->pdo->prepare("SELECT description FROM wow_buildconfig WHERE root_cdn = ? ORDER BY id DESC LIMIT 1");
        $encq = $this->pdo->prepare("SELECT keyname FROM wow_encrypted WHERE filedataid = ? AND active = 1");
        $badlyencq = $this->pdo->prepare("SELECT filedataid FROM wow_encryptedbutnot WHERE filedataid = ?");

        if (!$mv && !$dbc) {
            $soundkitq = $this->pdo->prepare("SELECT soundkitentry.id as id, soundkitentry.entry as entry, soundkitname.name as name FROM `wowdata`.soundkitentry LEFT JOIN `wowdata`.soundkitname ON soundkitentry.entry=`wowdata`.soundkitname.id WHERE soundkitentry.id = ?");
            $cmdq = $this->pdo->prepare("SELECT id FROM `wowdata`.creaturemodeldata WHERE filedataid = ?");
            $mfdq = $this->pdo->prepare("SELECT ModelResourcesID FROM `wowdata`.modelfiledata WHERE FileDataID = ?");
            $tfdq = $this->pdo->prepare("SELECT MaterialResourcesID FROM `wowdata`.texturefiledata WHERE FileDataID = ?");
            $commentq = $this->pdo->prepare("SELECT comment, lastedited, users.username as username FROM wow_rootfiles_comments INNER JOIN users ON wow_rootfiles_comments.lasteditedby=users.id WHERE filedataid = ?");
            $bctxtq = $this->pdo->prepare("SELECT `Text`,Text1 FROM `wowdata`.broadcasttext WHERE SoundKit0 = ? OR SoundKit1 = ?");
        }

        $cdnq = $this->pdo->prepare("SELECT cdnconfig FROM wow_versions WHERE buildconfig = ?");
        $subq = $this->pdo->prepare("SELECT wow_rootfiles_chashes.root_cdn, wow_rootfiles_chashes.contenthash, wow_buildconfig.hash as buildconfig, wow_buildconfig.description FROM wow_rootfiles_chashes LEFT JOIN wow_buildconfig on wow_buildconfig.root_cdn=wow_rootfiles_chashes.root_cdn WHERE filedataid = ? ORDER BY wow_buildconfig.description ASC");
        $staticBuildName->execute([$staticBuild]);
        $buildName = $staticBuildName->fetch()['description'];
        $returndata['staticBuildName'] = parseBuildName($buildName)['full'];
        while ($row = $dataq->fetch()) {
            $contenthashes = array();
            $cfname = "";
            if ($row['verified'] == 0) {
                $cfname = $row['filename'];
                $row['filename'] = null;
            }
            if (!$mv && !$dbc) {
                // enc 0 = not encrypted, enc 1 = encrypted, unknown key, enc 2 = encrypted, known key, enc 3 = encrypted with multiple keys, some known, enc 4 = supposed to be encrypted but alas
                $encq->execute([$row['id']]);

                $encryptedKeyCount = 0;
                $encryptedAvailableKeys = 0;
                $enc = 0;
                $usedkeys = [];
                foreach ($encq->fetchAll(\PDO::FETCH_ASSOC) as $encr) {
                    $encryptedKeyCount++;
                    $usedkeys[] = $encr['keyname'];
                    if (array_key_exists($encr['keyname'], $keys)) {
                        if (!empty($keys[$encr['keyname']])) {
                            $encryptedAvailableKeys++;
                        }
                    }
                }

                if ($encryptedKeyCount > 0) {
                    if ($encryptedKeyCount == $encryptedAvailableKeys) {
                        $enc = 2;
                    } else {
                        if ($encryptedKeyCount > 1 && $encryptedAvailableKeys > 0) {
                            $enc = 3;
                        } else {
                            $enc = 1;
                        }
                    }
                }

                $badlyencq->execute([$row['id']]);
                if(!empty($badlyencq->fetch())){
                    $enc = 4;
                }

                /* CROSS REFERENCES */
                $xrefs = array();

                // SoundKit
                $soundkitq->execute([$row['id']]);
                $soundkits = $soundkitq->fetchAll();
                if (count($soundkits) > 0) {
                    $usedKits = [];
                    foreach ($soundkits as $soundkitrow) {
                        $kitDesc = $soundkitrow['entry'];
                        if (!empty($soundkitrow['name'])) {
                            $kitDesc .= " (" . htmlentities($soundkitrow['name'], ENT_QUOTES) . ")";
                        }

                        $bctxtq->execute([$soundkitrow['entry'], $soundkitrow['entry']]);
                        $bctxts = $bctxtq->fetchAll();
                        if (count($bctxts) > 0) {
                            if (!empty($bctxts[0]['Text'])) {
                                $kitDesc .= ": " . htmlentities($bctxts[0]['Text'], ENT_QUOTES);
                            } else if (!empty($bctxts[0]['Text1'])) {
                                $kitDesc .= ": " . htmlentities($bctxts[0]['Text1'], ENT_QUOTES);
                            }
                        }
                        $usedKits[] = $kitDesc;
                    }

                    $xrefs['soundkit'] = "<b>Part of SoundKit(s):</b> " . implode(", ", $usedKits);
                }

                // Creature Model Data
                $cmdq->execute([$row['id']]);
                $cmdr = $cmdq->fetch();
                if (!empty($cmdr)) {
                    $xrefs['cmd'] = "<b>CreatureModelData ID:</b> " . $cmdr['id'] . "<br>";
                }

                // TextureFileData
                $tfdq->execute([$row['id']]);
                $tfdr = $tfdq->fetch();
                if (!empty($tfdr)) {
                    $xrefs['tfd'] = "<b>MaterialResourcesID:</b> " . $tfdr['MaterialResourcesID'] . "<br>";
                }

                // ModelFileData
                $mfdq->execute([$row['id']]);
                $mfdr = $mfdq->fetch();
                if (!empty($mfdr)) {
                    $xrefs['mfd'] = "<b>ModelResourcesID:</b> " . $mfdr['ModelResourcesID'] . "<br>";
                }

                // Comments
                $commentq->execute([$row['id']]);
                $comments = $commentq->fetchAll();
                if (count($comments) > 0) {
                    for ($i = 0; $i < count($comments); $i++) {
                        $comments[$i]['username'] = htmlentities($comments[$i]['username'], ENT_QUOTES);
                        $comments[$i]['comment'] = htmlentities($comments[$i]['comment'], ENT_QUOTES);
                    }
                } else {
                    $comments = "";
                }
            } else {
                $enc = 0;
                $xrefs = array();
                $comments = "";
            }

            $versions = array();

            //if($staticBuild){

            if(file_exists(WORK_DIR . "/casc/extract/" . $staticBuild . "/" . $row['id'])){
                $subrow = array();
                $subrow['description'] = $returndata['staticBuildName'];
                $subrow['enc'] = $enc;
                $versions[] = $subrow;
            }
            /*}else{
                $subq->execute([$row['id']]);
        
                foreach ($subq->fetchAll() as $subrow) {
                    $cdnq->execute([$subrow['buildconfig']]);
                    $subrow['cdnconfig'] = $cdnq->fetchColumn();
        
                    if (in_array($subrow['contenthash'], $contenthashes)) {
                        continue;
                    } else {
                        $contenthashes[] = $subrow['contenthash'];
                    }
        
                    $subrow['enc'] = $enc;
                    if ($enc > 0) {
                        $subrow['key'] = implode(", ", $usedkeys);
                    }
        
                    // Mention firstseen if it is from first casc build
                    if ($subrow['description'] == "WOW-18125patch6.0.1_Beta") {
                        $subrow['firstseen'] = $row['firstseen'];
                    }
        
                    $parsedBuild = parseBuildName($subrow['description']);
        
                    $subrow['description'] = $parsedBuild['full'];
                    $subrow['branch'] = $parsedBuild['branch'];
                    
                    $versions[] = $subrow;
                }
            }*/


            $returndata['data'][] = array($row['id'], $row['filename'], $row['lookup'], array_reverse($versions), $row['type'], $xrefs, $comments, $cfname);
        }

        if ($profiling) {
            $profileq = $this->pdo->query('show profiles');
            $returndata['profiling'] = $profileq->fetchAll(\PDO::FETCH_ASSOC);
            $totalDuration = 0;
            foreach ($returndata['profiling'] as $profile) {
                $totalDuration += $profile['Duration'];
            }
            $returndata['profiletotalquerytime'] = $totalDuration;
            $returndata['profiletimings'][] = microtime(true);
            $this->pdo->exec('set profiling=0');
        }

        return new JsonResponse($returndata);
    }

    #[Route('/api/filedata', name: 'files_api_file_data')]
    public function fileDataApiAction()
    {
        if (!empty($_GET['filedataid'])) {
            $q = $this->pdo->prepare("SELECT * FROM wow_rootfiles WHERE id = :id");
            $q->bindParam(":id", $_GET['filedataid'], \PDO::PARAM_INT);
            $q->execute();
            $row = $q->fetch();

            if (!empty($_GET['filename']) && $_GET['filename'] == 1) {
                $exploded = explode(",", $_GET['filedataid']);
                if (count($exploded) == 1) {
                    if (!empty($row['filename'])) {
                        echo $row['filename'];
                    }
                } else {
                    for ($i = 0; $i < count($exploded); $i++) {
                        $q->bindParam(":id", $exploded[$i], \PDO::PARAM_INT);
                        $q->execute();
                        $row = $q->fetch();
                        if (!empty($row['filename'])) {
                            echo $row['filename'];
                            die();
                        }
                    }
                }
                die();
            }

            if (empty($row)) {
                die("Could not find file!");
            }

            if (!empty($_GET['lookup']) && $_GET['lookup'] == 1) {
                echo $row['lookup'];
                die();
            }

            $contenthashes = array();
            $subq = $this->pdo->prepare("SELECT wow_rootfiles_chashes.root_cdn, wow_rootfiles_chashes.contenthash, wow_rootfiles_sizes.size, wow_buildconfig.hash as buildconfig, wow_buildconfig.description FROM wow_rootfiles_chashes LEFT JOIN wow_buildconfig on wow_buildconfig.root_cdn=wow_rootfiles_chashes.root_cdn LEFT OUTER JOIN wow_rootfiles_sizes on wow_rootfiles_sizes.contenthash=wow_rootfiles_chashes.contenthash WHERE filedataid = :id ORDER BY wow_buildconfig.description DESC");
            $subq->bindParam(":id", $row['id'], \PDO::PARAM_INT);
            $subq->execute();
            $versions = array();
            $prevcontenthash = '';

            while ($subrow = $subq->fetch()) {
                if (in_array($subrow['contenthash'], $contenthashes)) {
                    continue;
                } else {
                    $contenthashes[] = $subrow['contenthash'];
                }
                if ($subrow['contenthash'] == $prevcontenthash) {
                    continue;
                }

                if (empty($subrow['size'])) {
                    $subrow['size'] = 0;
                }

                $versions[] = $subrow;
            }

            $returndata = array("filedataid" => $row['id'], "filename" => $row['filename'], "lookup" => $row['lookup'], "versions" => $versions, "type" => $row['type']);
            $staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

            if ($returndata['type'] == "ogg" || $returndata['type'] == "mp3") {
                $soundkitq = $this->pdo->prepare("SELECT soundkitentry.entry as entry, soundkitname.name as name FROM `wowdata`.soundkitentry INNER JOIN `wowdata`.soundkitname ON soundkitentry.entry=`wowdata`.soundkitname.id WHERE soundkitentry.id = :id");
                $soundkitq->bindParam(":id", $returndata['filedataid']);
                $soundkitq->execute();
                $soundkits = $soundkitq->fetchAll();
            }

            $eq = $this->pdo->prepare("SELECT wow_tactkey.id, wow_encrypted.keyname, wow_tactkey.description, wow_tactkey.keybytes, wow_encrypted.active FROM wow_encrypted LEFT JOIN wow_tactkey ON wow_encrypted.keyname = wow_tactkey.keyname WHERE wow_encrypted.filedataid = :id");
            $eq->bindParam(":id", $returndata['filedataid']);
            $eq->execute();
            $eqr = $eq->fetchAll(\PDO::FETCH_ASSOC);

            $badlyencq = $this->pdo->prepare("SELECT filedataid FROM wow_encryptedbutnot WHERE filedataid = ?");
            $badlyencq->execute([$row['id']]);

            $nbq = $this->pdo->prepare("(SELECT * FROM wow_rootfiles WHERE id >= :id1 ORDER BY id ASC LIMIT 4) UNION (SELECT * FROM wow_rootfiles WHERE id < :id2 ORDER BY id DESC LIMIT 3) ORDER BY id ASC");
            $nbq->bindParam(":id1", $row['id']);
            $nbq->bindParam(":id2", $row['id']);
            $nbq->execute();
            $nbr = $nbq->fetchAll();

            $lq = $this->pdo->prepare("SELECT wow_rootfiles_links.*, wow_rootfiles.id, wow_rootfiles.filename, wow_rootfiles.type as filetype FROM wow_rootfiles_links INNER JOIN wow_rootfiles ON wow_rootfiles.id=wow_rootfiles_links.parent WHERE child = :id");
            $lq->bindParam(":id", $row['id']);
            $lq->execute();
            $parents = $lq->fetchAll();

            $bhashq = $this->pdo->prepare("SELECT hash FROM wow_buildconfig WHERE root_cdn IN (SELECT root_cdn FROM wow_rootfiles_chashes WHERE filedataid = ?) ORDER BY ID DESC LIMIT 1");
            $lq = $this->pdo->prepare("SELECT wow_rootfiles_links.*, wow_encrypted.keyname, wow_rootfiles.id, wow_rootfiles.filename, wow_rootfiles.type as filetype FROM wow_rootfiles_links INNER JOIN wow_rootfiles ON wow_rootfiles.id=wow_rootfiles_links.child LEFT OUTER JOIN wow_encrypted ON wow_rootfiles.id=wow_encrypted.filedataid WHERE parent = :id");
            $lq->bindParam(":id", $row['id']);
            $lq->execute();
            $children = $lq->fetchAll();

            foreach ($children as &$lrow) {
                if ($lrow['filetype'] == "blp") {
                    // select newest bc for this file, TODO: most recent one instead of random one
                    $bhashq->execute([$lrow['child']]);
                    $buildhashforchildres = $bhashq->fetch();
                    if (!empty($buildhashforchildres)) {
                        $buildhashforchild = $buildhashforchildres['hash'];
                    } else {
                        $buildhashforchild = $versions[0]['buildconfig'];
                    }
                    // check encryption
                    $eq->bindParam(":id", $lrow['child']);
                    $eq->execute();
                    $enc = 0;
                    foreach ($eq->fetchAll(PDO::FETCH_ASSOC) as $er) {
                        if (!empty($er['keybytes'])) {
                            $enc = 2;
                        } else {
                            $enc = 1;
                        }
                    }

                    $lrow['buildhashforchild'] = $buildhashforchild;
                    $lrow['enc'] = $enc;
                }
            }

            return new Response($this->render('files/api_filedata.html.twig', [
                'staticBuild' => $staticBuild,
                'returndata' => $returndata,
                'soundkits' => @$soundkits,
                'eqr' => $eqr,
                'row' => $row,
                'nbr' => $nbr,
                'versions' => $versions,
                'parents' => $parents,
                'children' => $children,
                'showEncryptDisclaimer' => !empty($badlyencq->fetch()),
                'fileExists' => file_exists(WORK_DIR . "/casc/extract/" . $staticBuild . "/" . $returndata['filedataid'])
            ]));
        }

        if (!empty($_GET['contenthash'])) {
            if (strlen($_GET['contenthash']) != 32 || !ctype_xdigit($_GET['contenthash'])) {
                die("Invalid contenthash!");
            }

            $chashq = $this->pdo->prepare("SELECT wow_rootfiles_chashes.filedataid, wow_rootfiles_chashes.root_cdn, wow_rootfiles.filename FROM wow_rootfiles_chashes JOIN wow_rootfiles ON wow_rootfiles.id = wow_rootfiles_chashes.filedataid WHERE contenthash = :contenthash ORDER BY filedataid ASC");
            $chashq->bindParam(":contenthash", $_GET['contenthash']);
            $chashq->execute();

            $chashes = $chashq->fetchAll();

            return new Response($this->render('files/api_contenthash.html.twig', [
                'chashes' => $chashes
            ]));
        }

        if (!empty($_GET['type']) && $_GET['type'] == "gettypes") {
            $types = array();
            foreach ($this->pdo->query("SELECT DISTINCT(TYPE) FROM wow_rootfiles") as $row) {
                $types[] = $row['TYPE'];
            }
            
            return new Response(implode(",", $types));
        }
    }

    #[Route('/api/preview', name: 'files_api_file_preview')]
    public function filePreviewAction()
    {
        if (empty($_GET['buildconfig']) || empty($_GET['filedataid'])) {
            die("Not enough information!");
        }

        $staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

        if(empty($_GET['buildconfig']) || $_GET['buildconfig'] == "undefined"){
            $selectBuildFilterQ = $this->pdo->prepare("SELECT `hash` FROM wow_buildconfig WHERE root_cdn = ? GROUP BY root ORDER BY id ASC");
            $selectBuildFilterQ->execute([$staticBuild]);
            $_GET['buildconfig'] = $selectBuildFilterQ->fetchColumn();
        }

        $build = getVersionByBuildConfigHash($_GET['buildconfig'], "wow");

        if (empty($build)) {
            die("Invalid build!");
        }

        $_GET['filedataid'] = (int)$_GET['filedataid'];

        $q2 = $this->pdo->prepare("SELECT id, filename, type FROM wow_rootfiles WHERE id = :id");
        $q2->bindParam(":id", $_GET['filedataid'], \PDO::PARAM_INT);
        $q2->execute();
        $row2 = $q2->fetch();
        if (empty($row2)) {
            die("File not found in database!");
        } else {
            $type = $row2['type'];
            $dbid = $row2['id'];
        }

        if (empty($row2['filename'])) {
            $row2['filename'] = $row2['id'] . "." . $type;
        }

        function downloadFile($params, $outfile)
        {
            $fp = fopen($outfile, 'w+');
            $url = 'http://localhost:5005/casc/file' . $params;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $exec = curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            if ($exec) {
                return true;
            } else {
                return false;
            }
        }

        if (!empty($_GET['contenthash'])) {
            // If contenthash is available, use that for faster lookups
            $cascparams = "/chash?buildconfig=" . $build['buildconfig']['hash'] . "&cdnconfig=" . $build['cdnconfig']['hash'] . "&filename=" . urlencode($row2['filename']) . "&contenthash=" . $_GET['contenthash'];
        } else {
            // Otherwise, use filedataid
            $cascparams = "/fdid?buildconfig=" . $build['buildconfig']['hash'] . "&cdnconfig=" . $build['cdnconfig']['hash'] . "&filename=" . urlencode($row2['filename']) . "&filedataid=" . $_GET['filedataid'];
        }

        $previewURL = "/casc/extract/" . $staticBuild . "/" . $_GET['filedataid'];
        $output = '';

        if ($type != 'ogg' && $type != 'mp3' && $type != 'blp') {
            $tempfile = WORK_DIR . "/casc/extract/" . $staticBuild . "/" . $_GET['filedataid'];
            if ($type == "m2" || $type == "wmo") {
                // dump json
                $output = shell_exec("cd " . BACKEND_BASE_DIR . "/jsondump; /usr/bin/dotnet WoWJsonDumper.dll " . $type . " " . escapeshellarg($tempfile) . " 2>&1");
                $output = htmlentities($output);
            } elseif ($type == "xml" || $type == "xsd" || $type == "lua" || $type == "toc" || $type == "htm" || $type == "html" || $type == "sbt" || $type == "txt" || $type == "wtf") {
                $output = file_get_contents($tempfile);
                // $output = htmlentities($output);
            } else if ($type == "wwf") {
                $output = shell_exec("/usr/bin/tail -c +9 " . escapeshellarg($tempfile) . "");
                $output = addslashes($output);
            } else {
                // dump via hd
                // "Not a supported file for previews, dumping hex output (until 1MB).";
                $output = shell_exec("/usr/bin/hd -n1048576 " . escapeshellarg($tempfile));
                // $output = htmlentities($output);
            }
        }

        return new Response($this->render('files/api_filepreview.html.twig', [
            'build' => $build,
            'previewURL' => $previewURL,
            'filedataid' => @$_GET['filedataid'],
            'type' => $type,
            'output' => $output
        ]));
    }

    #[Route('/api/download', name: 'files_api_file_download')]
    public function fileDownnloadAction()
    {
        if(empty($_GET['build']) || empty($_GET['id'])) {
            die("Not enough parameters");
        }

        $staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

        if($_GET['build'] != $staticBuild)
            die("Invalid build, it might still be extracting, try again later");

        if(!is_numeric($_GET['id']))
            die("Invalid ID");

        $id = intval($_GET['id']);

        $fnameq = $this->pdo->prepare("SELECT `filename`, `type` FROM wow_rootfiles WHERE `id` = ?");
        $fnameq->execute([$id]);
        $file = $fnameq->fetch(\PDO::FETCH_ASSOC);

        if(empty($file))
            die("File not found");

        if (empty($file['filename'])) {
            $filename = $id . "." . $file['type'];
        } else {
            $filename = basename($file['filename']);
        }

        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile(WORK_DIR . "/casc/extract/" . $staticBuild . "/" . $id);
        
        exit;
    }
}