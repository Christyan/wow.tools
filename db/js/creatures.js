var Elements = {};

async function loadCreatureInfo(id){
    const response = await fetch("/db/creature_api.php?id=" + id, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    });
    return response.json();
}

function renderCreatureInfo(id, info){
    let result = "";
    result += "<h2>" + info["Name[0]"];

    if(info["Title"] != ""){
        result += "<small>&lt;" + info['Title'] + "&gt;</small>"
    }

    result += " <a target='_BLANK' href='https://wowhead.com/npc=" + id + "' class='btn btn-sm align-middle btn-outline-primary'>Wowhead</a> ";
    result += "<a target='_BLANK' href='https://wowdb.com/npcs/" + id + "' class='btn btn-sm align-middle btn-outline-primary'>WoWDB</a></h2>";

    var mvEnabled = document.getElementById("enableMV").checked;
    if(mvEnabled){
        result += "<iframe width='950' height='700' src='https://wow.tools/mv/?filedataid=" + info["filedataid"] + "&type=m2&embed=true'></iframe>";
        result += "<div id='tableContainer'>";
    }else{
        result += "<div>";
    }

    result += "<table class='table table-sm table-striped table-hover' id='creatureInfoTable'></table></div>";

    $("#creatures_preview").html(result);

    Object.keys(info).forEach(function (key) {
        const val = info[key];
        if(val != ""){
            $("#creatureInfoTable").append("<tr><td>" + key + "</td><td>" + val + "</td></tr>");
        }

    });
}
function locationHashChanged(event) {
    var searchHash = location.hash.substr(1),
        searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];

    if(searchString != undefined && searchString.length > 0){
        searchString = decodeURIComponent(searchString);
    }

    if($("#creatures_filter label input").val() != searchString){
        $('#creatures').DataTable().search(searchString).draw(false);
    }
    var page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1], 10) || 1) - 1;
    if($('#creatures').DataTable().page() != page){
        $('#creatures').DataTable().page(page).draw(false);
    }

    var sortCol = searchHash.substr(searchHash.indexOf('sort=')).split('&')[0].split('=')[1];
    if(!sortCol){
        sortCol = 0;
    }

    var sortDesc = searchHash.substr(searchHash.indexOf('desc=')).split('&')[0].split('=')[1];
    if(!sortDesc){
        sortDesc = "asc";
    }

    var curSort = $('#creatures').DataTable().order();
    if(sortCol != curSort[0][0] || sortDesc != curSort[0][1]){
        $('#creatures').DataTable().order([sortCol, sortDesc]).draw(false);
    }
}

$(document).ready(() => {
    (function() {
        var searchHash = location.hash.substr(1),
            searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];

        if(searchString != undefined && searchString.length > 0){
            searchString = decodeURIComponent(searchString);
        }

        var page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1], 10) || 1) - 1;
        var sortCol = searchHash.substr(searchHash.indexOf('sort=')).split('&')[0].split('=')[1];
        if(!sortCol){
            sortCol = 0;
        }

        var sortDesc = searchHash.substr(searchHash.indexOf('desc=')).split('&')[0].split('=')[1];
        if(!sortDesc){
            sortDesc = "asc";
        }

        Elements.table = $('#creatures').DataTable({
            "processing": true,
            "serverSide": true,
            "search": { "search": searchString },
            "ajax": "/db/creature_api.php",
            "pageLength": 25,
            "displayStart": page * 25,
            "autoWidth": false,
            "pagingType": "input",
            "orderMulti": false,
            "order": [[sortCol, sortDesc]]
        });

        $('#creatures').on( 'draw.dt', function () {
            var currentSearch = encodeURIComponent($("#creatures_filter label input").val());
            var currentPage = $('#creatures').DataTable().page() + 1;

            var sort = $('#creatures').DataTable().order();
            var sortCol = sort[0][0];
            var sortDir = sort[0][1];

            var url = "search=" + currentSearch + "&page=" + currentPage + "&sort=" + sortCol +"&desc=" + sortDir;

            window.location.hash = url;

            $("[data-toggle=popover]").popover();
        });

        $('#creatures').on('click', 'tbody tr td', function() {
            $("#creatures_preview").html("Loading..");
            var data = Elements.table.row($(this).parent()).data();
            loadCreatureInfo(data[0])
                .then(returnedData => {
                    renderCreatureInfo(data[0], returnedData); // JSON data parsed by `response.json()` call
                });

            $(".selected").removeClass("selected");
            $(this).parent().addClass('selected');
        });

    }());

    window.onhashchange = locationHashChanged;    
})
