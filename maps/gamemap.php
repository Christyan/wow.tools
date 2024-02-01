<?php

require_once("../inc/header.php");
$build = "9.0.2.36086";
$buildconfig = "c981cf9ca7a2c7dc501a325539f169e4";
$cdnconfig = "a8b9a0a48cb2ca7afcca94463f78ecc0";
?>
<script src="/js/bufo.js"></script>
<script src="/js/js-blp.js?v=<?=filemtime(__DIR__ . "/../js/js-blp.js")?>"></script>
<style type='text/css'>
    .navbar{
        display: none;
    }

    #breadcrumbs{
        position: absolute;
        left: 50px;
        top: 10px;
        z-index: 1;
    }

    .breadcrumb{
        background-color: rgba(0,0,0,0);
    }

    #mapCanvas{
        position: absolute;
        top: 0px;
        left: 0px;
        /* width: 100%; */
        height: 100%;
        max-width: 100%;
        z-index: 0;
    }

    #finalCanvas{
        position: absolute;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 100%;
        max-width: 100%;
        z-index: 0;
    }

    #main{
        position: absolute;
        left: 0px;
        top: 0px;
        width: 100%;
        height: 100%;
    }

    #debug{
        position: absolute;
        left: 0px;
        bottom: 0px;
        width: 500px;
        height: 200px;
        z-index: 2;
    }
</style>
    <p style='height: 35px; position: absolute; z-index: 1; display: none'>
        <select name='map' id='mapSelect'>
            <option value = ''>Select a map</option>
        </select>
        <input type='checkbox' id='showExplored' CHECKED> <label for='showExplored'>Show explored?</label>
    </p>
    <div id='breadcrumbs' style='display: none'></div>
    <div id='debug'></div>
    <canvas id='mapCanvas' width='1024' height='1024'></canvas>
<script type='text/javascript'>
    var build = "<?=$build?>";

    /* Required DBs */
    const dbsToLoad = ["uimap", "uimapxmapart", "uimaparttile", "worldmapoverlay", "worldmapoverlaytile", "uimapart", "uimapartstylelayer", "uitextureatlasmember", "uitextureatlas", "uimaplink"];
    const dbPromises = dbsToLoad.map(db => loadDatabase(db, build));
    Promise.all(dbPromises).then(loadedDBs => databasesAreLoadedNow(loadedDBs));

    var uiMap = {};
    var uiMapXMapArt = {};
    var uiMapArtTile = {};
    var worldMapOverlay = {};
    var worldMapOverlayTile = {};
    var uiMapArt = {};
    var uiMapArtStyleLayer = {};
    var uiTextureAtlasMember = {};
    var uiTextureAtlas = {};
    var uiMapLink = {};

    var azerothOverlay = false;
    var outlandOveray = false;
    var draenorOverlay = false;

    var originalCosmicImg;
    var isCosmicOriginal = false;

    var highlightAreas = [];
    /* Secondary DBs */
    // const secondaryDBsToLoad = ["questpoiblob", "questpoipoint"];
    // const secondaryDBPromises = secondaryDBsToLoad.map(db => loadDatabase(db, build));
    // Promise.all(secondaryDBPromises).then(loadedDBs => secondaryDatabasesAreLoadedNow(loadedDBs));

    // var questPOIBlob = {};
    // var questPOIPoint = {};

    function databasesAreLoadedNow(loadedDBs){
        uiMap = loadedDBs[0];
        uiMapXMapArt = loadedDBs[1];
        uiMapArtTile = loadedDBs[2];
        worldMapOverlay = loadedDBs[3];
        worldMapOverlayTile = loadedDBs[4];
        uiMapArt = loadedDBs[5];
        uiMapArtStyleLayer = loadedDBs[6];
        uiTextureAtlasMember = loadedDBs[7];
        uiTextureAtlas = loadedDBs[8];
        uiMapLink = loadedDBs[9];

        loadedDBs[0].forEach(function (data){
            $("#mapSelect").append("<option value='" + data.ID + "'>" + data.ID + " - " + data.Name_lang);
        });

        let params = (new URL(document.location)).searchParams;
        if(params.has('id')){
            var id = params.get('id');
            renderMap(id);
        }
    }

    function secondaryDatabasesAreLoadedNow(loadedDBs){
        questPOIBlob = loadedDBs[0];
        questPOIPoint = loadedDBs[1];
    }

    function loadDatabase(database, build){
        console.log("Loading database " + database + " for build " + build);
        const header = loadHeaders(database, build);
        const data = loadData(database, build);
        return mapEntries(database, header, data);
    }

    function loadHeaders(database, build){
        console.log("Loading " + database + " headers for build " + build);
        return $.get("https://wow.tools/dbc/api/header/" + database + "/?build=" + build);
    }

    function loadData(database, build){
        console.log("Loading " + database + " data for build " + build);
        return $.post("https://wow.tools/dbc/api/data/" + database + "/?build=" + build + "&useHotfixes=true", { draw: 1, start: 0, length: 100000});
    }

    async function mapEntries(database, header, data){
        await header;
        await data;

        var dbEntries = [];

        var idCol = -1;
        header.responseJSON.headers.forEach(function (data, key){
            if (data == "ID"){
                idCol = key;
            }
        });

        data.responseJSON.data.forEach(function (data, rowID) {
            dbEntries[data[idCol]] = {};
            Object.values(data).map(function(value, key) {
                dbEntries[data[idCol]][header.responseJSON.headers[key]] = value;
            });
        });

        return dbEntries;
    }

    function generateBreadcrumb(uiMapID){
        var parent = uiMapID;

        var breadcrumbs = [];
        while(parent != 0){
            var row = getParentMapByUIMapID(parent);

            if(row == false){
                return;
            }

            parent = row.ParentUiMapID;
            breadcrumbs.unshift([row.ID, row.Name_lang]);
        }

        $("#breadcrumbs").html("<nav aria-label='breadcrumb'><ol id='breadcrumbList' class='breadcrumb'></ol></nav>");

        breadcrumbs.forEach(function (breadcrumb){
            $("#breadcrumbList").append("<li class='breadcrumb-item'><a onclick='renderMap("+ breadcrumb[0] + ")' href='#'>" + breadcrumb[1] + "</a></li>");
        });

    }

    function getParentMapByUIMapID(uiMapID){
        if(uiMapID in uiMap){
            return uiMap[uiMapID];
        }else{
            return false;
        }
    }

    function renderMap(uiMapID) {
        if ($("#mapSelect").val() != uiMapID) {
            $("#mapSelect").val(uiMapID);
        }

        generateBreadcrumb(uiMapID);

        const artStyle = getArtStyleByUIMapID(uiMapID);
        const canvas = document.getElementById("mapCanvas");
        canvas.width = artStyle.LayerWidth;
        canvas.height = artStyle.LayerHeight;

        const showExplored = $("#showExplored").prop("checked");

        const uiMapXMapArtRow = uiMapXMapArt.find(row => row && row.UiMapID == uiMapID);
        const uiMapArtID = uiMapXMapArtRow.UiMapArtID;

        if (uiMapXMapArtRow.PhaseID > 0) {
            console.log("Ignoring PhaseID " + uiMapXMapArtRow.PhaseID);
            return;
        }

        const unexploredPromises = uiMapArtTile
        .filter(row => row.UiMapArtID == uiMapArtID)
        .map(row => {
            const imagePosX = row.RowIndex * artStyle.TileWidth;
            const imagePosY = row.ColIndex * artStyle.TileHeight;
            const bgURL = `https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=maptile&filedataid=${row.FileDataID}`;

            return renderBLPToCanvasElement(bgURL, "mapCanvas", imagePosY, imagePosX);
        });


         fetch(API_URL + "/api/find/uimaplink?build=<?=$build?>&col=ParentUiMapID&val=" + uiMapID)
        .then(function (response) {
            return response.json();
        }).then(function (uiMapChildren) {
            uiMapChildren.forEach(function(uiMapChild){
                const childUIMapXMapArtRow = uiMapXMapArt.find(row => row && row.UiMapID == uiMapChild.ChildUiMapID);
                const childUIMapArtID = childUIMapXMapArtRow.UiMapArtID;

                const uiTextureAtlasMemberForMap = uiTextureAtlasMember[uiMapArt[childUIMapArtID].HighlightAtlasID];
                console.log(uiMapChild, uiTextureAtlasMemberForMap);

                var topLeft = normalizeCoord(uiMapID, uiMapChild["UiMin[0]"], uiMapChild["UiMin[1]"]);
                var bottomRight = normalizeCoord(uiMapID, uiMapChild["UiMax[0]"], uiMapChild["UiMax[1]"]);

                // Debug
                var ctx = canvas.getContext("2d");
                ctx.beginPath();
                console.log(topLeft);
                console.log(bottomRight);
                ctx.rect(topLeft[0], topLeft[1], (bottomRight[0] - topLeft[0]), (bottomRight[1] - topLeft[1]));
                ctx.strokeStyle = 'red';
                ctx.stroke();

                const uiTextureAtlasRow = uiTextureAtlas[uiTextureAtlasMemberForMap.UiTextureAtlasID];
                // console.log(uiTextureAtlasRow);
                const tempCanvas = document.createElement("canvas");
                tempCanvas.width = uiTextureAtlasRow.AtlasWidth;
                tempCanvas.height = uiTextureAtlasRow.AtlasHeight;
                const highlightURL = `https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=maphighlight&filedataid=${uiTextureAtlasRow.FileDataID}`;
                 fetch(highlightURL)
                .then(function(response) {
                    return response.arrayBuffer();
                })
                .then(function(arrayBuffer) {
                    let data = new Bufo(arrayBuffer);
                    let blp = new BLPFile(data);
                    let image = blp.getPixels(0, tempCanvas, 0, 0);

                    let scrollChildX = Number(uiMapChild["UiMin[0]"]);
                    let scrollChildY = Number(uiMapChild["UiMin[1]"]);

                    let textureX = uiMapChild["UiMax[0]"] - uiMapChild["UiMin[0]"];
                    let textureY = uiMapChild["UiMax[0]"] - uiMapChild["UiMin[1]"];

                    console.log("normalized scrollChildX", scrollChildX);
                    console.log("normalized scrollChildY", scrollChildY);
                    console.log("normalized textureX", textureX);
                    console.log("normalized textureY", textureY);

                    var centerPosX = ((scrollChildX + 0.5 * textureX) - 0.5) * artStyle.LayerWidth;
                    var centerPosY = ((scrollChildY + 0.5 * textureY) - 0.5) * artStyle.LayerHeight;
                   
                    var boxSizeX = (uiTextureAtlasMemberForMap.CommittedRight - uiTextureAtlasMemberForMap.CommittedLeft);
                    var boxSizeY = (uiTextureAtlasMemberForMap.CommittedBottom - uiTextureAtlasMemberForMap.CommittedTop);
                    
                    console.log("px", centerPosX, centerPosY);

                    ctx.beginPath();
                    ctx.strokeStyle = 'blue';
                    ctx.rect(scrollChildX * artStyle.LayerWidth, scrollChildY * artStyle.LayerHeight, (uiTextureAtlasMemberForMap.CommittedRight - uiTextureAtlasMemberForMap.CommittedLeft),uiTextureAtlasMemberForMap.CommittedBottom - uiTextureAtlasMemberForMap.CommittedTop);
                    ctx.stroke();

                    // ctx.drawImage(
                    //     tempCanvas, 
                    //     uiTextureAtlasMemberForMap.CommittedLeft, 
                    //     uiTextureAtlasMemberForMap.CommittedTop,
                    //     (uiTextureAtlasMemberForMap.CommittedRight - uiTextureAtlasMemberForMap.CommittedLeft),
                    //     (uiTextureAtlasMemberForMap.CommittedBottom - uiTextureAtlasMemberForMap.CommittedTop),
                    //     (scrollChildX * artStyle.LayerWidth),
                    //     (scrollChildY * artStyle.LayerHeight),
                    //     (uiTextureAtlasMemberForMap.CommittedRight - uiTextureAtlasMemberForMap.CommittedLeft), 
                    //     (uiTextureAtlasMemberForMap.CommittedBottom - uiTextureAtlasMemberForMap.CommittedTop)
                    // );


                    // ctx.drawImage(
                    //     tempCanvas, 
                    //     uiTextureAtlasMemberForMap.CommittedLeft, 
                    //     uiTextureAtlasMemberForMap.CommittedTop, 
                    //     (uiTextureAtlasMemberForMap.CommittedRight - uiTextureAtlasMemberForMap.CommittedLeft), 
                    //     (uiTextureAtlasMemberForMap.CommittedBottom - uiTextureAtlasMemberForMap.CommittedTop),
                    //     -50,
                    //     -30, 
                    //     461, 
                    //     391
                    // );
                });
            });
        });
        
        // Do UICanvas highlight stuff
        const uiMapArtRow = uiMapArt[uiMapArtID];
        console.log(uiMapArtRow);

        Promise.all(unexploredPromises).then(_ => {
            if (showExplored) {
                renderExplored();
            }else{
                // updateMainCanvas();
            }
        })

        updateURL();
    }

    function normalizeCoord(uiMapID, coordX, coordY){
        const artStyleLayer = getArtStyleByUIMapID(uiMapID);
        
        const normalizedCoordX = artStyleLayer.LayerWidth * coordX;
        const normalizedCoordY = artStyleLayer.LayerHeight * coordY;

        return {x: normalizedCoordX, y: normalizedCoordY};
    }

    function getArtStyleByUIMapID(uiMapID){
        const uiMapXMapArtRow = uiMapXMapArt.find(row => row && row.UiMapID == uiMapID);
        const uiMapArtID = uiMapXMapArtRow.UiMapArtID;
        const uiMapArtRow = uiMapArt[uiMapArtID];
        return uiMapArtStyleLayer.find(row => row && row.UiMapArtStyleID == uiMapArtRow.UiMapArtStyleID);
    }

    function renderExplored(){
        var showExplored = $("#showExplored").prop('checked');

        var uiMapID = $("#mapSelect").val();

        const artStyle = getArtStyleByUIMapID(uiMapID);
        const uiMapXMapArtRow = uiMapXMapArt.find(row => row && row.UiMapID == uiMapID);
        const uiMapArtID = uiMapXMapArtRow.UiMapArtID;

        worldMapOverlay.forEach(function(wmoRow){
            if(wmoRow.UiMapArtID == uiMapArtID){
                worldMapOverlayTile.forEach(function(wmotRow){
                    if(wmotRow.WorldMapOverlayID == wmoRow.ID){
                        var layerPosX = parseInt(wmoRow.OffsetX) + (wmotRow.ColIndex * (artStyle.TileWidth / 1));
                        var layerPosY = parseInt(wmoRow.OffsetY) + (wmotRow.RowIndex * (artStyle.TileHeight / 1));
                        var bgURL = "https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=exploredmaptile&filedataid=" + wmotRow.FileDataID;

                        renderBLPToCanvasElement(bgURL, "mapCanvas", layerPosX, layerPosY);
                    }
                });
            }
        });

        // updateMainCanvas();
    }

    function updateURL(){
        const uiMapID =  $("#mapSelect").val();
        if(uiMapID in uiMap){
            var title = "WoW.tools | Map Browser | " + uiMap[uiMapID].Name_lang;
        }else{
            var title = "WoW.tools | Map Browser";
        }

        var url = '/maps/gamemap.php?id=' + $("#mapSelect").val();

        window.history.pushState( {uiMapID: uiMapID}, title, url );

        document.title = title;
    }

    // function renderQuestBlob(){
    //  const uiMapID =  $("#mapSelect").val();
    //  const results = questPOIBlob.filter(row => row && row.UiMapID == uiMapID && row.NumPoints > 1);

    //  const canvas = document.getElementById('mapCanvas');
    //  const ctx = canvas.getContext('2d');

    //  results.forEach(function(result){
    //      console.log(result);
    //      const pointResults = questPOIPoint.filter(row => row && row.QuestPOIBlobID == result.ID);

    //      ctx.beginPath();
    //      pointResults.forEach(function(pointResult){
    //          const x = (parseInt(pointResult.X) + 10000) + canvas.width / 2;
    //          const y = (parseInt(pointResult.Y) + 0) + canvas.height / 2z;
    //          console.log("Drawing line between " + pointResult.X + " (" + x + ") and " + y);
    //          ctx.lineTo(x, y);
    //      });
    //      ctx.fill();
    //  });
    // }

    $('#mapSelect').on( 'change', function () {
        renderMap(this.value);
    });


    $("#showExplored").on("click", function (){
        if($(this).prop('checked') == false){
            renderMap($("#mapSelect").val());
        }else{
            renderExplored();
        }
    });

    // $("#mapCanvas").on("contextmenu", function (){
    //     var currentMap = $("#mapSelect").val();
    //     let parent = getParentMapByUIMapID(currentMap);
    //     console.log(parent);
    //     if(parent && parent.ParentUiMapID > 0){
    //         renderMap(parent.ParentUiMapID);
    //     }

    //     return false;
    // });

    $("#mapCanvas").on("mousemove", function (e){
        // var canvas = document.getElementById("mapCanvas");
        // var context = canvas.getContext("2d");

        // var pos = getMousePos(canvas, e);
        // $("#debug").html(Math.floor(pos.x) + " " + Math.floor(pos.y));

        // if(!originalCosmicImg){
        //     originalCosmicImg = context.getImageData(0, 0, canvas.width, canvas.height);
        // }

        // if(pos.x > 577 && pos.y > 282){
        //     if(!azerothOverlay){
        //         var bgURL = "https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=exploredmaptile&filedataid=137125";
        //         context.putImageData(originalCosmicImg, 0, 0);
        //         renderBLPToCanvasElement(bgURL, "mapCanvas", 0, 156);
        //         azerothOverlay = true;
        //         isCosmicOriginal = false;
        //     }
        // }else{
        //     azerothOverlay = false;
        // }

        // if(pos.x > 110 && pos.x < 415 && pos.y > 330){
        //     if(!outlandOveray){
        //         var bgURL = "https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=exploredmaptile&filedataid=137126";
        //         context.putImageData(originalCosmicImg, 0, 0);
        //         renderBLPToCanvasElement(bgURL, "mapCanvas", 0, 156);
        //         outlandOveray = true;
        //         isCosmicOriginal = false;
        //     }
        // }else{
        //     outlandOveray = false;
        // }

        // if(pos.x > 300 && pos.x < 630 && pos.y < 400){
        //     if(!draenorOverlay){
        //         var bgURL = "https://wow.tools/casc/file/fdid?buildconfig=<?=$buildconfig?>&cdnconfig=<?=$cdnconfig?>&filename=exploredmaptile&filedataid=1064798";
        //         context.putImageData(originalCosmicImg, 0, 0);
        //         renderBLPToCanvasElement(bgURL, "mapCanvas", 0, 0);
        //         draenorOverlay = true;
        //         isCosmicOriginal = false;
        //     }
        // }else{
        //     draenorOverlay = false;
        // }

        // if(!azerothOverlay && !draenorOverlay && !outlandOveray){
        //     context.putImageData(originalCosmicImg, 0, 0);
        // }
    });

    function getMousePos(canvas, evt) {
        var rect = canvas.getBoundingClientRect();
        return {
            x: (evt.clientX - rect.left) / (rect.right - rect.left) * canvas.width,
            y: (evt.clientY - rect.top) / (rect.bottom - rect.top) * canvas.height
        };
    }
</script>
