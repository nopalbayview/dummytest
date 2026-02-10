<<<<<<< HEAD
<form id="importexcel" style="padding-inline: 0px;">
    <div class="row">
        <div>
            <div class="form-group">
                <label class="required">Excel File</label>
                <input type="file" name="excelfile" id="excelfile" accept=".xlsx, .xls" class="form-input" style="padding: 8px;pointer-events: unset !important;">
            </div>
        </div>
    </div>
    <div id="loading-alltrans" class="hiding">
        <h4>
            <i class='bx bx-loader-circle bx-spin text-info'></i> Processing <span class="text-primary" id="totalsent">0</span> / <span id="alltotals" class="text-primary">100</span>
        </h4>
    </div>
    <div class="modal-footer dflex" style="justify-content: space-between !important;">
        <button style="margin: 0 !important;" class="btn btn-info dflex align-center justify-center" type="button" onclick="downloadTemplate()">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Template</span>
        </button>
        <div style="margin-left: 0 !important; margin-right: 0 !important;" class="dflex">
            <button class="btn btn-warning dflex button-import align-center margin-r-2" type="button" onclick="close_modal('modaldetail')">
                <i class="bx bx-x margin-r-2"></i>
                <span class="fw-normal fs-7">Cancel</span>
            </button>
            <button class="btn btn-primary dflex button-import align-center" type="submit">
                <i class="bx bx-check margin-r-2"></i>
                <span class="fw-normal fs-7">Process</span>
            </button>
        </div>
    </div>
</form>
<script>
    function downloadTemplate() {
        var url = '<?= base_url('downloadable/Template Customer.xlsx') ?>';
        window.location.href = url;
    }

    async function getFiles(e) {
    e = e || window.event;
    let file = e.target.files[0];
    let data = await file.arrayBuffer();
    let wb = XLSX.read(data);
    let ws = wb.Sheets[wb.SheetNames[0]];

    let last_key = Object.keys(ws);
    last_key = last_key.filter(key => !key.startsWith('!'));
    let getlen = last_key[last_key.length - 1].replace(/[^0-9\.]/g, '');

    let arr = [];
    let offset = 100;
    let keys = 0;

    // total baris (dikurangi header)
    $("#alltotals").text(formatRupiah(getlen - 2));

    // mulai dari baris ke-2 (skip header)
    for (let o = 3; o <= getlen * 1; o++) {
        if (ws['B' + o] && ws['B' + o].v !== undefined) {
            keys++;
            arr.push([
                ws['B' + o]?.v ?? '', // Alamat
                ws['C' + o]?.v ?? '', // Telepon
                ws['D' + o]?.v ?? '', // Email
                ws['E' + o]?.v ?? '', // Nama Customer
                ws['F' + o]?.v ?? '', // Nama Customer
            ]);
        }

        if (keys == offset) {
            keys = 0;
            sendData(arr);
            arr = [];
        }
    }

    // kirim batch terakhir
    if (arr.length > 0) {
        sendData(arr, 't');
    }
}


    $(document).ready(function() {
        $("#importexcel").on('submit', function(e) {
            e.preventDefault();
            $(".button-import").attr('disabled', 'disabled');
            $("#excelfile").attr('onchange', 'getFiles(event)');
            $("#btn-close-modaldetail").addClass('hiding')
            $("#excelfile").trigger('change');
            $("#loading-alltrans").removeClass('hiding');
            $('#excelfile').attr('disabled', 'disabled')
            return false;
        })
    })

    undfhcustomer = 0

    async function sendData(arr, isfinish = 'f') {
        //untuk delay
        // await sleep(2000);
        //update progress jumlah data yang dikiirm
        let textproses = $("#totalsent").text();
        $("#totalsent").text(formatRupiah(exp_number(textproses) + arr.length));
        // untuk mengirim data ke back end
        $.ajax({
            url: '<?= base_url('customer/importExcel') ?>',
            type: 'post',
            dataType: 'json',
            data: {
                datas: JSON.stringify(arr),
                <?= csrf_token() ?>: decrypter($("#csrf_token").val())
            },
            async: true,
            success: function(res) {
                $('#excelfile').removeAttr('disabled');
                $("#csrf_token").val(encrypter(res.csrfToken));
                undfhcustomer += res.undfhcustomer
                //jika batch terakhir sudah selesai maka kirim notif
                if (isfinish == 't') {
                    showNotif("success", "Data updated successfully");
                    if (undfhcustomer >= 1) {
                        showNotif("error", `${undfhcustomer} customer dilewatkan`);
                    }
                    setTimeout(() => {
                        close_modal('modaldetail');
                        tbl.ajax.reload();
                    }, 250);
                }
                $(".button-import").removeAttr('disabled')
                $("#btn-close-modaldetail").removeClass('hiding')
            }
        })
    }
</script>
=======
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
>>>>>>> cf179c2c3b1d60e43f03294e62a7d219b42159cf
