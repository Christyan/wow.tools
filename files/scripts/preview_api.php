<?php

require_once("../../inc/config.php");

global $twig, $pdo;

if (empty($_GET['buildconfig']) || empty($_GET['filedataid'])) {
    die("Not enough information!");
}

$staticBuild = trim(file_get_contents(WORK_DIR . "/casc/extract/lastextractedroot.txt"));

if(empty($_GET['buildconfig']) || $_GET['buildconfig'] == "undefined"){
    $selectBuildFilterQ = $pdo->prepare("SELECT `hash` FROM wow_buildconfig WHERE root_cdn = ? GROUP BY root ORDER BY id ASC");
    $selectBuildFilterQ->execute([$staticBuild]);
    $_GET['buildconfig'] = $selectBuildFilterQ->fetchColumn();
}

$build = getVersionByBuildConfigHash($_GET['buildconfig'], "wow");

if (empty($build)) {
    die("Invalid build!");
}

$_GET['filedataid'] = (int)$_GET['filedataid'];

$q2 = $pdo->prepare("SELECT id, filename, type FROM wow_rootfiles WHERE id = :id");
$q2->bindParam(":id", $_GET['filedataid'], PDO::PARAM_INT);
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

print $twig->render('files/api_filepreview.html.twig', [
    'build' => $build,
    'previewURL' => $previewURL,
    'filedataid' => @$_GET['filedataid'],
    'type' => $type,
    'output' => $output
]);
