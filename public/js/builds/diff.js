function debounce(func, wait, immediate) {
    var timeout;
    return function() {
        var context = this,
            args = arguments;
        var later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        var callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
};

function actionToBadge(action){
    switch (action) {
        case "added":
            return "success";
        case "removed":
            return "danger";
        case "modified":
            return "warning";
    }
}

String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1)
}
$(document).ready(function() {
    var encrypted = BUILD_DIFF_ENCRYPTED;
    var encryptedbutnot = BUILD_DIFF_ENCRYPTEDBUTNOT;
    var table = $('#buildtable').DataTable({
        ajax: API_URL + BUILD_DIFF_API_URL,
        columns: [{
            data: 'action'
        },
            {
                data: 'id'
            },
            {
                data: 'filename'
            },
            {
                data: 'type'
            }
        ],
        pagingType: "input",
        pageLength: 25,
        autoWidth: false,
        deferRender: true,
        lengthMenu: [[25, 100, 500, -1], [25, 100, 500, "All"]],
        dom: "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'li><'col-sm-12 col-md-7'p>>",
        columnDefs: [{
            "targets": 0,
            "orderable": true,
            "render": function(data, type, full, meta) {

                var badge = actionToBadge(full.action);
                var content = "<span class='badge bg-" + badge + "'>" + full.action.capitalize() + "</span>";
                return content;
            }
        },
            {
                "targets": 2,
                "orderable": true,
                "createdCell": function(td, cellData, rowData, row, col) {
                    if (!cellData) {
                        $(td).css('background-color', '#ff5858');
                    }
                }
            },
            {
                "targets": 4,
                "render": function(data, type, full, meta) {
                    return "<a style='padding-top: 0px; padding-bottom: 0px; cursor: pointer' data-toggle='modal' data-target='#moreInfoModal' onClick='fillModal(" + full.id + ")'><i class='fa fa-info-circle'></i></a></td>";
                }
            },
            {
                "targets": 5,
                "render": function(data, type, full, meta) {
                    var content = "";
                    switch (full.action) {
                        case "added":
                            switch (full.type) {
                                case "db2":
                                    if (full.filename && full.filename != "Unknown") {
                                        var db2name = full.filename.replace("dbfilesclient/", "").replace(".db2", "");
                                        content = "<a href='/dbc/?dbc=" + db2name + "&build=" + BUILD_DIFF_TO + "' target='_BLANK'>View table</a>";
                                    }
                                    break;
                                case "m2":
                                case "wmo":
                                default:
                                    content = "N/A";
                                    break;
                            }

                            if(full.md5 == "de6135861a6cacfe176830f18f597c3e"){
                                content += " <span style='float: right'><a tabindex='0' role='button' data-trigger='hover' data-container='body' data-html='true' data-toggle='popover' data-placement='top' style='color: ;' data-content='<b>Placeholder audio</b><br> This file has no audio yet'><span class='fa-stack'><i class='fa fa-volume-off fa-stack-1x'></i><i class='fa fa-ban fa-stack-1x text-danger'></i></span></i></a></span>";
                            }
                            break;
                        case "modified":
                            switch (full.type) {
                                case "db2":
                                    if (full.filename && full.filename != "Unknown") {
                                        var db2name = full.filename.replace("dbfilesclient/", "").replace(".db2", "");
                                        content = "<a style='cursor: pointer' data-toggle='modal' data-target='#previewModal' onClick='fillDBCDiffModal(\"" + BUILD_DIFF_FROM + "\", \"" + BUILD_DIFF_TO + "\", \"" + db2name + "\")'>Preview</a>";

                                    }
                                    break;
                                // case "blp":
                                // case "htm":
                                // case "html":
                                // case "lua":
                                // case "sbt":
                                // case "toc":
                                // case "txt":
                                // case "xml":
                                // case "xsd":
                                // case "wtf":
                                //     content = "<a style='cursor: pointer' data-toggle='modal' data-target='#previewModal' onClick='fillDiffModal(\"<?= $fromBuild['hash'] ?>\", \"<?= $toBuild['hash'] ?>\", \"" + full.id + "\")'>Preview</a>";
                                //     break;
                                // case "ogg":
                                //     content = "<a style='cursor: pointer' data-toggle='modal' data-target='#previewModal' onClick='fillPreviewModalByContenthash(\"<?= $toBuild['hash'] ?>\", \"" + full.id + "\",\"" + full.md5 + "\")'>Preview</a>";
                                //     break;
                                default:
                                    content = "N/A";
                                    break;
                            }

                            if(full.md5 == "de6135861a6cacfe176830f18f597c3e" || full.md5 == "ea80e802952501021865cfeed808ac3f"){
                                content += " <span style='float: right'><a tabindex='0' role='button' data-trigger='hover' data-container='body' data-html='true' data-toggle='popover' data-placement='top' style='color: ;' data-content='<b>Placeholder audio</b><br> This file has no audio yet'><span class='fa-stack'><i class='fa fa-volume-off fa-stack-1x'></i><i class='fa fa-ban fa-stack-1x text-danger'></i></span></i></a></span>";
                            }
                            break;
                        case "removed":
                            switch (full.type) {
                                case "db2":
                                    if (full.filename && full.filename != "Unknown") {
                                        var db2name = full.filename.replace("dbfilesclient/", "");
                                        content = "<a href='/dbc/?dbc=" + db2name + "&build=' + BUILD_DIFF_FROM + '' target='_BLANK'>Preview</a>";
                                    }
                                    break;
                                default:
                                    content = "N/A";
                                    break;
                            }
                            break;
                    }
                    if(encrypted.includes(parseInt(full.id))){
                        content += " <i style=\"color: red\" title=\"File is encrypted, preview might be broken\" class=\"fa fa-lock\"></i>";
                    }

                    if(encryptedbutnot.includes(parseInt(full.id))){
                        content += " <i style=\"color: white\" title=\"Flagged as encrypted but not\" class=\"fa fa-unlock\"></i>";
                    }
                    return content;
                }
            }
        ],
        initComplete: function() {
            var table = this.api();
            $('#buildtable thead tr.filters th').each(function(index, element) {
                element = $(element);
                var column = table.column(index);
                if (element.hasClass("filterable")) {
                    var select = $('<select style="height: 20px; width: calc(100% - 25px);" class="form-control form-control-sm"><option value=""></option></select>')
                        .appendTo(element)
                        .on('change', function() {
                            var val = "^" + $(this).val() + "$"
                            if($(this).val() == "")
                                val = "";

                            table.column(index)
                                .search(val, true, false)
                                .draw();
                        });

                    column.data().unique().sort().each(function(d, j) {
                        if(d === "") {
                            return;
                        }
                        select.append('<option value="' + d + '">' + d + '</option>')
                    });
                } else if (element.hasClass("searchable")) {
                    $(this).html('<input class="form-control form-control-sm" type="text" style="height: 20px; width: calc(100% - 25px);" placeholder="Search" />');
                    $("input", this).on('keyup change', debounce(function() {
                        table.column(index).search(this.value).draw();
                    }, 50));
                }
            });
        },
        "drawCallback": function() {
            $('[data-toggle="popover"]').popover();
        }
    });

    window.table = table;

    table.on( 'xhr', function () {
        var json = table.ajax.json();
        $("#summary").html(" <span class='badge bg-" + actionToBadge("added") + "'>" + json['added'] + " added</span> <span class='badge bg-" + actionToBadge("modified") + "'>" + json['modified'] + " modified</span> <span class='badge bg-" + actionToBadge("removed") + "'>" + json['removed'] + " removed</span>");
    } );
});