<?php

include("../inc/config.php");
include("../inc/header.php");

function getFileCount($root)
{
    if (!file_exists(BACKEND_BASE_DIR . "/buildbackup/manifests/" . $root . ".txt")) {
        echo "	Dumping manifest..";
        $output = shell_exec("cd /home/wow/buildbackup; /usr/bin/dotnet /home/wow/buildbackup/BuildBackup.dll dumproot2 " . $root . " > /home/wow/buildbackup/manifests/" . $root . ".txt");
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

?>
<div class="container-fluid" style="width: 80%; margin-left: 10%; margin-top: 10px;">
    <div class="row">
        <div class="col-sm" style='max-height: 80vh; overflow-y: scroll'>
            <h4>File types</h4>
            <table class='table table-condensed table-striped'>
            <?php
            $typeq = $pdo->query("SELECT type, count(type) FROM wow_rootfiles GROUP BY type ORDER BY count(type) DESC");
            while ($typerow = $typeq->fetch()) {
                echo "<tr><td>" . $typerow['type'] . "</td><td>" . $typerow['count(type)'] . "</td></tr>";
            }
            ?>
            </table>
        </div>
        <div class="col-sm" style='max-height: 80vh; overflow-y: scroll'>
            <h4>File count per build</h4>
            <table class='table table-condensed table-striped'>
            <?php
            foreach ($arr as $build) {
                if (empty($build['count'])) {
                    $build['count'] = getFileCount($build['root_cdn']);
                    $iq = $pdo->prepare("INSERT IGNORE INTO wow_rootfiles_count (root_cdn, count) VALUES (?, ?)");
                    $iq->execute([$build['root_cdn'], $build['count']]);
                }
                echo "<tr><td>" . $build['description'] . "</td><td>" . $build['count'] . "</td></tr>";
            }
            ?>
            </table>
        </div>
        <div class="col-sm">
            <h4>Unnamed</h4>
            <table class='table table-condensed table-striped'>
            <?php
            $typeq = $pdo->query("SELECT type, count(type) FROM wow_rootfiles WHERE filename IS NULL GROUP BY type ORDER BY count(type) DESC");
            while ($typerow = $typeq->fetch()) {
                echo "<tr><td>" . $typerow['type'] . "</td><td>" . $typerow['count(type)'] . "</td></tr>";
            }
            ?>
            </table>
        </div>
    </div>
</div>
<?php include "../inc/footer.php"; ?>