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

function makeBuild(text){
    if (text == null){
        return "";
    }

    let rawdesc = text.replace("WOW-", "");
    const build  = rawdesc.substring(0, 5);

    rawdesc = rawdesc.replace(build, "").replace("patch", "");
    const descexpl = rawdesc.split("_");

    return descexpl[0] + "." + build;
}

function getFKCols(headers, fks){
    let fkCols = [];
    headers.forEach(function(header, index){
        Object.keys(fks).forEach(function(key) {
            if (key == header){
                fkCols[index] = fks[key];
            }
        });
    });
    return fkCols;
}

function openFKModal(value, location, build){
    const wowDBMap = new Map();
    wowDBMap.set("spell", "https://www.wowdb.com/spells/");
    wowDBMap.set("item", "https://www.wowdb.com/items/");
    wowDBMap.set("itemsparse", "https://www.wowdb.com/items/");
    wowDBMap.set("questv2", "https://www.wowdb.com/quests/");
    wowDBMap.set("creature", "https://www.wowdb.com/npcs/");
    wowDBMap.set("gameobjects", "https://www.wowdb.com/objects/");

    const wowheadMap = new Map();
    wowheadMap.set("spell", "https://www.wowhead.com/spell=");
    wowheadMap.set("item", "https://www.wowhead.com/item=");
    wowheadMap.set("itemsparse", "https://www.wowhead.com/item=");
    wowheadMap.set("questv2", "https://www.wowhead.com/quest=");
    wowheadMap.set("creature", "https://www.wowhead.com/npc=");
    wowheadMap.set("gameobjects", "https://www.wowhead.com/object=");

    const splitLocation = location.split("::");
    const db = splitLocation[0].toLowerCase();
    const col = splitLocation[1];
    const fkModal = document.getElementById("fkModalContent");

    fkModal.innerHTML = "<b>Lookup into table " + db + " on col '" + col + "' value '" + value + "'</b><br>";

    if (wowDBMap.has(db)){
        fkModal.innerHTML += " <a target='_BLANK' href='" + wowDBMap.get(db) + value + "' class='btn btn-warning btn-sm'>View on WoWDB</a>";
    }

    if (wowheadMap.has(db)){
        fkModal.innerHTML += " <a target='_BLANK' href='" + wowheadMap.get(db) + value + "' class='btn btn-warning btn-sm'>View on Wowhead</a>";
    }

    fkModal.innerHTML += "<table id='fktable' class='table table-condensed table-striped'><thead><tr><th style='width: 300px'>Column</th><th>Value</th></tr></thead></table>";

    const fkTable = document.getElementById("fktable");
    if (db == "spell" && col == "ID"){
        fetch(API_URL + "/api/peek/spellname?build=" + build + "&col=ID&val=" + value)
            .then(function (response) {
                return response.json();
            }).then(function (json) {
                if (json.values["Name_lang"] !== undefined){
                    fkTable.insertAdjacentHTML("beforeend", "<tr><td>Name <small>(from SpellName)</small></td><td>" + json.values["Name_lang"] + "</td></tr>");
                }
            });
    }

    Promise.all([
        fetch(API_URL + "/api/header/" + db + "?build=" + build),
        fetch(API_URL + "/api/peek/" + db + "?build=" + build + "&col=" + col + "&val=" + value)
    ])
        .then(async function (responses) {
            try {
                return Promise.all(responses.map(function (response) {
                    return response.json();
                }));
            } catch (error) {
                console.log(error);
                fkTable.insertAdjacentHTML("beforeend", "<tr><td colspan='2'>This row is not available in clients or an error occurred.</td></tr>");
            }
        }).then(function (data) {
            const headerjson = data[0];
            const json = data[1];

            if (!json || Object.keys(json.values).length == 0){
                fkTable.insertAdjacentHTML("beforeend", "<tr><td colspan='2'>This row is not available in clients, is a hotfix or is serverside-only.</td></tr>");
                return;
            }

            let fkTableHTML = "";
            Object.keys(json.values).forEach(function (key) {
                const val = json.values[key];
                if (key in headerjson.fks){
                    fkTableHTML += "<tr><td style='width: 300px;'>" + key + "</td>";

                    if (headerjson.fks[key] == "FileData::ID"){
                        fkTableHTML += "<td><a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' data-toggle='modal' data-target='#moreInfoModal' onclick='fillModal(" + val + ")'>" + val + "</a>";
                    } else if (headerjson.fks[key] == "SoundEntries::ID" && parseInt(build[0]) > 6){
                        fkTableHTML += "<td><a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' onclick='openFKModal(" + val + ", \"SoundKit::ID\", \"" + build + "\")'>" + val + "</a>";
                    } else if (headerjson.fks[key] == "Item::ID" && val > 0){
                        fkTableHTML += "<td><a data-build='" + build + "' data-tooltip='item' data-id='" + val + "' style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' onclick='openFKModal(" + val + ", \"" + headerjson.fks[key] + "\", \"" + build + "\")'>" + val + "</a>";
                    } else if (headerjson.fks[key] == "Spell::ID" || headerjson.fks[key] == "SpellName::ID" && val > 0){
                        fkTableHTML += "<td><a data-build='" + build + "' data-tooltip='spell' data-id='" + val + "' style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' onclick='openFKModal(" + val + ", \"" + headerjson.fks[key] + "\", \"" + build + "\")'>" + val + "</a>";
                    } else {
                        fkTableHTML += "<td><a data-build='" + build + "' data-tooltip='fk' data-id='" + val + "' data-fk='" + headerjson.fks[key] + "' style='padding-top: 0px; padding-bottom: 0px; cursor: pointer; border-bottom: 1px dotted;' onclick='openFKModal(" + val + ", \"" + headerjson.fks[key] + "\", \"" + build + "\")'>" + val + "</a>";
                    }

                    var cleanDBname = headerjson.fks[key].split('::')[0].toLowerCase();

                    if (wowDBMap.has(cleanDBname) && val != 0){
                        fkTableHTML += " <a target='_BLANK' href='" + wowDBMap.get(cleanDBname) + val + "' class='btn btn-warning btn-sm'>View on WoWDB</a>";
                    }

                    if (wowheadMap.has(cleanDBname) && val != 0){
                        fkTableHTML += " <a target='_BLANK' href='" + wowheadMap.get(cleanDBname) + val + "' class='btn btn-warning btn-sm'>View on Wowhead</a>";
                    }
                } else {
                    fkTableHTML += "<tr><td style='width: 300px;'>" + key + "</td><td>" + val;
                }


                const columnWithTable = db.toLowerCase() + "." + key;

                if (enumMap.has(columnWithTable)) {
                    var enumVal = getEnum(db.toLowerCase(), key, val);
                    if (val == '0' && enumVal == "Unk") {
                        // returnVar += val;
                    } else {
                        fkTableHTML += " <i>(" + enumVal + ")</i>";
                    }
                }

                if (conditionalEnums.has(columnWithTable)) {
                    let conditionalEnum = conditionalEnums.get(columnWithTable);
                    conditionalEnum.forEach(function(conditionalEnumEntry) {
                        let condition = conditionalEnumEntry[0].split('=');
                        let conditionTarget = condition[0].split('.');
                        let conditionValue = condition[1];
                        let resultEnum = conditionalEnumEntry[1];

                        let colTarget = headerjson["headers"].indexOf(conditionTarget[1]);

                        // Col target found?
                        if (colTarget > -1) {
                            if (json.values[colTarget] == conditionValue) {
                                var enumVal = getEnumVal(resultEnum, val);
                                if (val == '0' && enumVal == "Unk") {
                                    //
                                } else {
                                    fkTableHTML +=" <i>(" + enumVal + ")</i>";
                                }
                            }
                        }
                    });
                }

                fkTableHTML += "</td></tr>";
            });

            fkTable.insertAdjacentHTML("beforeend", fkTableHTML);

            fkModal.insertAdjacentHTML("beforeend", " <a target=\"_BLANK\" href=\"/dbc/?dbc=" + db.replace(".db2", "") + "&build=" + build + "#page=1&colFilter[" + headerjson.headers.indexOf(col) + "]=exact:" + value + "\" class=\"btn btn-primary\">Go to record</a>");
        }).catch(function (error) {
            console.log(error);
            fkTable.insertAdjacentHTML("beforeend", "<tr><td colspan='2'>This row is not available in clients or an error occurred.</td></tr>");
        });

    if (db == "soundkit" && col == "ID"){
        fkModal.insertAdjacentHTML("beforeend", "<div id='soundkitList'></div>");
        // TODO: Get rid of JQuery
        $( "#soundkitList" ).load( "/files/sounds.php?embed=1&skitid=" + value );
    }
}

function dec2hex(str, big = false){
    if (BigInt !== undefined && big){
        return (BigInt(str)).toString(16).replace('-', '');
    } else {
        return (parseInt(str) >>> 0).toString(16);
    }
}

function BGRA2RGBA(color){
    var hex = dec2hex(color).padStart(6, '0');

    for (var bytes = [], c = 0; c < hex.length; c += 2)
    {
        bytes.push(parseInt(hex.substr(c, 2), 16));
    }

    for (let i = 0; i < 4; i++){
        if (bytes[i] == undefined){
            bytes[i] = 0;
        }
    }
    console.log(color + " => #" + hex + " => " + bytes);

    let b = bytes[2];
    let g = bytes[1];
    let r = bytes[0];
    let a = 255;

    return "rgba(" + r + "," + g + "," + b + "," + a + ")";
}


function getFlagDescriptions(db, field, value, targetFlags = 0){
    let usedFlags = Array();
    if (targetFlags == 0){
        // eslint-disable-next-line no-undef
        targetFlags = flagMap.get(db + '.' + field);
    }

    if (BigInt === undefined){
        return [value];
    }

    if (value == "-1")
        return ["All"];

    for (let i = 0; i < 32; i++){
        let toCheck = BigInt(1) << BigInt(i);
        if (BigInt(value) & toCheck){
            if (targetFlags !== undefined && targetFlags[toCheck]){
                usedFlags.push(['0x' + "" + dec2hex(toCheck, true), targetFlags[toCheck]]);
            } else {
                usedFlags.push(['0x' + "" + dec2hex(toCheck, true), ""]);
            }
        }
    }

    return usedFlags;
}

function fancyFlagTable(flagArrs){
    if (flagArrs.length == 0){
        return "";
    }

    let tableHtml = "<table class=\"table table-sm table-striped\">";
    flagArrs.forEach((flagArr) => {
        tableHtml += "<tr><td>" + flagArr[0] + "</td><td>" + flagArr[1].replace("\"", "&quot;").replace("'", "&apos;") + "</td></tr>";
    });
    tableHtml += "</table>";

    return tableHtml;
}

function getEnum(db, field, value){
    // eslint-disable-next-line no-undef
    const targetEnum = enumMap.get(db + '.' + field);
    return getEnumVal(targetEnum, value);
}

function getEnumVal(targetEnum, value){
    if (targetEnum[value] !== undefined){
        if (Array.isArray(targetEnum[value])){
            return targetEnum[value][0];
        } else {
            return targetEnum[value];
        }
    } else {
        return "Unk";
    }
}

function parseLogic(l) { var i=0;var r = ""
    if (l & (1 << (16 + i))) r+='!'; r+='#'+i
    for (++i; i < 4; ++i) {
        let op = (l >> (2*(i-1))) & 3
        if (op == 1) r += ' | '; else if (op == 2) r+=' & '; else if (op == 0) continue
        if (l & (1 << (16 + i))) r+='!'; r+='#'+i
    }
    return r;
}

function parseDate(date){
    if (date == 0)
        return "";

    console.log("parsing " + date);

    let minute = date & 0x3F;
    if (minute == 63)
        minute = -1;

    console.log("minute", minute);
    
    let hour = (date >> 6) & 0x1F;
    if (hour == 31)
        hour = -1;

    console.log("hour", hour);

    let dotw = (date >> 11) & 0x7;
    if (dotw == 7)
        dotw = -1;
    
    console.log("day of the week", dotw);

    let dotm = (date >> 14) & 0x3F;
    if (dotm == 63){
        dotm -1;
    } else {
        dotm += 1;
    }
    
    console.log("day of the month", dotm);

    let month = (date >> 20) & 0xF;
    if (month == 15){
        month = -1;
    } else { 
        month += 1;
    }

    console.log("month", month);

    let year = (date >> 24) & 0x1F;
    if (year == 31){
        year = -1;
    } else {
        year += 2000;
    }

    console.log("year", year);

    let tz = (date >> 29) & 0x3;
    if (tz == 3)
        tz = -1;

    console.log("timezone", tz);

    if (dotm > 0 && month > 0 && year > 0){
        const utcDate = new Date(Date.UTC(year, month - 1, dotm, hour, minute, 0));
        return utcDate.toUTCString();
    }
}

function loadLogForm(pushID){
    fetch("/dbc/hotfix_api.php?logByPushID=" + pushID + "&cb=" + Date.now())
    .then(function (response) {
        return response.json();
    }).then(function (logEntry) {
        document.getElementById("logPushID").value = pushID;

        if(logEntry === false){
            return;
        }

        document.getElementById("logName").value = logEntry['name'];
        document.getElementById("logDescription").value = logEntry['description'];
        document.getElementById("logStatus").value = logEntry['status'];
        document.getElementById("logContributed").value = logEntry['contributedby'];
    }).catch(function (error) {
        console.log("An error occurred retrieving data: " + error);
    });
}

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




