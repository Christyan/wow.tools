<?php

if (php_sapi_name() != "cli") {
    die("This script cannot be run outside of CLI.");
}

include(__DIR__ . "/../../inc/config.php");

if(empty($argv[1])){
    $product = "wow";
}else{
    $product = $argv[1];
}

$dbcFDIDMap = $pdo->query("SELECT REPLACE(REPLACE(`filename`, \"dbfilesclient/\", \"\"), \".db2\", \"\"), `id` FROM wow_rootfiles WHERE `filename` LIKE 'DBFilesClient/%.db2'")->fetchAll(PDO::FETCH_KEY_PAIR);
$dbcMap = $pdo->query("SELECT `id`, `name` FROM wow_dbc_tables ORDER BY id ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$versionMap = $pdo->query("SELECT `id`, `version` FROM wow_builds ORDER BY id ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$unknownTableVersions = $pdo->query("SELECT versionid, tableid FROM wow_dbc_table_versions WHERE contenthash IS NULL ORDER BY versionid DESC")->fetchAll(PDO::FETCH_ASSOC);
$setTableVersionMD5 = $pdo->prepare("UPDATE wow_dbc_table_versions SET contenthash = ? WHERE versionid = ? AND tableid = ?");
$selectRootByBuild = $pdo->prepare("SELECT `hash`, `root_cdn` FROM " . $product . "_buildconfig WHERE description LIKE ?");

$prevVersion = "";
$manifest = [];
$root = "";
$cascBuild = false;

if(count($unknownTableVersions) > 0){
    echo "Setting MD5s for " . count($unknownTableVersions) . " DB2s\n";
}

foreach($unknownTableVersions as $tableVersion){
    $version = $versionMap[$tableVersion['versionid']];
    if($prevVersion != $version){
        $buildEx = explode(".", $version);

        echo "Checking " . $version . "..\n";

        $selectRootByBuild->execute(["WOW-" . $buildEx[3] . "patch%"]);
        $build = $selectRootByBuild->fetch(PDO::FETCH_ASSOC);
        if(empty($build)){
            echo "Not a CASC build, MD5ing files on disk..\n";
            $cascBuild = false;
        }else{
            $cascBuild = true;
        }

        if($cascBuild){
            $buildconfig = $build['hash'];
            $root = $build['root_cdn'];

            $manifest = [];

            if(!file_exists(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt") || filesize(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt") == 0){
                echo "Dumping manifest..";
                $output = shell_exec("cd " . BACKEND_BASE_DIR . "/buildbackup; /usr/bin/dotnet " . BACKEND_BASE_DIR . "/buildbackup/BuildBackup.dll dumproot2 " . $root . " " . $product . " > " . BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt");
                echo "..done!\n";
            }
        
            echo "Parsing manifest " .$root . "\n";

            if (($handle = fopen(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt", "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $manifest[$data[2]] = $data[3];
                }
                fclose($handle);
            }

            echo "Setting MD5s\n";
        }
       
        $prevVersion = $version;
    }
    
    $filename = BACKEND_BASE_DIR . "/dbcs/" . $version . "/dbfilesclient/" . $dbcMap[$tableVersion['tableid']] . ".db2";
    if(file_exists($filename)){
        if($cascBuild){
            if(!empty($manifest[$dbcFDIDMap[$dbcMap[$tableVersion['tableid']]]])){
                $setTableVersionMD5->execute([$manifest[$dbcFDIDMap[$dbcMap[$tableVersion['tableid']]]], $tableVersion['versionid'], $tableVersion['tableid']]);
            }else{
                echo $dbcMap[$tableVersion['tableid']]. " missing in manifest\n";
            }
        }else{
            $setTableVersionMD5->execute([md5_file($filename), $tableVersion['versionid'], $tableVersion['tableid']]);
        }
    }else{
        $filename = BACKEND_BASE_DIR . "/dbcs/" . $version . "/dbfilesclient/" . $dbcMap[$tableVersion['tableid']] . ".dbc";
        if(file_exists($filename)){
             $setTableVersionMD5->execute([md5_file($filename), $tableVersion['versionid'], $tableVersion['tableid']]);
        }else{
            echo "!!! File " . $filename . " does not exist\n";
        }
    }
}