<?php
if (php_sapi_name() != "cli") {
    die("This script cannot be run outside of CLI.");
}
require_once(__DIR__ . "/../../inc/config.php");

$processedMD5s = [];

$files = glob(BACKEND_BASE_DIR . 'dbcdumphost/caches/*.bin');
foreach ($files as $file) {
    $md5 = md5_file($file);
    if (in_array($md5, $processedMD5s)) {
        echo $file . " has MD5 " . $md5 . " that was already found!\n";
        rename($file, str_replace(BACKEND_BASE_DIR . "/dbcdumphost/caches/", BACKEND_BASE_DIR . "/dbcdumphost/dupecaches/", $file));
        continue;
    }

    $processedMD5s[] = $md5;
}
