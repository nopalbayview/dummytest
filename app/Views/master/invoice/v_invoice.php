    <?= $this->include('template/v_header') ?>
    <?= $this->include('template/v_appbar') ?>

    <div class="main-content content margin-t-4">
        <div class="card-header dflex align-center justify-end">
            
            <button class="btn btn-primary d-flex align-center">
            <a href="<?= site_url('invoice/form') ?>"
                class="btn btn-primary d-flex align-center">
                <i class="bx bx-plus-circle margin-r-2"></i>
                <span class="fw-normal fs-7">Add New</span>
            </a>
            </button>
            <button class="btn btn-primary d-flex align-center margin-l-2" onclick="exportData()">
                <i class="bx bx-upload margin-r-2"></i>
                <span class="fw-normal fs-7">Export Excel</span>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-responsive-lg table-master fs-7 w-100" id="dataTable">
                    <thead>
                        <tr>
                            <td class="tableheader">No</td>
                            <td class="tableheader">Transcode</td>
                            <td class="tableheader">Transdate</td>
                            <td class="tableheader">Customer Name</td>
                            <td class="tableheader">Grand Total</td>
                            <td class="tableheader">Description</td>
                            <td class="tableheader">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?= $this->include('template/v_footer') ?>
    <!-- Loading Modal -->
    <div id="exportLoadingModal" class="modal fade" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Exporting Invoice...</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="mb-2">
                            <span id="exportProgressText">Preparing...</span>
                        </div>
                        <div class="text-center mt-1">
                            <span id="exportProgressPercent" class="fw-bold fs-4" style="color: #28a745;">0%</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted" id="exportStatus">Please Wait...</small>
                        <small class="text-muted" id="exportChunkInfo"></small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="btnExportCancel">
                        <i class="bx bx-x margin-r-2"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ===== EXPORT EXCEL STATE =====
    let cancelExport = false;
    let exportXHR = null;
    
    function exportData() {
        let limit = 500;
        let offset = 0;
        let allData = [];

        cancelExport = false;
        $("#exportProgressPercent").text("0%");
        $("#exportProgressText").text("Preparing...");
        $("#exportLoadingModal").modal('show');

        // Get total count
        $.getJSON('invoice/getHeaderChunk?limit=1&offset=999999', function(res) {
            let total = res.total || 1000;
            
            function fetchChunk() {
                if (cancelExport) { 
                $("#exportLoadingModal").modal('hide');
                return;
                }
                
                exportXHR = $.getJSON('invoice/getHeaderChunk?limit=' + limit + '&offset=' + offset, function(data) {
                    if (cancelExport) return;
                    
                    if (data.rows && data.rows.length > 0) {
                        allData = allData.concat(data.rows);
                        offset += data.rows.length;
                        
                        let percent = Math.min(100, Math.floor((offset / total) * 100));
                        $("#exportProgressPercent").text(percent + "%");
                        $("#exportProgressText").text("Fetching chunk...");
                        
                        fetchChunk();
                    } else {
                        $("#exportProgressText").text("Generating Excel file...");
                        // Export Excel
                        exportXHR = $.ajax({
                            url: 'invoice/export',
                            type: 'POST',
                            data: { headers: JSON.stringify(allData) },
                            xhrFields: { responseType: 'blob' },
                            success: function(blob) {
                                // Validasi blob
                                if (!blob || blob.size === 0) {
                                    alert('Export failed: No data received');
                                    $("#exportLoadingModal").modal('hide');
                                    return;
                                }

                                try {
                                    // Method utama: createObjectURL
                                    var url = window.URL.createObjectURL(blob);
                                    var link = document.createElement('a');
                                    link.href = url;
                                    link.download = "Invoice.xlsx";
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                    window.URL.revokeObjectURL(url);
                                } catch (error) {
                                    // Fallback: FileReader untuk browser lama
                                    try {
                                        var reader = new FileReader();
                                        reader.onload = function() {
                                            var link = document.createElement('a');
                                            link.href = reader.result;
                                            link.download = "Invoice.xlsx";
                                            document.body.appendChild(link);
                                            link.click();
                                            document.body.removeChild(link);
                                        };
                                        reader.readAsDataURL(blob);
                                    } catch (fallbackError) {
                                        alert('Export failed. Please try using a different browser or contact administrator.');
                                    }
                                }

                                $("#exportLoadingModal").modal('hide');
                            },
                            error: function(xhr, status, error) {
                                if (cancelExport) return;
                                alert('Export failed. Please try again.');
                                $("#exportLoadingModal").modal('hide');
                            }
                        });
                    }
                });
            }  
            fetchChunk();
        });
    }

    // Cancel Handler
    $("#btnExportCancel").click(function() {
        cancelExport = true;
        if (exportXHR) exportXHR.abort();
        $("#exportLoadingModal").modal('hide');
    });


    function submitData() {
        let link = $('#linksubmit').val(),
            transcode = $('#transcode').val(),
            transdate = $('#transdate').val(),
            customername = $('#customername').val(),
            description = $('#description').val(),
            id = $('#id').val();
        $.ajax({
            url: link,
            type: 'post',
            dataType: 'json',
            data: {
                transcode: transcode,
                transdate: transdate,
                customername: customername,
                description: description,
                id: id
            },
            success: function(res) {
                if (res.sukses === '1') {
                    alert(res.pesan);
                    $('#transcode').val("");
                    $('#transdate').val("");
                    $('#customername').val("");
                    $('#description').val("");
                    tbl.ajax.reload();
                } else {
                    alert(res.pesan);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert("Request gagal: " + thrownError);
            }
        });
    }
    </script>