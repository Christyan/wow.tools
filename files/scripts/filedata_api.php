<?php

require_once("../../inc/config.php");
header("Access-Control-Allow-Origin: http://wow.tools.localhost");

global $twig, $pdo;

if (!empty($_GET['filedataid'])) {
    $q = $pdo->prepare("SELECT * FROM wow_rootfiles WHERE id = :id");
    $q->bindParam(":id", $_GET['filedataid'], PDO::PARAM_INT);
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
                $q->bindParam(":id", $exploded[$i], PDO::PARAM_INT);
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
    $subq = $pdo->prepare("SELECT wow_rootfiles_chashes.root_cdn, wow_rootfiles_chashes.contenthash, wow_rootfiles_sizes.size, wow_buildconfig.hash as buildconfig, wow_buildconfig.description FROM wow_rootfiles_chashes LEFT JOIN wow_buildconfig on wow_buildconfig.root_cdn=wow_rootfiles_chashes.root_cdn LEFT OUTER JOIN wow_rootfiles_sizes on wow_rootfiles_sizes.contenthash=wow_rootfiles_chashes.contenthash WHERE filedataid = :id ORDER BY wow_buildconfig.description DESC");
    $subq->bindParam(":id", $row['id'], PDO::PARAM_INT);
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
        $soundkitq = $pdo->prepare("SELECT soundkitentry.entry as entry, soundkitname.name as name FROM `wowdata`.soundkitentry INNER JOIN `wowdata`.soundkitname ON soundkitentry.entry=`wowdata`.soundkitname.id WHERE soundkitentry.id = :id");
        $soundkitq->bindParam(":id", $returndata['filedataid']);
        $soundkitq->execute();
        $soundkits = $soundkitq->fetchAll();
    }

    $eq = $pdo->prepare("SELECT wow_tactkey.id, wow_encrypted.keyname, wow_tactkey.description, wow_tactkey.keybytes, wow_encrypted.active FROM wow_encrypted LEFT JOIN wow_tactkey ON wow_encrypted.keyname = wow_tactkey.keyname WHERE wow_encrypted.filedataid = :id");
    $eq->bindParam(":id", $returndata['filedataid']);
    $eq->execute();
    $eqr = $eq->fetchAll(PDO::FETCH_ASSOC);

    $badlyencq = $pdo->prepare("SELECT filedataid FROM wow_encryptedbutnot WHERE filedataid = ?");
    $badlyencq->execute([$row['id']]);

    $nbq = $pdo->prepare("(SELECT * FROM wow_rootfiles WHERE id >= :id1 ORDER BY id ASC LIMIT 4) UNION (SELECT * FROM wow_rootfiles WHERE id < :id2 ORDER BY id DESC LIMIT 3) ORDER BY id ASC");
    $nbq->bindParam(":id1", $row['id']);
    $nbq->bindParam(":id2", $row['id']);
    $nbq->execute();
    $nbr = $nbq->fetchAll();

    $lq = $pdo->prepare("SELECT wow_rootfiles_links.*, wow_rootfiles.id, wow_rootfiles.filename, wow_rootfiles.type as filetype FROM wow_rootfiles_links INNER JOIN wow_rootfiles ON wow_rootfiles.id=wow_rootfiles_links.parent WHERE child = :id");
    $lq->bindParam(":id", $row['id']);
    $lq->execute();
    $parents = $lq->fetchAll();

    $bhashq = $pdo->prepare("SELECT hash FROM wow_buildconfig WHERE root_cdn IN (SELECT root_cdn FROM wow_rootfiles_chashes WHERE filedataid = ?) ORDER BY ID DESC LIMIT 1");
    $lq = $pdo->prepare("SELECT wow_rootfiles_links.*, wow_encrypted.keyname, wow_rootfiles.id, wow_rootfiles.filename, wow_rootfiles.type as filetype FROM wow_rootfiles_links INNER JOIN wow_rootfiles ON wow_rootfiles.id=wow_rootfiles_links.child LEFT OUTER JOIN wow_encrypted ON wow_rootfiles.id=wow_encrypted.filedataid WHERE parent = :id");
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
    
    print $twig->render('files/api_filedata.html.twig', [
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
    ]);
}

if (!empty($_GET['contenthash'])) {
    if (strlen($_GET['contenthash']) != 32 || !ctype_xdigit($_GET['contenthash'])) {
        die("Invalid contenthash!");
    }

    $chashq = $pdo->prepare("SELECT wow_rootfiles_chashes.filedataid, wow_rootfiles_chashes.root_cdn, wow_rootfiles.filename FROM wow_rootfiles_chashes JOIN wow_rootfiles ON wow_rootfiles.id = wow_rootfiles_chashes.filedataid WHERE contenthash = :contenthash ORDER BY filedataid ASC");
    $chashq->bindParam(":contenthash", $_GET['contenthash']);
    $chashq->execute();

    $chashes = $chashq->fetchAll();
    
    print $twig->render('files/api_contenthash.html.twig', [
        'chashes' => $chashes
    ]);
}

if (!empty($_GET['type']) && $_GET['type'] == "gettypes") {
    $types = array();
    foreach ($pdo->query("SELECT DISTINCT(TYPE) FROM wow_rootfiles") as $row) {
        $types[] = $row['TYPE'];
    }
    echo implode(",", $types);
}
