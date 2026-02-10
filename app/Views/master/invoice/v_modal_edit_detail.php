<!-- Modal Edit Detail Invoice -->
<div class="modal fade" id="editDetailModal" tabindex="-1" aria-labelledby="editDetailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editDetailModalLabel">Edit Detail Invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-edit-detail" class="form" enctype="multipart/form-data">
          <input type="hidden" id="detailid" name="detailid" value="">
          <input type="hidden" name="headerid" value="<?= $row['id'] ?? '' ?>">

          <div class="form-group mb-3">
            <label class="form-label fw-bold">Product</label>
            <select id="edit-productid" name="productid" class="form-select form-select-sm" required>
              <option value="">Select Product</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label class="form-label fw-bold">UOM</label>
            <select id="edit-uomid" name="uomid" class="form-select form-select-sm" required>
              <option value="" selected disabled>Select UOM</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label class="form-label fw-bold">Qty</label>
            <input type="number" step="0.001" id="edit-qty" name="qty" class="form-control form-control-sm" required>
          </div>

          <div class="form-group mb-3">
            <label class="form-label fw-bold">Price</label>
            <input type="number" id="edit-price" name="price" class="form-control form-control-sm" min="0" step="0.01" required>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" form="form-edit-detail" id="btn-update-detail" class="btn btn-primary btn-sm d-flex align-items-center">
          <i class="bx bx-check me-1"></i> Update
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Initialize Select2 for edit modal
    $('#edit-productid').select2({
      placeholder: '-- Select Product --',
      minimumResultsForSearch: 0,
      dropdownParent: $('#editDetailModal'),
      ajax: {
        url: '<?= base_url("invoice/product/list") ?>',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: params => ({
          search: params.term
        }),
        processResults: data => ({
          results: data.items
        })
      }
    });

    $('#edit-uomid').select2({
      placeholder: '-- Select UOM --',
      minimumResultsForSearch: 0,
      dropdownParent: $('#editDetailModal'),
      ajax: {
        url: '<?= base_url("invoice/uomList") ?>',
        type: 'POST',
        dataType: 'json',
        delay: 250,
        data: params => ({
          search: params.term
        }),
        processResults: data => ({
          results: data.results
        })
      }
    });

    // Submit edit detail form
    $('#form-edit-detail').on('submit', function(e) {
      e.preventDefault();
      
      let url = "<?= base_url('invoice/updateDetail') ?>";

      $.ajax({
        type: 'POST',
        url: url,
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res) {
          if (res.sukses) {
            showNotif('success', res.pesan);
            
            // Close modal
            $('#editDetailModal').modal('hide');
            
            // Reload detail table
            $('#detailTable').DataTable().ajax.reload(null, false);
            
            // Reload header table if needed
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload(null, false);
            }
            
            // Update CSRF token
            $("#csrf_token").val(encrypter(res.csrfToken));
          } else {
            showNotif('error', res.pesan);
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showError(thrownError + ", please contact administrator.");
        }
      });
    });

    // Validasi input angka untuk field price di modal
    $('#edit-price').on('input', function() {
      let value = $(this).val();
      // Hanya izinkan angka dan titik desimal
      let cleanedValue = value.replace(/[^0-9.]/g, '');
      // Pastikan hanya ada satu titik desimal
      let parts = cleanedValue.split('.');
      if (parts.length > 2) {
        cleanedValue = parts[0] + '.' + parts.slice(1).join('');
      }
      $(this).val(cleanedValue);
    });

    // Prevent paste non-numeric characters
    $('#edit-price').on('paste', function(e) {
      let pasteData = e.originalEvent.clipboardData.getData('text');
      if (!/^[0-9.]+$/.test(pasteData)) {
        e.preventDefault();
      }
    });

    // Reset form when modal is hidden
    $('#editDetailModal').on('hidden.bs.modal', function() {
      $('#form-edit-detail')[0].reset();
      $('#edit-productid, #edit-uomid').val(null).trigger('change');
      $('#detailid').val('');
    });
  });

  // Function to open edit modal with data
  function openEditDetailModal(id, productId, uomId, qty, price, productName = '', uomName = '') {
    // Set detail ID
    $('#detailid').val(id);
    
    // Untuk Product - buat option baru jika belum ada
    if ($('#edit-productid').find(`option[value="${productId}"]`).length === 0) {
        $('#edit-productid').append(new Option(productName || 'Product', productId, true, true));
    }
    $('#edit-productid').val(productId).trigger('change');
    
    // Untuk UOM - buat option baru jika belum ada
    if ($('#edit-uomid').find(`option[value="${uomId}"]`).length === 0) {
        $('#edit-uomid').append(new Option(uomName || 'UOM', uomId, true, true));
    }
    $('#edit-uomid').val(uomId).trigger('change');
    
    // Set quantity dan price
    $('#edit-qty').val(parseFloat(qty));
    $('#edit-price').val(parseFloat(price));

    // Open modal
    $('#editDetailModal').modal('show');
  }
</script>