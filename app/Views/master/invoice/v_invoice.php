    <?= $this->include('template/v_header') ?>
    <?= $this->include('template/v_appbar') ?>

    <div class="main-content content margin-t-4">
        <div class="card-header dflex align-center" style="gap:10px;">
            <div class="dflex align-center" style="gap:16px;flex-wrap:wrap;">
                <div>
                    <label class="small mb-0">Transdate Start</label>
                    <input type="date" id="f_from" class="form-control form-control-sm py-1" style="width: 110px;">
                </div>
                <div>
                    <label class="small mb-0">Transdate End</label>
                    <input type="date" id="f_to" class="form-control form-control-sm py-1" style="width: 110px;">
                </div>
                <div>
                    <label class="small mb-0">Customer</label>
                    <select id="f_customer" class="form-control form-control-sm py-1" style="width: 150px;">
                        <option value="">Select Customer</option>
                    </select>
                </div>
                <div class="d-flex gap-1">
                    <button id="btnFilter" class="btn btn-success btn-sm py-1">
                        <i class="bx bx-filter margin-r-2"></i>Filter
                    </button>
                    <button id="btnReset" class="btn btn-secondary btn-sm py-1">
                        <i class="bx bx-revision margin-r-2"></i>Reset
                    </button>
                </div>
            </div>
        </div>

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
            <button class="btn btn-primary dflex align-center margin-l-2" onclick="return modalForm('Import Invoice', 'modal-lg', '<?= getURL('invoice/formImport') ?>')">
                <i class="bx bx-download margin-r-2"></i>
                <span class="fw-normal fs-7">Import Excel</span>
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
        <input type="hidden" id="filter_start_date" value="">
        <input type="hidden" id="filter_end_date" value="">
        <input type="hidden" id="filter_customer_id" value="">
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
        // Initialize Select2 Customer Filter
        $('#f_customer').select2({
            minimumResultsForSearch: 0,
            dropdownParent: $('body'),
            placeholder: 'Select Customer',
            allowClear: true,
            ajax: {
                url: '<?= base_url("invoice/customer/list") ?>',
                type: 'POST',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.items
                    };
                }
            }
        });

        $('#btnFilter').on('click', function() {
            console.log('Filter clicked');
            console.log('f_from:', $('#f_from').val());
            console.log('f_to:', $('#f_to').val());
            console.log('f_customer:', $('#f_customer').val());

            $('#filter_start_date').val($('#f_from').val());
            $('#filter_end_date').val($('#f_to').val());
            $('#filter_customer_id').val($('#f_customer').val());

            console.log('filter_start_date after set:', $('#filter_start_date').val());
            console.log('filter_end_date after set:', $('#filter_end_date').val());
            console.log('filter_customer_id after set:', $('#filter_customer_id').val());

            $('#dataTable').DataTable().ajax.reload();
        });

        // Reset Button
        $('#btnReset').on('click', function() {
            $('#f_from').val('');
            $('#f_to').val('');
            $('#f_customer').val('').trigger('change');
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
            $('#filter_customer_id').val('');
            $('#dataTable').DataTable().ajax.reload();
        });

        // ===== EXPORT EXCEL STATE =====
        let cancelExport = false;
        let exportXHR = null;

        function exportData() {
            let limit = 500;
            let offset = 0;
            let allData = [];

            // Get current filter values
            let startDate = $('#f_from').val();
            let endDate = $('#f_to').val();
            let customerId = $('#f_customer').val();

            cancelExport = false;
            $("#exportProgressPercent").text("0%");
            $("#exportProgressText").text("Preparing...");
            $("#exportLoadingModal").modal('show');

            // Build query string with filters
            let queryParams = 'limit=1&offset=999999';
            if (startDate) queryParams += '&startDate=' + encodeURIComponent(startDate);
            if (endDate) queryParams += '&endDate=' + encodeURIComponent(endDate);
            if (customerId) queryParams += '&customerId=' + encodeURIComponent(customerId);

            // Get total count with filters
            $.getJSON('invoice/getHeaderChunk?' + queryParams, function(res) {
                let total = res.total || 1000;

                function fetchChunk() {
                    if (cancelExport) {
                        $("#exportLoadingModal").modal('hide');
                        return;
                    }

                    let chunkQueryParams = 'limit=' + limit + '&offset=' + offset;
                    if (startDate) chunkQueryParams += '&startDate=' + encodeURIComponent(startDate);
                    if (endDate) chunkQueryParams += '&endDate=' + encodeURIComponent(endDate);
                    if (customerId) chunkQueryParams += '&customerId=' + encodeURIComponent(customerId);

                    exportXHR = $.getJSON('invoice/getHeaderChunk?' + chunkQueryParams, function(data) {
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
                            // Export Excel with filter parameters
                            let exportQueryParams = '';
                            if (startDate) exportQueryParams += '&startDate=' + encodeURIComponent(startDate);
                            if (endDate) exportQueryParams += '&endDate=' + encodeURIComponent(endDate);
                            if (customerId) exportQueryParams += '&customerId=' + encodeURIComponent(customerId);

                            window.location.href = 'invoice/export?' + exportQueryParams.substring(1);
                            // Close modal after starting download
                            setTimeout(function() {
                                $("#exportLoadingModal").modal('hide');
                            }, 1500);
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