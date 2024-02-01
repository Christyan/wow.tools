<?php
require_once(__DIR__ . "/../inc/header.php");
// Map old URL to new url for backwards compatibility
if (!empty($_GET['bc'])) {
    $bcq = $pdo->prepare("SELECT description FROM wow_buildconfig WHERE hash = ?");
    $bcq->execute([$_GET['bc']]);
    $row = $bcq->fetch();
    if (!empty($row)) {
        $build = parseBuildName($row['description'])['full'];
        $newurl = str_replace("bc=" . $_GET['bc'], "build=" . $build, $_SERVER['REQUEST_URI']);
        $newurl = str_replace(".db2", "", $newurl);
        echo "<meta http-equiv='refresh' content='0; url=https://wow.tools" . $newurl . "'>";
        die();
    }
} elseif (!empty($_GET['dbc']) && strpos($_GET['dbc'], "db2") !== false) {
    $newurl = str_replace(".db2", "", $_SERVER['REQUEST_URI']);
    echo "<meta http-equiv='refresh' content='0; url=https://wow.tools" . $newurl . "'>";
    die();
}

?>
<link href="/dbc/css/dbc.css?v=<?=filemtime(WORK_DIR . "/dbc/css/dbc.css")?>" rel="stylesheet">
<div class="container-fluid">
    <form id='dbcform' action='/dbc/' method='GET'>
        <select id='fileFilter' class='form-control form-control-sm'></select>
        <select id='buildFilter' name='build' class='form-control form-control-sm buildFilter'></select>

        <!-- <select id='localeSelection' name='locale' class='form-control form-control-sm buildFilter'>
            <option value=''>enUS (Default)</option>
        </select> -->
        <div class='btn-group' style='margin-top: -8px'>
        <!-- data-content='<span class="badge badge-danger">WARNING!</span> CSV exports are going away soon (see link in footer for alternative)!' -->
            <!-- <a href='' id='downloadCSVButton' class='form-control form-control-sm btn btn-sm btn-secondary'><i class='fa fa-download'></i> CSV</a> -->
            <!-- <button type="button" class="btn btn-sm btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu">
                <a href="" id='downloadDB2Link'><i class='fa fa-download'></i> DB2</a>
            </div> -->
        </div>
        <label class="btn btn-sm btn-info active" style='margin-left: 5px;'>
            <input type="checkbox" autocomplete="off" id="hotfixToggle"> Use hotfixes?
        </label>
        <a style='vertical-align: top;' class='btn btn-secondary btn-sm' data-toggle='modal' href=''
            data-target='#settingsModal'><i class='fa fa-gear'></i> Settings</a>
        <a id='dbdButton' style='vertical-align: top; display: none;' class='btn btn-secondary btn-sm disabled' href=''
            target='_BLANK'><i class='fa fa-external-link'></i> DBD</a>
        <a style='vertical-align: top; display: none' id='fkSearchButton' class='btn btn-danger btn-sm'
            data-toggle='modal' href='' data-target='#foreignKeySearchModal'><i class='fa fa-search'></i> FK Search</a>
    </form><br>
    <div id='tableContainer'><br>
        <table id='dbtable' class="table table-striped table-bordered table-condensed" cellspacing="0" width="100%">
            <thead>
            </thead>
            <tbody>
                <tr>
                    <td style='text-align: center' id='loadingMessage'>Select a table in the dropdown above</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="modal" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Settings</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="settingsModalContent">
                <input type="checkbox" autocomplete="off" id="tooltipToggle" CHECKED> <label for='tooltipToggle'>Enable
                    tooltips?</label><br>
                <input type="checkbox" autocomplete="off" id="alwaysEnableFilters" CHECKED> <label
                    for='alwaysEnableFilters'>Always show filters?</label><br>
                <input type="checkbox" autocomplete="off" id="changedVersionsOnly"> <label
                    for='changedVersionsOnly'>Only show changed versions (experimental)?</label><br>
                <input type="checkbox" autocomplete="off" id="showDBDButton"> <label for='showDBDButton'>Show DBD
                    button?</label><br>
                <input type="checkbox" autocomplete="off" id="showFKButton"> <label for='showFKButton'>Show FK search
                    button?</label><br>
                <label for="lockedBuild">Lock build to: </label>
                <select id='lockedBuild' style='width: 100%'>
                    <option value='none'>None</option>
                    <?php foreach ($pdo->query("SELECT description, root_cdn FROM wow_buildconfig ORDER BY wow_buildconfig.description DESC") as $build) {
                        $prettyBuild = prettyBuild($build['description']);
                    ?>
                    <option value='<?=explode(" ", $prettyBuild)[0]?>'><?=$prettyBuild?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSettings();"
                    data-dismiss="modal">Save</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="fkModal" tabindex="-1" role="dialog" aria-labelledby="fkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fkModalLabel">Foreign key lookup</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="fkModalContent">
                <i class="fa fa-refresh fa-spin" style="font-size:24px"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="moreInfoModal" tabindex="-1" role="dialog" aria-labelledby="moreInfoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moreInfoModalLabel">More information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="moreInfoModalContent">
                <i class="fa fa-refresh fa-spin" style="font-size:24px"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewModalContent">
                <i class="fa fa-refresh fa-spin" style="font-size:24px"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="chashModal" tabindex="-1" role="dialog" aria-labelledby="chashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chashModalLabel">Content hash lookup</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="chashModalContent">
                <i class="fa fa-refresh fa-spin" style="font-size:24px"></i>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="foreignKeySearchModal" tabindex="-1" role="dialog" aria-labelledby="foreignKeySearchLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="foreignKeySearchLabel">Foreign key search</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="foreignKeySearchContent">
                <form class='form-inline' id='foreignKeySearchForm' onsubmit="fkDBSearchSubmit()">
                    <div class="form-row">
                        <div class="col">
                            <select class='form-control' onchange="fkDBSearchChange()" id="fkSearchDB">
                                <option>DB Name</option>
                            </select>
                        </div>
                        <div class="col">
                            <select class='form-control' id='fkSearchField' disabled>
                                <option>Field</option>
                            </select>
                        </div>
                        <div class="col">
                            <input class='form-control' style='height: 26px;' type='text' placeholder='Value'
                                id='fkSearchValue'>
                        </div>
                        <div class="col">
                            <input class='form-control' type='submit' value='Search'>
                        </div>
                    </div><br>
                </form>
                <div id='fkSearchResultHolder'>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/css/select2.min.css" rel="stylesheet" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.7/js/select2.min.js"></script>
<script src="/files/js/files.js" crossorigin="anonymous"></script>
<script src="/dbc/js/dbc.js?v=<?=filemtime(WORK_DIR . "/dbc/js/dbc.js")?>"></script>
<script src="/dbc/js/flags.js?v=<?=filemtime(WORK_DIR . "/dbc/js/flags.js")?>"></script>
<script src="/dbc/js/enums.js?v=<?=filemtime(WORK_DIR . "/dbc/js/enums.js")?>"></script>
<script type='text/javascript'>
var Settings = {
    filtersAlwaysEnabled: false,
    filtersCurrentlyEnabled: false,
    enableTooltips: true,
    changedVersionsOnly: false,
    showDBDButton: false,
    showFKButton: false,
    lockedBuild: null
}

// var Locales = {
//     koKR: "Korean",
//     frFR: "French",
//     deDE: "German",
//     zhCN: "Simplified Chinese",
//     esES: "Spanish",
//     zhTW: "Taiwanese Mandarin",
//     enGB: "Also English",
//     esMX: "Mexican Spanish",
//     ruRU: "Russian",
//     ptBR: "Brazilian Portugese",
//     itIT: "Italian",
//     ptPT: "Portugese"
// }

let APIBase = API_URL + "/";
const siteURL = new URL(window.location);
if(siteURL.hostname == "wow.tools.localhost" || siteURL.hostname == "localhost"){
    APIBase = "http://localhost/wtapi/";
}
let clearState = false;

function updateDBDButton() {
    console.log("updating button");
    const dbdButton = document.getElementById("dbdButton");
    if (dbdButton) {
        if (Settings.showDBDButton) {
            document.getElementById("dbdButton").style.display = 'inline-block';
        } else {
            document.getElementById("dbdButton").style.display = 'none';
        }

        const fileFilter = document.getElementById("fileFilter");
        if (fileFilter.selectedIndex != -1) {
            dbdButton.href = "https://github.com/wowdev/WoWDBDefs/blob/master/definitions/" + fileFilter.options[
                fileFilter.selectedIndex].text + ".dbd";
            dbdButton.classList.remove("disabled");
        }
    }
}

function saveSettings() {
    if (document.getElementById("tooltipToggle").checked) {
        localStorage.setItem('settings[tooltipToggle]', '1');
    } else {
        localStorage.setItem('settings[tooltipToggle]', '0');
    }

    if (document.getElementById("alwaysEnableFilters").checked) {
        localStorage.setItem('settings[alwaysEnableFilters]', '1');
    } else {
        localStorage.setItem('settings[alwaysEnableFilters]', '0');
    }

    if (document.getElementById("changedVersionsOnly").checked) {
        localStorage.setItem('settings[changedVersionsOnly]', '1');
    } else {
        localStorage.setItem('settings[changedVersionsOnly]', '0');
    }

    if (document.getElementById("showDBDButton").checked) {
        localStorage.setItem('settings[showDBDButton]', '1');
        document.getElementById("dbdButton").style.display = 'inline-block';
    } else {
        localStorage.setItem('settings[showDBDButton]', '0');
        document.getElementById("dbdButton").style.display = 'none';
    }

    if (document.getElementById("showFKButton").checked) {
        localStorage.setItem('settings[showFKButton]', '1');
        document.getElementById("fkSearchButton").style.display = 'inline-block';
    } else {
        localStorage.setItem('settings[showFKButton]', '0');
        document.getElementById("fkSearchButton").style.display = 'none';
    }

    if (document.getElementById("lockedBuild").value == null || document.getElementById("lockedBuild").value ==
        "none") {
        localStorage.removeItem('settings[lockedBuild]');
    } else {
        localStorage.setItem('settings[lockedBuild]', document.getElementById("lockedBuild").value);
    }

    if (Settings.changedVersionsOnly != document.getElementById("changedVersionsOnly").checked) {
        loadSettings();
        refreshVersions();
    }

    if (Settings.lockedBuild != localStorage.getItem('settings[lockedBuild]')) {
        loadSettings();
        refreshFiles();
        refreshVersions();
        loadTable();
    }
}

function loadSettings() {
    /* Enable tooltips? */
    var tooltipToggle = localStorage.getItem('settings[tooltipToggle]');
    if (tooltipToggle) {
        if (tooltipToggle == "1") {
            Settings.enableTooltips = true;
        } else {
            Settings.enableTooltips = false;
        }
    }

    document.getElementById("tooltipToggle").checked = Settings.enableTooltips;

    /* Filters always enabled? */
    var alwaysEnableFilters = localStorage.getItem('settings[alwaysEnableFilters]');
    if (alwaysEnableFilters) {
        if (alwaysEnableFilters == "1") {
            Settings.filtersAlwaysEnabled = true;
        } else {
            Settings.filtersAlwaysEnabled = false;
        }
    }

    document.getElementById("alwaysEnableFilters").checked = Settings.filtersAlwaysEnabled;

    /* Changed versions only? */
    var changedVersionsOnly = localStorage.getItem('settings[changedVersionsOnly]');
    if (changedVersionsOnly) {
        if (changedVersionsOnly == "1") {
            Settings.changedVersionsOnly = true;
        } else {
            Settings.changedVersionsOnly = false;
        }
    }

    document.getElementById("changedVersionsOnly").checked = Settings.changedVersionsOnly;

    /* Show DBD button? */
    var showDBDButton = localStorage.getItem('settings[showDBDButton]');
    if (showDBDButton) {
        if (showDBDButton == "1") {
            Settings.showDBDButton = true;
            document.getElementById("dbdButton").style.display = 'inline-block';
        } else {
            Settings.showDBDButton = false;
            document.getElementById("dbdButton").style.display = 'none';
        }
    }

    document.getElementById("showDBDButton").checked = Settings.showDBDButton;

    /* Show FK button? */
    var showFKButton = localStorage.getItem('settings[showFKButton]');
    if (showFKButton) {
        if (showFKButton == "1") {
            Settings.showFKButton = true;
            document.getElementById("fkSearchButton").style.display = 'inline-block';
        } else {
            Settings.showFKButton = false;
            document.getElementById("fkSearchButton").style.display = 'none';
        }
    }

    document.getElementById("showFKButton").checked = Settings.showFKButton;

    var lockedBuild = localStorage.getItem("settings[lockedBuild]");
    Settings.lockedBuild = lockedBuild;

    if (lockedBuild && lockedBuild != "none") {
        document.getElementById("lockedBuild").value = lockedBuild;
    }
}

loadSettings();

function toggleFilters() {
    if (!Settings.filtersCurrentlyEnabled) {
        $("#filterToggle").text("Disable filters");
        $("#filterToggle").addClass("btn-outline-danger");
        $("#filterToggle").removeClass("btn-outline-success");
        $("#filterToggle").removeClass("btn-outline-primary");
        $("#tableContainer thead tr").clone(true).appendTo("#tableContainer thead");
        $("#tableContainer thead tr:eq(1) th").each(function(i) {
            var title = $(this).text();

            $(this).html('<input type="text"/>');

            if ($('#dbtable').DataTable().column(i).search() != "") {
                $('input', this).val($('#dbtable').DataTable().column(i).search());
            }

            $('input', this).on('keyup change', function() {
                if ($('#dbtable').DataTable().column(i).search() !== this.value) {
                    $('#dbtable').DataTable().column(i).search(this.value).draw();
                }
            });

            $('input', this).on('click', function(e) {
                e.stopPropagation();
            });
        });

        Settings.filtersCurrentlyEnabled = true;
    } else {
        $("#filterToggle").text("Enable filters");
        $("#filterToggle").addClass("btn-outline-success");
        $("#filterToggle").removeClass("btn-outline-danger");
        $("#filterToggle").removeClass("btn-outline-primary");

        $("#tableContainer thead tr:eq(1) th").each(function(i) {
            $('#dbtable').DataTable().column(i).search("");
        });

        $("#tableContainer thead tr:eq(1)").remove();

        Settings.filtersCurrentlyEnabled = false;
    }

    $('#dbtable').DataTable().draw('page');
}

function buildURL(currentParams) {
    let url = window.location.protocol + "//" + window.location.hostname + "/dbc/";

    if (currentParams["dbc"]) {
        url += "?dbc=" + currentParams["dbc"];
    }

    if (currentParams["build"]) {
        url += "&build=" + currentParams["build"];
    }

    // if (currentParams["locale"]) {
    //     url += "&locale=" + currentParams["locale"];
    // }

    if (currentParams["hotfixes"]) {
        url += "&hotfixes=" + currentParams["hotfixes"];
    }

    return url;
}

function refreshFiles() {
    console.log("Refreshing files");

    let apiURL = APIBase + "databases/";

    if (Settings.lockedBuild && Settings.lockedBuild != "none") {
        apiURL += Settings.lockedBuild;
    }

    fetch(apiURL)
        .then(function(fileResponse) {
            return fileResponse.json();
        }).then(function(data) {
            var fileFilter = document.getElementById('fileFilter');
            fileFilter.innerHTML = "";

            var option = document.createElement("option");
            option.text = "Select a table";
            fileFilter.appendChild(option);

            data.forEach((file) => {
                var option = document.createElement("option");
                option.value = file.name;
                option.text = file.displayName;
                if (option.value == currentParams["dbc"]) {
                    option.selected = true;
                }
                fileFilter.appendChild(option);
            });

            updateDBDButton();
        }).catch(function(error) {
            console.log("An error occurred retrieving files: " + error);
        });
}

function refreshVersions() {
    if (currentParams["dbc"] == "")
        return;

    console.log("Refreshing versions");
    var versionAPIURL = APIBase + "databases/" + currentParams["dbc"] + "/versions";
    if (Settings.changedVersionsOnly)
        versionAPIURL += "?uniqueOnly=true";

    fetch(versionAPIURL)
        .then(function(versionResponse) {
            return versionResponse.json();
        }).then(function(data) {
            var buildFilter = document.getElementById('buildFilter');
            buildFilter.innerHTML = "";

            var preselected = false;
            if (Settings.lockedBuild != null && Settings.lockedBuild != "none" && (!data.includes(Settings
                    .lockedBuild))) {
                console.log("Locked build is not in dropdown");

                var option = document.createElement("option");
                option.value = Settings.lockedBuild;
                option.text = Settings.lockedBuild;
                option.selected = true;
                preselected = true;
                buildFilter.appendChild(option);
            }

            data.forEach((version) => {
                var option = document.createElement("option");
                option.value = version;
                option.text = version;
                if (!preselected && (option.value == currentParams["build"] || option.value == Settings
                        .lockedBuild)) {
                    option.selected = true;
                }

                buildFilter.appendChild(option);
            });

            if (currentParams["build"] == "" || !data.includes(currentParams["build"])) {
                // No build was selected, load table with the latest build
                if (Settings.lockedBuild !== null) {
                    currentParams["build"] = Settings.lockedBuild;
                } else {
                    currentParams["build"] = data[0];
                }
                loadTable();
            }

            if (Settings.lockedBuild != null && Settings.lockedBuild != "none") {
                buildFilter.disabled = true;
                buildFilter.classList.add("disabled");
                buildFilter.title = "Build locked in settings";
            } else {
                buildFilter.disabled = false;
                buildFilter.classList.remove("disabled");
                buildFilter.title = "";
            }
        }).catch(function(error) {
            console.log("An error occurred retrieving versions: " + error);
        });
}

function fkDBSearchChange() {
    // Load headers and fill columns options
    const currentDBC = document.getElementById("fkSearchDB").value;

    fetch(API_URL + "/api/header/" + currentDBC + "/?build=" + currentParams["build"]).then(function(headerResponse) {
        return headerResponse.json();
    }).then(function(json) {
        var fkSearchField = document.getElementById('fkSearchField');
        fkSearchField.disabled = false;
        fkSearchField.innerHTML = "";

        json.headers.forEach((header) => {
            if (header in json.relationsToColumns) {
                var option = document.createElement("option");
                option.value = header;
                option.text = header;
                console.log(header);
                fkSearchField.appendChild(option);
            }
        });

        $('#fkSearchField').select2();
    });
}

function fkDBSearchSubmit() {
    event.preventDefault();

    const currentDBC = document.getElementById("fkSearchDB").value;
    const currentField = document.getElementById("fkSearchField").value;
    const searchValue = document.getElementById("fkSearchValue").value;

    fkDBSearch(currentDBC, currentField, searchValue, false);
}

async function fkDBSearch(db, col, val, isInline = false) {
    if (!isInline) {
        document.getElementById("foreignKeySearchForm").style.display = "none";
    } else {
        document.getElementById("foreignKeySearchForm").style.display = "flex";
    }

    document.getElementById("fkSearchResultHolder").innerHTML =
        "<ul class='nav nav-tabs' id='fkresultTabList' role='tablist'></ul><div class='tab-content' id='fkresultTabs'></div><div id='noResultHolder'></div>";

    const dbsToSearch = [];
    const headerResult = await fetch(API_URL + "/api/header/" + db + "/?build=" + currentParams["build"]);
    const headerJSON = await headerResult.json();

    headerJSON.relationsToColumns[col].forEach((foreignKey) => {
        const splitFK = foreignKey.split("::");
        dbsToSearch.push(async () => {
            try {
                const result = await fetch(API_URL + "/api/find/" + splitFK[0] + "?build=" +
                    currentParams["build"] + "&col=" + splitFK[1] + "&val=" + val);
                const json = await result.json();
                fkDBResults(splitFK, json, val);
            } catch (e) {
                console.log(e);
            }
        });
    });

    dbsToSearch.forEach(anonPromise => anonPromise());

    await Promise.all(dbsToSearch);
}

function fkDBResults(splitFK, results, searchVal) {
    console.log("Search results for " + splitFK, results);

    const tabHolder = document.getElementById("fkresultTabList");
    const resultHolder = document.getElementById("fkresultTabs");
    const fkNoResultHolder = document.getElementById("fkresultTabs");

    if (results.length) {
        if (tabHolder.children.length == 0) {
            tabHolder.innerHTML += "<li class='nav-item'><a class='nav-link active' data-toggle='tab' href='#tab" +
                splitFK[0] + splitFK[1] + "'>" + splitFK[0] + "::" + splitFK[1] + "</a></li>";
        } else {
            tabHolder.innerHTML += "<li class='nav-item'><a class='nav-link' data-toggle='tab' href='#tab" + splitFK[
                0] + splitFK[1] + "'>" + splitFK[0] + "::" + splitFK[1] + "</a></li>";
        }

        let resultsHTML = "";
        if (resultHolder.children.length == 0) {
            resultsHTML += "<div class='tab-pane show active' id='tab" + splitFK[0] + splitFK[1] + "'>";
        } else {
            resultsHTML += "<div class='tab-pane' id='tab" + splitFK[0] + splitFK[1] + "'>";
        }

        if(results.length < 1000){
            resultsHTML += "<br><table id='FKResults" + splitFK[0] + splitFK[1] + "' class='table table-sm'>";
            resultsHTML += "<thead><tr>";
            let targetCol = 0;
            Object.keys(results[0]).forEach((key) => {
                if (key == splitFK[1]) {
                    targetCol = Object.keys(results[0]).indexOf(key);
                    resultsHTML += "<th class='text-success'>" + key + "</th>";
                } else {
                    resultsHTML += "<th>" + key + "</th>";
                }
            });

            resultsHTML += "</tr></thead><tbody></tbody>";
            var externalResultURL = "/dbc/?dbc=" + splitFK[0].toLowerCase() + "&build=" + currentParams["build"] + "#page=1&colFilter[" + targetCol + "]=exact:" + encodeURIComponent(searchVal);
            resultsHTML += "</table><br><a class='btn btn-sm btn-primary' href='" + externalResultURL + "' target='_BLANK'>View " + splitFK[0] + "::" + splitFK[1] + " results in new tab</a></div>";
            resultHolder.insertAdjacentHTML('beforeend', resultsHTML);
            const dt = $("#FKResults" + splitFK[0] + splitFK[1]).DataTable({
                "pagingType": "input",
                "searching": false,
                "pageLength": 10,
                "displayStart": 0,
                "autoWidth": true
            });

            results.forEach((result) => {
                dt.rows.add([Object.values(result)]);
            });

            dt.draw();
        }else{
            let targetCol = 0;
            Object.keys(results[0]).forEach((key) => {
                if (key == splitFK[1]) {
                    targetCol = Object.keys(results[0]).indexOf(key);
                }
            });
            var externalResultURL = "/dbc/?dbc=" + splitFK[0].toLowerCase() + "&build=" + currentParams["build"] + "#page=1&colFilter[" + targetCol + "]=exact:" + encodeURIComponent(searchVal);
            resultsHTML += "<a class='btn btn-sm btn-primary' href='" + externalResultURL + "' target='_BLANK'>Too many results! View " + splitFK[0] + "::" + splitFK[1] + " results in new tab</a></div>";
        
            resultHolder.insertAdjacentHTML('beforeend', resultsHTML);
        }
    } else {
        if (document.getElementById("noResultHolder").innerHTML == "") {
            document.getElementById("noResultHolder").innerHTML = "<b>No results for:</b>";
        }

        document.getElementById("noResultHolder").innerHTML += splitFK[0] + "::" + splitFK[1] + ", ";
    }
}

function loadTable() {
    console.log("Loading table", currentParams);
    if (!currentParams["dbc"] || !currentParams["build"]) {
        // Don't bother doing anything else if no DBC is selected
        console.log("No DBC or build selected, skipping table load.");
        return;
    }
    Settings.filtersCurrentlyEnabled = false;

    build = currentParams["build"];

    $("#dbtable").html(
        "<tbody><tr><td style='text-align: center' id='loadingMessage'>Select a table in the dropdown above</td></tr></tbody>"
        );
    // document.getElementById('downloadCSVButton').href = buildURL(currentParams).replace("/dbc/?dbc=", API_URL + "/api/export/?name=");

    // if(currentParams["locale"] != ""){
        // document.getElementById('downloadDB2Link').href = "/casc/file/db2/?tableName=" + currentParams["dbc"] + "&fullBuild=" + currentParams["build"] + "&locale=" + currentParams["locale"];
    // }else{
        // document.getElementById('downloadDB2Link').href = API_URL + "/api/export/db2/?tableName=" + currentParams["dbc"] + "&fullBuild=" + currentParams["build"];
    // }
    
    // document.getElementById('downloadCSVButton').classList.remove("disabled");
    $("#loadingMessage").html("Loading..");

    let apiArgs = currentParams["dbc"] + "/?build=" + currentParams["build"];

    // if (currentParams["locale"] != "") {
        // apiArgs += "&locale=" + currentParams["locale"];
    // }

    if (currentParams["hotfixes"]) {
        apiArgs += "&useHotfixes=true";
        //document.getElementById('downloadCSVButton').href = document.getElementById('downloadCSVButton').href.replace("&hotfixes=", "&useHotfixes=");
    }

    let tableHeaders = "";
    let idHeader = 0;

    const buildSplit = currentParams["build"].split('.');
    const buildOnly = buildSplit[buildSplit.length - 1];

    let hotfixedIDs = [];
    
    if (currentParams["hotfixes"]) {
        fetch(APIBase + "databases/" + currentParams["dbc"] + "/hotfixes?build=" + buildOnly)
        .then(function (response) {
            return response.json();
        }).then(function (data) {
            hotfixedIDs = data;
        });
    }

    $.ajax({
        "url": API_URL + "/api/header/" + currentParams["dbc"] + "/?build=" + currentParams["build"],
        "success": function(json) {
            if (json['error'] != null) {
                if (json['error'] == "No valid definition found for this layouthash or build!") {
                    json['error'] +=
                        "\n\nPlease open an issue on the WoWDBDefs repository with the DBC name and selected version on GitHub to request a definition for this build.\n\nhttps://github.com/wowdev/WoWDBDefs";
                }
                $("#loadingMessage").html(
                    "<div class='alert '><b>Whoops, something exploded while loading this DBC</b><br>It is possible this is due to maintenance or an issue with reading the DBC file itself. Please try again later or report the below error (together with the table name and version) in Discord if it persists. Thanks!</p><p style='margin: 5px;'><kbd>" +
                    json['error'] + "</kbd></p></div>");
                return;
            }
            let allCols = [];
            $.each(json['headers'], function(i, val) {
                tableHeaders += "<th style='white-space: nowrap' ";
                if (val in json['comments']) {
                    tableHeaders += "title='" + json['comments'][val] + "' class='colHasComment'>";
                } else {
                    tableHeaders += ">";
                }

                if (val in json['relationsToColumns']) {
                    tableHeaders +=
                        " <i class='fa fa-reply' style='font-size: 10px' title='The following tables point to this column: " +
                        json['relationsToColumns'][val].join(", ") + "'></i> ";
                }

                tableHeaders += val;

                if (val.startsWith("Field_")) {
                    tableHeaders +=
                        " <i class='fa fa-question' style='color: red; font-size: 12px' title='This column is not yet named'></i> ";
                } else if (json['unverifieds'].includes(val)) {
                    tableHeaders +=
                        " <i class='fa fa-question' style='font-size: 12px' title='This column name is not verified to be 100% accurate'></i> ";
                }

                if (val in json['fks']) {
                    tableHeaders +=
                        " <i class='fa fa-share' style='font-size: 10px' title='This column points to " +
                        json['fks'][val] + "'></i> ";
                }

                tableHeaders += "</th>";

                if (val == "ID") {
                    idHeader = i;
                }
                allCols.push(i);
            });

            const fkCols = getFKCols(json['headers'], json['fks']);
            $("#tableContainer").empty();
            $("#tableContainer").append(
                '<table id="dbtable" class="table table-striped table-bordered table-condensed" cellspacing="0" width="100%"><thead><tr>' +
                tableHeaders + '</tr></thead></table>');

            let searchHash = location.hash.substr(1),
                searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];

            if (searchString != undefined && searchString.length > 0) {
                searchString = decodeURIComponent(searchString);
            }

            let page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1],
                10) || 1) - 1;
            let highlightRow = parseInt(searchHash.substr(searchHash.indexOf('row=')).split('&')[0].split(
                '=')[1], 10) - 1;
            $.fn.dataTable.ext.errMode = 'none';
            $('#dbtable').on('error.dt', function(e, settings, techNote, message) {
                console.log('An error occurred: ', message);
            });

            var colSearchSet = false;
            var colSearches = [];

            if (!clearState) {
                for (let i = 0; i < json['headers'].length; i++) {
                    var colSearch = searchHash.substr(searchHash.replace("%5B", "[").replace("%5D", "]").indexOf('colFilter[' + i + ']=')).split(
                        '&')[0].split('=')[1];
                    if (colSearch != undefined && colSearch != "") {
                        colSearches.push({
                            search: decodeURIComponent(colSearch.trim())
                        });
                        colSearchSet = true;
                    } else {
                        colSearches.push(null);
                    }
                }
            } else {
                console.log("Clearing state");
                page = 0;
                clearState = false;
            }

            var table = $('#dbtable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: API_URL + "/api/data/" + apiArgs,
                    type: "POST",
                    beforeSend: function() {
                        if (table && table.hasOwnProperty('settings')) {
                            // table.settings()[0].jqXHR.abort();
                        }
                    },
                    "data": function(result) {
                        for (const col in result.columns) {
                            result.columns[col].search.value = result.columns[col].search.value
                                .trim();
                        }
                        return result;
                    }
                },
                "pageLength": 25,
                "displayStart": page * 25,
                "autoWidth": true,
                "pagingType": "input",
                "lengthMenu": [
                    [10, 25, 50, 100, 1000],
                    [10, 25, 50, 100, 1000]
                ],
                "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12 dbtableholder'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                "orderMulti": false,
                "ordering": true,
                "order": [], // Sets default order to nothing (as returned by backend)
                "language": {
                    "search": "<a id='filterToggle' class='btn btn-dark btn-sm btn-outline-primary' href='#' onClick='toggleFilters()' style='margin-right: 10px'>Toggle filters</a> Search: _INPUT_ "
                },
                "search": {
                    "search": searchString
                },
                "searchCols": colSearches,
                "columnDefs": [{
                    "targets": allCols,
                    "render": function(data, type, full, meta) {
                        let returnVar = full[meta.col];
                        const columnWithTable = currentParams["dbc"] + '.' + json[
                            "headers"][meta.col];
                        let fk = "";
                        if (meta.col in fkCols) {
                            fk = fkCols[meta.col];
                        } else if (conditionalFKs.has(columnWithTable)) {
                            let conditionalFK = conditionalFKs.get(columnWithTable);
                            conditionalFK.forEach(function(conditionalFKEntry) {
                                let condition = conditionalFKEntry[0].split(
                                '=');
                                let conditionTarget = condition[0].split('.');
                                let conditionValue = condition[1];
                                let resultTarget = conditionalFKEntry[1];

                                let colTarget = json["headers"].indexOf(
                                    conditionTarget[1]);

                                // Col target found?
                                if (colTarget > -1) {
                                    if (full[colTarget] == conditionValue) {
                                        fk = resultTarget;
                                    }
                                }
                            });
                        }

                        if (fk != "") {
                            if (fk == "FileData::ID") {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-toggle='modal' data-target='#moreInfoModal' data-tooltip='file' data-id='" +
                                    full[meta.col] + "' onclick='fillModal(" + full[meta
                                        .col] + ")'>" + full[meta.col] + "</a>";
                            } else if (fk == "SoundEntries::ID" && parseInt(
                                    currentParams["build"][0]) > 6) {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"SoundKit::ID\",\"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            } else if (fk == "Item::ID" && full[meta.col] > 0) {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-tooltip='item' data-id='" +
                                    full[meta.col] +
                                    "' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"" + fk + "\", \"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            } else if (fk.toLowerCase() == "questv2::id" && full[meta
                                    .col] > 0) {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-tooltip='quest' data-id='" +
                                    full[meta.col] +
                                    "' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"" + fk + "\", \"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            } else if (fk == "Creature::ID" && full[meta.col] > 0) {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-tooltip='creature' data-id='" +
                                    full[meta.col] +
                                    "' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"" + fk + "\", \"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            } else if (fk.toLowerCase() == "spell::id" && full[meta
                                .col] > 0) {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-tooltip='spell' data-id='" +
                                    full[meta.col] +
                                    "' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"" + fk + "\", \"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            } else {
                                returnVar =
                                    "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-tooltip='fk' data-id='" +
                                    full[meta.col] + "' data-fk='" + fk +
                                    "' data-toggle='modal' data-target='#fkModal' onclick='openFKModal(" +
                                    full[meta.col] + ", \"" + fk + "\", \"" +
                                    currentParams["build"] + "\")'>" + full[meta.col] +
                                    "</a>";
                            }
                        } else if (json["headers"][meta.col].startsWith("Flags") ||
                            flagMap.has(columnWithTable)) {
                            returnVar =
                                "<span style='padding-top: 0px; padding-bottom: 0px; cursor: help; border-bottom: 1px dotted;' data-trigger='hover' data-container='body' data-html='true' data-toggle='popover' data-content='" +
                                fancyFlagTable(getFlagDescriptions(currentParams["dbc"],
                                    json["headers"][meta.col], full[meta.col])) +
                                "'>0x" + dec2hex(full[meta.col]) + "</span>";
                        } else if (columnWithTable == "item.ID") {
                            returnVar =
                                "<span style='padding-top: 0px; padding-bottom: 0px; cursor: help; border-bottom: 1px dotted;' data-tooltip='item' data-id='" +
                                full[meta.col] + "'>" + full[meta.col] + "</span>";
                        } else if (columnWithTable == "spell.ID" || columnWithTable ==
                            "spellname.ID") {
                            returnVar =
                                "<span style='padding-top: 0px; padding-bottom: 0px; cursor: help; border-bottom: 1px dotted;' data-tooltip='spell' data-id='" +
                                full[meta.col] + "'>" + full[meta.col] + "</span>";
                        } else if (currentParams["dbc"].toLowerCase() ==
                            "playercondition" && json["headers"][meta.col].endsWith(
                                "Logic") && full[meta.col] != 0) {
                            returnVar += " <i>(" + parseLogic(full[meta.col]) + ")</i>";
                        }

                        if (json["headers"][meta.col] in json["relationsToColumns"] &&
                            columnWithTable != "spell.ID") {
                            returnVar =
                                " <a data-toggle='modal' href='' style='cursor: help; border-bottom: 1px solid;' data-target='#foreignKeySearchModal' onClick='fkDBSearch(\"" +
                                currentParams["dbc"] + "\", \"" + json["headers"][meta
                                    .col
                                ] + "\", \"" + full[meta.col] + "\")'>" + full[meta
                                .col] + "</a>";
                        }

                        if (enumMap.has(columnWithTable)) {
                            var enumVal = getEnum(currentParams["dbc"].toLowerCase(),
                                json["headers"][meta.col], full[meta.col]);
                            if (full[meta.col] == '0' && enumVal == "Unk") {
                                // returnVar += full[meta.col];
                            } else {
                                returnVar += " <i>(" + enumVal + ")</i>";
                            }
                        }

                        if (conditionalEnums.has(columnWithTable)) {
                            let conditionalEnum = conditionalEnums.get(columnWithTable);
                            conditionalEnum.forEach(function(conditionalEnumEntry) {
                                let condition = conditionalEnumEntry[0].split(
                                    '=');
                                let conditionTarget = condition[0].split('.');
                                let conditionValue = condition[1];
                                let resultEnum = conditionalEnumEntry[1];

                                let colTarget = json["headers"].indexOf(
                                    conditionTarget[1]);

                                // Col target found?
                                if (colTarget > -1) {
                                    if (full[colTarget] == conditionValue) {
                                        var enumVal = getEnumVal(resultEnum,
                                            full[meta.col]);
                                        if (full[meta.col] == '0' && enumVal ==
                                            "Unk") {
                                            returnVar = full[meta.col];
                                        } else {
                                            returnVar = full[meta.col] +
                                                " <i>(" + enumVal + ")</i>";
                                        }
                                    }
                                }
                            });
                        }

                        if (conditionalFlags.has(columnWithTable)) {
                            let conditionalFlag = conditionalFlags.get(columnWithTable);
                            conditionalFlag.forEach(function(conditionalFlagEntry) {
                                let condition = conditionalFlagEntry[0].split(
                                    '=');
                                let conditionTarget = condition[0].split('.');
                                let conditionValue = condition[1];
                                let resultFlag = conditionalFlagEntry[1];

                                let colTarget = json["headers"].indexOf(
                                    conditionTarget[1]);

                                // Col target found?
                                if (colTarget > -1) {
                                    if (full[colTarget] == conditionValue) {
                                        returnVar =
                                            "<span style='padding-top: 0px; padding-bottom: 0px; cursor: help; border-bottom: 1px dotted;' data-trigger='hover' data-container='body' data-html='true' data-toggle='popover' data-content='" +
                                            getFlagDescriptions(currentParams[
                                                "dbc"], json["headers"][meta
                                                .col
                                            ], full[meta.col], resultFlag).join(
                                                ",<br> ") + "'>0x" + dec2hex(
                                                full[meta.col]) + "</span>";
                                    }
                                }
                            });
                        }

                        if (colorFields.includes(columnWithTable)) {
                            returnVar =
                                "<div style='display: inline-block; border: 2px solid black; height: 19px; width: 19px; background-color: " +
                                BGRA2RGBA(full[meta.col]) + "'>&nbsp;</div> " + full[
                                    meta.col];
                        }

                        if (dateFields.includes(columnWithTable)) {
                            let parsedDate = parseDate(full[meta.col]);
                            if (parsedDate && parsedDate != "")
                                returnVar = parsedDate + "<small> (" + full[meta.col] +
                                ")</small>";
                        }

                        if(meta.col == idHeader && (Number(full[meta.col]) in hotfixedIDs)){
                            returnVar += "<a href='/dbc/hotfixes.php?search=pushid:" + hotfixedIDs[Number(full[meta.col])] + "' target='_BLANK'><i style='color: orange' class='fa fa-pencil-square' data-trigger='hover' data-container='body' data-toggle='popover' data-content='This row was added or modified in a hotfix. Click to go to diff.'></i></a>";
                        }

                        return returnVar;
                    }
                }],
                "createdRow": function(row, data, dataIndex) {
                    if (dataIndex == highlightRow) {
                        $(row).addClass('highlight');
                        highlightRow = -1;
                    }
                },
            });

            $('#dbtable').on('init.dt', function() {
                if (Settings.filtersAlwaysEnabled) {
                    toggleFilters();
                };
                if (colSearchSet && !Settings.filtersAlwaysEnabled && !Settings
                    .filtersCurrentlyEnabled) {
                    toggleFilters();
                }
            });

            $('#dbtable').on('draw.dt', function() {
                window.history.pushState('dbc', 'WoW.Tools | Database browser', buildURL(
                    currentParams));

                let currentPage = $('#dbtable').DataTable().page() + 1;
                let hashPart = "page=" + currentPage;

                const currentSearch = encodeURIComponent($("#dbtable_filter label input").val());
                if (currentSearch != "") {
                    hashPart += "&search=" + currentSearch;
                }

                const columnSearches = $('#dbtable').DataTable().columns().search();
                for (let i = 0; i < columnSearches.length; i++) {
                    var colSearch = columnSearches[i];

                    if (colSearch == "")
                        continue;

                    hashPart += "&colFilter[" + i + "]=" + encodeURIComponent(colSearch);
                }

                window.location.hash = hashPart;

                $('.popover').remove();

                $("[data-toggle=popover]").popover({
                    sanitize: false
                });
            });

        },
        "dataType": "json"
    });
}

let build;
let currentParams = [];

(function() {
    $('#fileFilter').select2();
    $('#lockedBuild').select2();
    let vars = {};
    let parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
        if (value.includes('#')) {
            const splitString = value.split('#');
            vars[key] = splitString[0];
        } else {
            vars[key] = value;
        }
    });

    if (vars["dbc"] == null) {
        currentParams["dbc"] = "";
    } else {
        currentParams["dbc"] = vars["dbc"].replace(".db2", "").toLowerCase().split('#')[0];
    }
    
    // if (vars["locale"] == null) {
    //     currentParams["locale"] = "";
    // } else {
    //     currentParams["locale"] = vars["locale"];
    // }

    // const localeSelection = document.getElementById("localeSelection");

    // for (let value of Object.entries(Locales)) {
    //     var option = document.createElement("option");
    //     option.val = value[1];
    //     option.text = value[0];

    //     if(currentParams["locale"] == value[0]){
    //         option.selected = true;
    //     }

    //     localeSelection.appendChild(option);
    // }

    if (vars["build"] == null) {
        currentParams["build"] = "";
        if ($('#buildFilter').val() != undefined && $('#buildFilter').val() != '') {
            currentParams["build"] = $('#buildFilter').val();
        }
    } else {
        currentParams["build"] = vars["build"];
    }

    currentParams["hotfixes"] = false;
    if (vars["hotfixes"] == "true") {
        currentParams["hotfixes"] = true;
        document.getElementById('hotfixToggle').checked = true;
    } else {
        document.getElementById('hotfixToggle').checked = false;
    }

    refreshFiles();
    refreshVersions();
    loadTable();

    $('#fileFilter').on('change', function() {
        if ($(this).val() != "" && $(this).val() != "Select a table") {
            currentParams["dbc"] = $(this).val();
            if (document.getElementById("buildFilter")) {
                clearState = true;
                refreshVersions();
                loadTable();
                updateDBDButton();
            } else {
                document.location = buildURL(currentParams);
            }
        }
    });

    $('#buildFilter').on('change', function() {
        currentParams["build"] = $('#buildFilter').val();
        loadTable();
    });

    // $('#localeSelection').on('change', function() {
    //     currentParams["locale"] = $('#localeSelection').val();
    //     loadTable();
    // });

    $('#hotfixToggle').on('change', function() {
        if (document.getElementById('hotfixToggle').checked) {
            currentParams["hotfixes"] = true;
        } else {
            currentParams["hotfixes"] = false;
        }
        loadTable();
    });

    // window.onpopstate = history.onpushstate = function(e) { console.log(e); }

    console.log("Refreshing files");

    /* FK search */
    var fkSearchDB = document.getElementById('fkSearchDB');
    fetch(API_URL + "/api/relations/")
        .then(function(fileResponse) {
            return fileResponse.json();
        }).then(function(data) {
            const dbsWithRelations = [];

            Object.keys(data).sort().forEach((fk) => {
                const splitFK = fk.split("::");
                if (!dbsWithRelations.includes(splitFK[0])) {
                    dbsWithRelations.push(splitFK[0]);
                }
            });

            fkSearchDB.innerHTML = "";

            var option = document.createElement("option");
            option.text = "Select a table";
            fkSearchDB.appendChild(option);

            dbsWithRelations.forEach((file) => {
                var option = document.createElement("option");
                option.value = file.toLowerCase();
                option.text = file;
                fkSearchDB.appendChild(option);
            });

            $('#fkSearchDB').select2();
        }).catch(function(error) {
            console.log("An error occurred retrieving files: " + error);
        });
    $('#foreignKeySearchModal').on('hidden.bs.modal', function() {
        document.getElementById("foreignKeySearchForm").style.display = 'flex';
        document.getElementById("fkSearchResultHolder").innerHTML = "";
    })
}());
</script>
<?php require_once(__DIR__ . "/../inc/footer.php"); ?>