<?php

if (php_sapi_name() != "cli") {
    die("This script cannot be run outside of CLI.");
}

$useBuildBackup = true;

include(__DIR__ . "/../../inc/config.php");
while (true) {
    $uq = $pdo->prepare("UPDATE wow_rootfiles SET type = :type WHERE id = :id");
    /* Known filenames */
    foreach ($pdo->query("SELECT id, filename FROM wow_rootfiles WHERE type IS NULL AND filename IS NOT NULL OR type = 'unk' AND filename != '' ORDER BY id DESC") as $row) {
        if ($row['id'] == 841983) {
            continue;
        } // Skip signaturefile
        $ext = pathinfo($row['filename'], PATHINFO_EXTENSION);
        if ($ext == "unk" || empty($ext)) {
            continue;
        }

        echo "Adding type " . $ext . " for FileData ID " . $row['id'] . "\n";
        $uq->bindParam(":type", $ext);
        $uq->bindParam(":id", $row['id']);
        $uq->execute();
    }
    
    $resetRows = $pdo->exec("UPDATE wow_rootfiles SET type = NULL WHERE wow_rootfiles.id IN (SELECT filedataid FROM wow_encrypted WHERE keyname IN (SELECT keyname FROM wow_tactkey WHERE keybytes IS NOT NULL) AND active = 1)  AND  (type = 'unk')");
    if($resetRows > 0){
        echo "[" . date('h:i:s') . "] Reset type for " . $resetRows . " newly decrypted files..\n";
    }

    /* Known types */
    $modelFileData = $pdo->query("SELECT FileDataID FROM wowdata.modelfiledata")->fetchAll(PDO::FETCH_COLUMN);
    $textureFileData = $pdo->query("SELECT FileDataID FROM wowdata.texturefiledata")->fetchAll(PDO::FETCH_COLUMN);
    $movieFileData = $pdo->query("SELECT ID FROM wowdata.moviefiledata")->fetchAll(PDO::FETCH_COLUMN);
    $mp3Manifest = $pdo->query("SELECT ID FROM wowdata.manifestmp3")->fetchAll(PDO::FETCH_COLUMN);
    $skitManifest = $pdo->query("SELECT ID from wowdata.soundkitentry")->fetchAll(PDO::FETCH_COLUMN);
    $cdi = $pdo->query("SELECT PortraitTextureFileDataID, `TextureVariationFileDataID[0]`, `TextureVariationFileDataID[1]`, `TextureVariationFileDataID[2]` FROM wowdata.creaturedisplayinfo");
    $cdifdids = [];
    foreach ($cdi->fetchAll(PDO::FETCH_ASSOC) as $entry) {
        if ($entry['PortraitTextureFileDataID'] != 0) {
            $cdifdids[] = $entry['PortraitTextureFileDataID'];
        }
        if ($entry['TextureVariationFileDataID[0]'] != 0) {
            $cdifdids[] = $entry['TextureVariationFileDataID[0]'];
        }
        if ($entry['TextureVariationFileDataID[1]'] != 0) {
            $cdifdids[] = $entry['TextureVariationFileDataID[1]'];
        }
        if ($entry['TextureVariationFileDataID[2]'] != 0) {
            $cdifdids[] = $entry['TextureVariationFileDataID[2]'];
        }
    }
    foreach ($pdo->query("SELECT id, filename FROM wow_rootfiles WHERE type IS NULL OR type = 'unk'") as $file) {
        if (in_array($file['id'], $modelFileData)) {
            echo "File " . $file['id'] . " is a model!\n";
            $uq->bindValue(":type", "m2");
            $uq->bindParam(":id", $file['id']);
            $uq->execute();
        }

        if (in_array($file['id'], $textureFileData) || in_array($file['id'], $cdifdids)) {
            echo "File " . $file['id'] . " is a blp!\n";
            $uq->bindValue(":type", "blp");
            $uq->bindParam(":id", $file['id']);
            $uq->execute();
        }

        if (in_array($file['id'], $movieFileData)) {
            echo "File " . $file['id'] . " is an avi!\n";
            $uq->bindValue(":type", "avi");
            $uq->bindParam(":id", $file['id']);
            $uq->execute();
        }

        if (in_array($file['id'], $mp3Manifest)) {
            echo "File " . $file['id'] . " is an mp3!\n";
            $uq->bindValue(":type", "mp3");
            $uq->bindParam(":id", $file['id']);
            $uq->execute();
        }

        if(in_array($file['id'], $skitManifest)){
            echo "File " . $file['id'] . " is an ogg!\n";
            $uq->bindValue(":type", "ogg");
            $uq->bindParam(":id", $file['id']);
            $uq->execute();
        }
    }
    // /* Unknown but decrypted */

    // $resetTypeQ = $pdo->prepare("UPDATE wow_rootfiles SET type = NULL WHERE id = ?");

    // foreach($pdo->query("SELECT id FROM wow_rootfiles WHERE id IN (SELECT filedataid FROM wow_encrypted WHERE wow_encrypted.keyname IN (SELECT wow_tactkey.keyname FROM wow_tactkey WHERE wow_tactkey.keybytes IS NOT NULL)) AND wow_rootfiles.type = 'unk'") as $file){
    //     $resetTypeQ->execute([$file['id']]);
    // }

    /* Unknown filenames */
    $files = array();
    foreach ($pdo->query("SELECT filedataid, wow_rootfiles_chashes.contenthash, wow_buildconfig.hash as buildconfig FROM wow_rootfiles_chashes LEFT JOIN wow_buildconfig on wow_buildconfig.root_cdn=wow_rootfiles_chashes.root_cdn WHERE filedataid IN (SELECT id FROM wow_rootfiles WHERE type = '' OR type IS NULL AND filename IS NULL) GROUP BY filedataid ORDER BY buildconfig ASC") as $row) {
        $files[$row['buildconfig']][] = array("chash" => $row['contenthash'], "id" => $row['filedataid']);
    }

    $flushMemcached = false;

    foreach ($files as $buildconfig => $filelist) {
        $cdncq = $pdo->prepare("SELECT cdnconfig FROM wow_versions WHERE buildconfig = ?");
        $cdncq->execute([$buildconfig]);
        $cdnrow = $cdncq->fetch();
        if (empty($cdnrow)) {
            die("Unable to locate CDNConfig for this build (" . $buildconfig . ")!");
        }

        if (!file_exists("/tmp/casc/")) {
            mkdir("/tmp/casc/");
        }

        if (!file_exists("/tmp/casc/" . $buildconfig . "/")) {
            mkdir("/tmp/casc/" . $buildconfig . "/");
        }

        if($useBuildBackup){
            $toextract = 0;
            $extracted = 0;
            $fhandle = fopen("/tmp/casc/" . $buildconfig . ".txt", "w");
            foreach ($filelist as $file) {
                if (!file_exists("/tmp/casc/" . $buildconfig . "/" . $file['id'] . ".unk")) {
                    fwrite($fhandle, $file['chash'] . "," . $file['id'] . ".unk\n");
                    $toextract++;
                } else {
                    $extracted++;
                }

                if ($toextract > 500) {
                    break;
                }
            }
            fclose($fhandle);
            echo("Extracting " . $toextract . " unknown files (" . $extracted . " already extracted) for buildconfig " . $buildconfig . "\n");
            // echo "cd " . BACKEND_BASE_DIR . "/buildbackup; /usr/bin/dotnet " . BACKEND_BASE_DIR . "/buildbackup/BuildBackup.dll extractfilesbylist ".$buildconfig." ".$cdnrow['cdnconfig']." /tmp/casc/".$buildconfig."/ /tmp/casc/".$buildconfig.".txt";
            if ($toextract > 0) {
                $cmd = "cd " . BACKEND_BASE_DIR . "/buildbackup; /usr/bin/dotnet " . BACKEND_BASE_DIR . "/buildbackup/BuildBackup.dll extractfilesbylist " . $buildconfig . " " . $cdnrow['cdnconfig'] . " /tmp/casc/" . $buildconfig . "/ /tmp/casc/" . $buildconfig . ".txt";
                exec($cmd, $output);
            }
        }else{
            $toextract = count($filelist);
            $extracted = 0;
            foreach ($filelist as $file) {
                if (!file_exists("/tmp/casc/" . $buildconfig . "/" . $file['id'] . ".unk")) {
                    $cmd = "wget -q -O /tmp/casc/" . $buildconfig . "/" . $file['id'] . ".unk http://localhost:5005/casc/file/chash?contenthash=" . $file['chash'] . "&buildconfig=" . $buildconfig . "&cdnconfig=" . $cdnrow['cdnconfig'] . "&filename=out.unk";
                    exec($cmd, $output);
                    if(file_exists("/tmp/casc/" . $buildconfig . "/" . $file['id'] . ".unk")){
                        $extracted++;
                    }else{
                        echo "Failed to extract file " . $file['id'] . ": \n" . print_r($output, true);
                    }
                }
            }

            if($extracted != $toextract){
                echo "Failed to extract some files\n";
            }
        }

        foreach (glob("/tmp/casc/" . $buildconfig . "/*.unk") as $extractedfile) {
            $ext = guessFileExtByExtractedFilename($extractedfile);
            if (empty($ext)) {
                $ext = "unk";
            }

            $id = str_replace(array("/tmp/casc/" . $buildconfig . "/", ".unk"), "", $extractedfile);
            echo $id . " is of type " . $ext . "\n";
            $uq->bindValue(":type", str_replace(".", "", $ext));
            $uq->bindParam(":id", $id);
            $uq->execute();
            unlink($extractedfile);

            $flushMemcached = true;
        }

        if($flushMemcached){
            $memcached->flush();
        }
    }

    echo "[" . date('h:i:s') . "] Sleeping for 10 sec..\n";
    sleep(10);
}

function guessFileExtByExtractedFilename($name)
{

    $output = shell_exec("/usr/bin/file -b -i -m " . WORK_DIR . "/builds/scripts/wow.mg " . escapeshellarg($name));
    $cleaned = explode(";", $output);
    switch (trim($cleaned[0])) {
        case "wow/blp2":
            $ext = ".blp";
            break;
        case "wow/m2/legacy":
        case "wow/m2":
            $ext = ".m2";
            break;
        case "wow/m2/skin":
            $ext = ".skin";
            break;
        case "wow/m2/bone":
            $ext = ".bone";
            break;
        case "wow/m2/phys":
            $ext = ".phys";
            break;
        case "wow/m2/anim":
            $ext = ".anim";
            break;
        case "wow/m3":
            $ext = ".m3";
            break;
        case "wow/modelblob":
            $ext = ".blob";
            break;
        case "wow/tex":
            $ext = ".tex";
            break;
        case "wow/wdbc":
            $ext = ".dbc";
            break;
        case "wow/wdb2":
        case "wow/wdb3":
        case "wow/wdb4":
        case "wow/wdb5":
        case "wow/wdb6":
        case "wow/wdb7":
        case "wow/wdb8":
        case "wow/wdc1":
        case "wow/wdc2":
        case "wow/wdc3":
        case "wow/cls1":
            $ext = ".db2";
            break;
        case "wow/adt/root":
            $ext = ".adt";
            break;
        case "wow/adt/tex0":
            $ext = "_tex0.adt";
            break;
        case "wow/adt/tex1":
            $ext = "_tex1.adt";
            break;
        case "wow/adt/lod-fuddlewizz":
        case "wow/adt/obj":
            $ext = "_obj.adt";
            break;
        case "wow/adt/lod":
            $ext = "_lod.adt";
            break;
        case "wow/adt/dat":
            $ext = ".adt.dat";
            break;
        case "wow/wmo/root":
            $ext = ".wmo";
            break;
        case "wow/wmo/group":
            $ext = "_xxx.wmo";
            break;
        case "wow/bls":
        case "wow/bls/pixel":
        case "wow/bls/vertex":
        case "wow/bls/metal":
            $ext = ".bls";
            break;
        case "wow/wdt":
        case "wow/wdt/occ":
        case "wow/wdt/lgt":
        case "wow/wdt/lgt2":
            $ext = ".wdt";
            break;
        case "wow/adt/lod-doodaddefs":
        case "wow/wdl":
            $ext = ".wdl";
            break;
        case "wow/wwf":
            $ext = ".wwf";
            break;
        case "audio/mpeg":
            $ext = ".mp3";
            break;
        case "text/plist":
            $ext = ".plist";
            break;
        case "wow/sig":
            $ext = ".sig";
            break;
        case "wow/m2/skel":
            $ext = ".skel";
            break;
        case "text/xml":
            $ext = ".xml";
            break;
        case "audio/ogg":
            $ext = ".ogg";
            break;
        case "wow/scn0":
            $ext = ".scn";
            break;
        default:
            $ext = ".unk";
            break;
    }
    return $ext;
}
