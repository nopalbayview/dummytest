<form id="form-files" style="padding-inline: 0px;" enctype="multipart/form-data">
    <?= csrf_field() ?>
    
    <input type="hidden" id="form_type" name="form_type" value="<?= $form_type ?>">
    <?php if ($form_type == 'edit' && !empty($encrypted_id)) { ?>
        <input type="hidden" id="id" name="id" value="<?= $encrypted_id ?>">
    <?php } ?>
    
    <div class="form-group">
        <label>Upload File:</label>
        <div class="dropzone" id="fileDropzone">
            <div class="dz-message">
                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                <p class="mt-2">Drag & drop file di sini atau klik untuk pilih file</p>
                <small class="text-muted">Semua format file</small>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-warning" onclick="close_modal('modaldetail')">
            <i class="bx bx-x margin-r-2"></i> Cancel
        </button>
        <button type="button" class="btn btn-primary" id="btn-upload" disabled>
            <i class="bx bx-upload margin-r-2"></i> <?= $form_type == 'edit' ? 'Update' : 'Upload' ?>
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    Dropzone.autoDiscover = false;
    
    var form_type = "<?= $form_type ?>";
    var uploadUrl = form_type === 'edit' 
        ? '<?= base_url('files/update') ?>' 
        : '<?= base_url('files/upload') ?>';
    
    var myDropzone = new Dropzone("#fileDropzone", {
        url: uploadUrl,
        paramName: 'filedirectory',
        maxFilesize: 50,
        addRemoveLinks: true,
        dictRemoveFile: 'Hapus',
        dictCancelUpload: 'Batal',
        uploadMultiple: false,
        parallelUploads: 1,
        autoProcessQueue: false,
        clickable: '.dz-message, .dz-button',
        timeout: 60000,
        acceptedFiles: null,
        
        init: function() {
            var dz = this;
            var uploadBtn = document.getElementById('btn-upload');
            
            this.on('addedfile', function(file) {
                if (dz.files.length > 0) {
                    uploadBtn.disabled = false;
                    var btnText = form_type === 'edit' ? 'Update' : 'Upload';
                    uploadBtn.innerHTML = '<i class="bx bx-upload margin-r-2"></i> ' + btnText;
                }
            });
            
            this.on('removedfile', function(file) {
                if (dz.files.length === 0) {
                    uploadBtn.disabled = true;
                    var btnText = form_type === 'edit' ? 'Update' : 'Upload';
                    uploadBtn.innerHTML = '<i class="bx bx-upload margin-r-2"></i> ' + btnText;
                }
            });
            
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (dz.files.length > 0) {
                    dz.processQueue();
                }
            });
            
            this.on('sending', function(file, xhr, formData) {
                var csrf = $('input[name="csrf_token"]').val();
                if (csrf) {
                    formData.append('csrf_token', csrf);
                }
                
                var formType = document.getElementById('form_type').value;
                formData.append('form_type', formType);
                
                if (formType === 'edit') {
                    var idField = document.getElementById('id');
                    if (idField && idField.value) {
                        formData.append('id', idField.value);
                    } else {
                        console.log('ID field not found or empty');
                    }
                }
            });
            
            this.on('success', function(file, response) {
                console.log('Response:', response);
                try {
                    var res;
                    
                    if (typeof response === 'object') {
                        res = response;
                    } else {
                        res = JSON.parse(response);
                    }
                    
                    // Update CSRF token dari response
                    if (res.csrfToken && typeof encrypter === 'function') {
                        $('#csrf_token').val(encrypter(res.csrfToken));
                    }
                    
                    // Check for success - field from controller is 'sukses' (satu s)
                    if (res.sukses == 1 || res.sukses == '1') {
                        // Reload DataTable SEBELUM close modal
                        if ($.fn.DataTable.isDataTable('#fileTable')) {
                            $('#fileTable').DataTable().ajax.reload(null, false);
                        }
                        
                        showNotif('success', res.pesan);
                        close_modal('modaldetail');
                    } else {
                        showNotif('error', res.pesan || 'Operasi gagal');
                    }
                } catch(e) {
                    console.error('Parse error:', e);
                    console.log('Response:', response);
                    showNotif('error', 'Response tidak valid');
                }
                dz.removeFile(file);
            });
            
            this.on('error', function(file, message) {
                showNotif('error', message || 'Upload gagal');
                dz.removeFile(file);
            });
            
            this.on('queuecomplete', function() {
                var btnText = form_type === 'edit' ? 'Update' : 'Upload';
                uploadBtn.innerHTML = '<i class="bx bx-upload margin-r-2"></i> ' + btnText;
            });
        }
    });
});
</script>
