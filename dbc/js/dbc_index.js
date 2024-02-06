let build;
let currentParams = [];

$(document).ready(() => {

    loadSettings();

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
});
