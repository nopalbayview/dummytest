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
            <i class='bx bx-loader-circle bx-spin text-info'></i> Processing <span class="text-primary" id="progressPercent">0</span>%
        </h4>
    </div>
    <div class="modal-footer dflex" style="justify-content: space-between !important;">
        <button style="margin: 0 !important;" class="btn btn-info dflex align-center justify-center" type="button" onclick="downloadTemplate()">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Template</span>
        </button>
        <div style="margin-left: 0 !important; margin-right: 0 !important;" class="dflex">
            <button class="btn btn-warning dflex button-import align-center margin-r-2" type="button" onclick="handleCancel()">
                <i class="bx bx-x margin-r-2"></i>
                <span class="fw-normal fs-7">Cancel</span>
            </button>
            <button id="btn-process" class="btn btn-primary dflex align-center" type="submit">
                <i class="bx bx-check margin-r-2"></i>
                <span class="fw-normal fs-7">Process</span>
            </button>
        </div>
    </div>
</form>
<script>
    function downloadTemplate() {
        var url = '<?= base_url('/downloadable/TemplateInvoice.xlsx') ?>';
        window.location.href = url;
    }

    let isCancelled = false;
    let totalRows = 0;
    let processedRows = 0;

    async function getFiles(e) {
        isCancelled = false;
        processedRows = 0;
        e = e || window.event;
        let file = e.target.files[0];
        let data = await file.arrayBuffer();
        let wb = XLSX.read(data);
        let ws = wb.Sheets[wb.SheetNames[0]];

        let range = XLSX.utils.decode_range(ws['!ref']);
        totalRows = range.e.r - range.s.r;

        $("#progressPercent").text('0');

        let rows = [];
        for (let R = range.s.r + 1; R <= range.e.r; R++) {
            if (isCancelled) {
                return;
            }

            let rowData = [];
            for (let C = range.s.c + 1; C <= range.e.c; C++) {
                let cellRef = XLSX.utils.encode_cell({
                    r: R,
                    c: C
                });
                rowData.push(ws[cellRef] && ws[cellRef].v !== undefined ? ws[cellRef].v : '');
            }

            if (rowData[0] !== '') {
                rows.push(rowData);
            }
        }

        totalRows = rows.length;

        for (let i = 0; i < rows.length; i += 200) {
            if (isCancelled) {
                return;
            }

            let batch = rows.slice(i, i + 200);
            let isLast = (i + 200) >= rows.length;
            try {
                await sendData(batch, isLast ? 't' : 'f');
            } catch {
                return;
            }
        }
    }

    $(document).ready(function() {
        $("#importexcel").on('submit', function(e) {
            e.preventDefault();
            $("#btn-process").attr('disabled', 'disabled');
            $("#excelfile").attr('onchange', 'getFiles(event)');
            $("#btn-close-modaldetail").addClass('hiding')
            $("#excelfile").trigger('change');
            $("#loading-alltrans").removeClass('hiding');
            $('#excelfile').attr('disabled', 'disabled')
            return false;
        })
    })

    undfhinvoice = 0
    totalskipped = 0
    totalinserted = 0

    function sendData(arr, isfinish = 'f') {
        return new Promise((resolve, reject) => {
            if (isCancelled && isfinish === 't') {
                let percent = $("#progressPercent").text();
                showNotif("warning", `Import dibatalkan pada ${percent}%`);
                resolve();
                return;
            }

            if (arr.length === 0) {
                resolve();
                return;
            }

            $.ajax({
                url: '<?= base_url('invoice/importExcel') ?>',
                type: 'post',
                dataType: 'json',
                data: {
                    datas: JSON.stringify(arr),
                    <?= csrf_token() ?>: decrypter($("#csrf_token").val())
                },
                success: function(res) {
                    $('#excelfile').removeAttr('disabled');
                    $("#csrf_token").val(encrypter(res.csrfToken));
                    undfhinvoice += res.undfhinvoice;
                    totalskipped += res.skipped;
                    totalinserted += res.inserted;

                    processedRows += arr.length;
                    let percent = Math.round((processedRows / totalRows) * 100);
                    if (percent > 100) percent = 100;
                    $("#progressPercent").text(percent);

                    if (isfinish == 't' && !isCancelled) {
                        $("#progressPercent").text('100');
                        
                        let alertMessages = [];
                        let successMessages = [];
                        
                        if (totalinserted > 0) {
                            successMessages.push(`${totalinserted} data berhasil diimport`);
                        }
                        
                        if (totalskipped > 0) {
                            alertMessages.push(`${totalskipped} data sudah ada ( dilewatkan)`);
                        }
                        
                        if (undfhinvoice >= 1) {
                            if (res.invalidcustomerarr && res.invalidcustomerarr.length > 0) {
                                alertMessages.push(`${undfhinvoice} customer tidak ditemukan: ${res.invalidcustomerarr.join(', ')}`);
                            } else {
                                alertMessages.push(`${undfhinvoice} data tidak valid`);
                            }
                        }
                        
                        if (successMessages.length > 0) {
                            showNotif("success", successMessages.join('. '));
                        }
                        
                        if (alertMessages.length > 0) {
                            showNotif("error", alertMessages.join('. '));
                        }
                        
                        if (successMessages.length === 0 && alertMessages.length === 0) {
                            showNotif("info", "Tidak ada data yang diproses");
                        }
                        
                        setTimeout(() => {
                            close_modal('modaldetail');
                            location.reload();
                        }, 200);
                    }
                    $("#btn-close-modaldetail").removeClass('hiding');
                    resolve();
                },
                error: function() {
                    reject();
                }
            });
        });
    }

    function handleCancel() {
        isCancelled = true;
        $("#btn-process").removeAttr('disabled');
        let percent = $("#progressPercent").text();
        showNotif("error", `Import dibatalkan pada ${percent}%`);
        close_modal('modaldetail');
        location.reload();
    }
</script>