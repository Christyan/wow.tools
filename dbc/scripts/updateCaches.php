<?php

if (php_sapi_name() != "cli") {
    die("This script cannot be run outside of CLI.");
}
require_once(__DIR__ . "/../../inc/config.php");

$fullrun = false;
if (!empty($argv[1]) && $argv[1] == "fullrun") {
    echo "Full run, reimporting all caches!\n";
    $fullrun = true;
}

$filter = "";
if (!empty($argv[2])) {
    $filter = $argv[2];
}

$processedMD5s = $pdo->query("SELECT DISTINCT(md5) FROM wow_hotfixes_parsed")->fetchAll(PDO::FETCH_COLUMN);
$insertMD5 = $pdo->prepare("INSERT IGNORE INTO wow_hotfixes_parsed (md5) VALUES (?)");

$files = glob('/mnt/wowtools/archivetools/cache/*.wdb');
foreach ($files as $file) {
    // Only process hotfixes newer than 6 hours ago
    if (!$fullrun && filemtime($file) < strtotime("-2 hours")) {
        continue;
    }

    if ($fullrun && !empty($filter)) {
        if (strpos($file, $filter) === false) {
            echo "Skipping " . $file . ", does not match \"" . $filter . "\" filter\n";
            continue;
        }
    }

    $md5 = md5_file($file);
    if (!$fullrun && in_array($md5, $processedMD5s)) {
        continue;
    }

    echo "[Cache updater] [" . date("Y-m-d H:i:s") . "] Reading " . $file . "\n";
    $output = shell_exec("cd /home/wowtools/backend/WoWTools.WDBUpdater/bin/Release/net7.0/; dotnet WoWTools.WDBUpdater.dll " . escapeshellarg($file) . " mysql onlyretail");
    // if (!$fullrun && $output != null && substr($output, -34) != "New entries: 0\nUpdated entries: 0\n") {
    //     // echo "[Cache updater] [" . date("Y-m-d H:i:s") . "] " . substr($output, -34);
    //     //telegramSendMessage($output);
    // }
    echo "[Cache updater] [" . date("Y-m-d H:i:s") . "] " . $output . "\n";

    if (!$fullrun && !in_array($md5, $processedMD5s)) {
        $insertMD5->execute([$md5]);
        echo "[Cache updater] [" . date("Y-m-d H:i:s") . "] Inserted " . $md5 . " as processed cache\n";
        $processedMD5s[] = $md5;
    }
}
