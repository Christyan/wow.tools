<?php

include("../inc/config.php");

global $twig, $pdo;

function getFileCount($root)
{
    if (!file_exists(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt")) {
        echo "	Dumping manifest..";
        $output = shell_exec("cd " . BACKEND_BASE_DIR . "/buildbackup; /usr/bin/dotnet " . BACKEND_BASE_DIR . "/buildbackup/BuildBackup.dll dumproot2 " . $root . " > " . BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt");
        echo "..done!\n";

        if(!file_exists(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt")){
            echo "	!!! Manifest missing, quitting..\n";
            die();
        }

        if(filesize(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt") == 0){
            echo "	!!! Manifest dump empty, removing and quitting..\n";
            unlink(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt");
            die();
        }
    }

    $fdids = [];

    if (($handle = fopen(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $fdids[] = $data[2];
        }
        fclose($handle);
    }

    return count($fdids);
}

$arr = $pdo->query("SELECT wow_versions.buildconfig, wow_versions.cdnconfig, wow_buildconfig.description, wow_buildconfig.root_cdn, wow_rootfiles_count.count FROM wow_versions LEFT OUTER JOIN wow_buildconfig ON wow_versions.buildconfig=wow_buildconfig.hash LEFT OUTER JOIN wow_rootfiles_count ON wow_rootfiles_count.root_cdn=wow_buildconfig.root_cdn ORDER BY wow_buildconfig.description DESC")->fetchAll();
foreach ($arr as &$build) {
    if (empty($build['count'])) {
        $build['count'] = getFileCount($build['root_cdn']);
        $iq = $pdo->prepare("INSERT IGNORE INTO wow_rootfiles_count (root_cdn, count) VALUES (?, ?)");
        $iq->execute([$build['root_cdn'], $build['count']]);
    }
}

$typeq  = $pdo->query("SELECT type, count(type) as typecount FROM wow_rootfiles GROUP BY type ORDER BY count(type) DESC");
$typeq2 = $pdo->query("SELECT type, count(type) as typecount FROM wow_rootfiles WHERE filename IS NULL GROUP BY type ORDER BY count(type) DESC");

print $twig->render('files/stats.html.twig', [
    'arr' => $arr,
    'types' => $typeq->fetchAll(),
    'types2' => $typeq2->fetchAll()
]);
