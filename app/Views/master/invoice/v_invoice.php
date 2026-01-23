<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header dflex align-center justify-between">
            <div class="dflex align-center" style="gap: 10px;">
            </div>
            <button class="btn btn-primary dflex align-center" onclick="return modalForm('Add Invoice', 'modal-lg', '<?= getURL('invoice/form') ?>')">
                <i class="bx bx-plus-circle margin-r-2"></i>
                <span class="fw-normal fs-7">Add New</span>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-master fs-7 w-100">
                    <thead>
                        <tr>
                            <td class="tableheader">No</td>
                            <td class="tableheader">Transcode</td>
                            <td class="tableheader">Transdate</td>
                            <td class="tableheader">Customer</td>
                            <td class="tableheader">Grandtotal</td>
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
</div>
<?= $this->include('template/v_footer') ?>
</body>
</html>
<script>
    function submitData() {
        let link = $('#linksubmit').val(),
            transcode = $('#transcode').val(),
            transdate = $('#transdate').val(),
            customerid = $('#customerid').val(),
            grandtotal = $('#grandtotal').val(),
            description = $('#description').val();

        $.ajax({
            url: link,
            type: 'post',
            dataType: 'json',
            data: {
                transcode: transcode,
                transdate: transdate,
                customerid: customerid,
                grandtotal: grandtotal,
                description: description,
            },
            success: function(res) {
                if (res.sukses == '1') {
                    alert(res.pesan);
                    $('#transcode').val("");
                    $('#transdate').val("");
                    $('#customerid').val("");
                    $('#grandtotal').val("");
                    $('#description').val("");
                    $('#addInvoiceModal').modal('hide');
                    tbl.ajax.reload();
                } else {
                    alert(res.pesan);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError);
            }
        })
    }

    $(document).ready(function() {
        $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url("invoice/table") ?>',
                type: 'POST'
            },
            columns: [
                { data: 0 }, // No
                { data: 1 }, // Transcode
                { data: 2 }, // Transdate
                { data: 3 }, // Customer Name
                { data: 4 }, // Grand Total
                { data: 5 }, // Description
                { data: 6 }  // Actions
            ]
        });
    });
</script>