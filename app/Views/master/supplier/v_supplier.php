<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<div class="main-content content margin-t-4">
    <div class="card-header dflex align-center justify-end">
        <button class="btn btn-primary dflex align-center"
            onclick="return modalForm('Add Supplier', 'modal-lg', '<?= base_url('supplier/forms') ?>')">
            <i class="bx bx-plus-circle margin-r-2"></i>
            <span class="fw-normal fs-7">Add New</span>
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive margin-t-14p">
            <table class="table table-bordered table-master fs-7 w-100" id="dataTable">
                <thead>
                    <tr>
                        <th class="tableheader">No</th>
                        <th class="tableheader">Supplier</th>
                        <th class="tableheader">Address</th>
                        <th class="tableheader">Telephone</th>
                        <th class="tableheader">Email</th>
                        <th class="tableheader">File Path</th>
                        <th class="tableheader">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        if (!$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "<?= base_url('supplier/table') ?>",
                    "type": "POST",
                    "dataSrc": function (json) {
                        if (!json.data) {
                            console.error('Data structure is incorrect:', json);
                            return [];
                        }
                        return json.data;
                    }
                },
                "columns": [
                    { "data": "id" },
                    { "data": "suppliername" },
                    { "data": "address" },
                    { "data": "phone" },
                    { "data": "email" },
                    {
                        "data": "filepath", "render": function (data) {
                            return '<a href="' + data + '" target="_blank">' + data + '</a>';
                        }
                    },
                    {
                        "data": "actions", "render": function (data, type, row) {
                            return `
                                <button class="btn btn-danger btn-sm" onclick="modalDelete('${row.suppliername}', { link: '<?= base_url('supplier/delete') ?>', id: ${row.id} })">
                                    Delete
                                </button>`;
                        }
                    }
                ],
                "pagingType": "full_numbers",
                "lengthMenu": [5, 10, 25, 50],
                "language": {
                    "search": "Filter records:",
                    "lengthMenu": "Display _MENU_ records per page",
                    "zeroRecords": "No matching records found",
                    "info": "Showing page _PAGE_ of _PAGES_",
                    "infoEmpty": "No records available",
                    "infoFiltered": "(filtered from _MAX_ total records)"
                },
                "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
                    '<"row"<"col-sm-12"tr>>' +
                    '<"row"<"col-sm-5"i><"col-sm-7"p>>'
            });
        }
    });

    function modalForm(title, size, url) {
        $('#modalForm .modal-title').text(title);
        $('#modalForm .modal-dialog').addClass(size);
        $('#modalForm .modal-body').load(url);
        $('#modalForm').modal('show');
    }

    function modalDelete(title, data) {
        if (confirm('Are you sure you want to delete ' + title + '?')) {
            $.ajax({
                type: 'POST',
                url: data.link + '/' + data.id,
                dataType: 'json',
                success: function (response) {
                    if (response.success == 1) {
                        alert(response.message);
                        $('#dataTable').DataTable().ajax.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert(thrownError);
                }
            });
        }
    }
</script>
<?= $this->include('template/v_footer') ?>