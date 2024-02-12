function fillDiffModal(from, to){
    $( "#previewModalContent" ).load( "/monitor/scripts/diff.php?from=" + from + "&to=" + to);
}

$('#files').on( 'draw.dt', function () {
    var currentSearch = encodeURIComponent($("#files_filter label input").val());
    var currentPage = $('#files').DataTable().page() + 1;

    var sort = $('#files').DataTable().order();
    var sortCol = sort[0][0];
    var sortDir = sort[0][1];

    var product = $('#files').DataTable().column(1).search();

    var url = "search=" + currentSearch + "&page=" + currentPage + "&sort=" + sortCol +"&desc=" + sortDir;
    if(product){
        url += "&product=" + product.replace('/', '').replace('/', '');
    }

    window.location.hash = url;

    $("[data-toggle=popover]").popover();
});

$(document).ready(() => {
    (function() {
        var searchHash = location.hash.substr(1),
            searchString = searchHash.substr(searchHash.indexOf('search=')).split('&')[0].split('=')[1];
    
        if(searchString != undefined && searchString.length > 0){
            searchString = decodeURIComponent(searchString);
        }
    
        var page = (parseInt(searchHash.substr(searchHash.indexOf('page=')).split('&')[0].split('=')[1], 10) || 1) - 1;
        var selectedProduct = searchHash.substr(searchHash.indexOf('product=')).split('&')[0].split('=')[1];
        var products = MONITOR_PRODUCTS;
    
        var sortCol = searchHash.substr(searchHash.indexOf('sort=')).split('&')[0].split('=')[1];
        var sortDesc = searchHash.substr(searchHash.indexOf('desc=')).split('&')[0].split('=')[1];
    
        if(!sortCol){
            sortCol = 0;
        }
    
        if(!sortDesc){
            sortDesc = "desc";
        }
        var previewTypes = ["ogg", "mp3", "blp", "wmo", "m2"];
    
        var table = $('#files').DataTable({
            "processing": true,
            "serverSide": true,
            "searching": true,
            "search": { "search": searchString },
            "dom": "<'row'<'col-sm-6 col-md-2'l><'col-sm-12 col-md-10'pf>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "ajax": "scripts/api.php",
            "pageLength": 5,
            "displayStart": page * 5,
            "autoWidth": false,
            "pagingType": "input",
            "orderMulti": false,
            "order": [[sortCol, sortDesc]],
            "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
            "searchCols": [null, {"search": selectedProduct ? '/'+selectedProduct+'/' : '', regex: true }, null],
            initComplete: function () {
                this.api().columns().every( function (col) {
                    var column = this;
                    if(col == 1){
                        var select = $('<select id="productSelect" style="max-width: 100%"><option value="">Product</option></select>')
                            .appendTo( $(column.header()).empty() )
                            .on( 'change', function () {
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );
    
                                column
                                    .search( val ? '/'+val+'/' : '', true, false )
                                    .draw();
                            } );
    
                        products.forEach(function(product){
                            if(selectedProduct == product.product){
                                select.append('<option value="'+ product.product +'" SELECTED>'+product.name+' ('+product.product+')</option>');
                            }else{
                                select.append('<option value="'+ product.product +'">'+product.name+' ('+product.product+')</option>');
                            }
                        });
                    }
                } );
            },
            "columnDefs": [
                {
                    "targets": [1,2],
                    "orderable": false,
                }],
            "language": {"search": ""}
        });
    }());
});
