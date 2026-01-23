<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Import Customer</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info" role="alert">
                            <h4 class="alert-heading">Petunjuk Import</h4>
                            <p>Silakan unduh template Excel untuk import data customer. Pastikan format data sesuai dengan template yang disediakan.</p>
                            <hr>
                            <p class="mb-0">
                                <a href="<?= base_url('uploads/template/customer_template.xlsx') ?>" class="btn btn-primary btn-sm" download>
                                    <i class="bx bx-download"></i> Download Template
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                <form id="formImport" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="file_excel">Pilih File Excel</label>
                                <input type="file" class="form-control" id="file_excel" name="file_excel" accept=".xlsx,.xls" required>
                                <small class="form-text text-muted">Format file: .xlsx atau .xls</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary" id="btnImport">
                                <i class="bx bx-upload"></i> Import Data
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closeModal()">
                                <i class="bx bx-x"></i> Batal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#formImport').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        Swal.fire({
            title: 'Memproses...',
            text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?= base_url('customer/importExcel') ?>',
            type: 'post',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.close();
                if (response.sukses == '1') {
                    let message = 'Import berhasil!';
                    if (response.undfhcustomer > 0) {
                        message += `\n${response.undfhcustomer} data tidak valid: ${response.undfhcustomerarr.join(', ')}`;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        closeModal();
                        reloadTable();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.err || 'Terjadi kesalahan saat import',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat mengirim data',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});
</script>
