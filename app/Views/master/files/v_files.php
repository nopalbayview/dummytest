<?=  $this->include('template/v_header') ?>
<?=  $this->include('template/v_appbar') ?>
<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header dflex align-center justify-end">
            <button class="btn btn-primary dflex align-center" onclick="return modalForm('Add File', 'modal-lg', '<?= getURL('files/form/') ?>')">
                <i class="bx bx-plus-circle margin-r-2"></i>
                <span class="fw-normal fs-7">Add New</span>
            </button>
        </div>
        <div class="card-body">
            <!-- DataTable -->
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-master fs-7 w-100" id="fileTable">
                    <thead>
                        <tr>
                            <td class="tableheader">No</td>
                            <td class="tableheader">File Real Name</td>
                            <td class="tableheader">File Directory</td>
                            <td class="tableheader">Created Date</td>
                            <td class="tableheader">Created By</td>
                            <td class="tableheader">Actions</td>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->include('template/v_footer') ?>

<script>
var tbl = null;

$(document).ready(function() {
    // Destroy existing DataTable if already initialized
    if ($.fn.DataTable.isDataTable('#fileTable')) {
        $('#fileTable').DataTable().destroy();
    }
    
    // CSRF token helper
    function getCsrfToken() {
        var csrf = $('#csrf_token').val();
        if (csrf && typeof decrypter === 'function') {
            return decrypter(csrf);
        }
        return csrf || '';
    }
    
    tbl = $('#fileTable').DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        ajax: {
            url: '<?= base_url("files/table") ?>',
            type: 'POST',
            data: function(d) {
                d["<?= csrf_token() ?>"] = getCsrfToken();
                return d;
            },
            dataSrc: function(json) {
                // Update CSRF token from response
                if (json.csrfToken && typeof encrypter === 'function') {
                    $('#csrf_token').val(encrypter(json.csrfToken));
                }
                return json.data;
            }
        },
        columns: [
            { data: '0' },
            { data: '1' },
            { data: '2' },
            { data: '3' },
            { data: '4' },
            { data: '5' }
        ],
        order: [[3, 'desc']],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: 'Tidak ada data file',
            zeroRecords: 'Tidak ada data yang cocok'
        }
    });
});
</script>

