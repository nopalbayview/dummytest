<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>
<style>
  .main-content {
    margin-top: 100px;
  }
</style>
<div class="main-content content">
  <!-- Form Header -->
  <h5 class="fw-bold mb-3"><?= ($form_type == 'edit') ? 'Edit Invoice' : 'Tambah Invoice' ?></h5>
  <form id="form-invoice" class="form" enctype="multipart/form-data">
    <?php if ($form_type === 'edit'): ?>
      <input type="hidden" id="headerid" name="id"
        value="<?= !empty($headerid) ? $headerid : (!empty($row['id']) ? $row['id'] : '') ?>">
    <?php endif; ?>

    <div class="form-group mb-3">
      <label for="transcode" class="form-label fw-bold">Transcode</label>
      <input type="text" class="form-control form-control-sm" id="transcode" name="transcode"
        value="<?= ($form_type == 'edit') ? ($row['transcode'] ?? '') : '' ?>" required>
    </div>

    <div class="form-group mb-3">
      <label for="transdate" class="form-label fw-bold">Transaction Date</label>
      <input type="date" class="form-control form-control-sm" id="transdate" name="transdate"
        value="<?= ($form_type == 'edit') ? ($row['transdate'] ?? date('Y-m-d')) : date('Y-m-d') ?>" required>
    </div>

    <div class="form-group mb-3">
      <label for="customerid" class="form-label fw-bold">Customer</label>
      <select id="customerid" name="customerid" class="form-select form-select-sm" required>
        <option value="" selected disabled>Select Customer</option>
        <?php if ($form_type == 'edit' && !empty($row['customerid'])): ?>
          <option value="<?= $row['customerid'] ?>" selected>
            <?= $row['customername'] ?? 'Selected Customer' ?>
          </option>
        <?php endif; ?>
        <?php if (!empty($customers)): ?>
          <?php foreach ($customers as $c): ?>
            <?php if ($form_type == 'edit' && $row['customerid'] == $c['id']) continue; ?>
            <option value="<?= $c['id'] ?>">
              <?= $c['customername'] ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="form-group mb-3">
      <label for="description" class="form-label fw-bold">Description</label>
      <textarea class="form-control form-control-sm" id="description" name="description" rows="3"><?= ($form_type == 'edit') ? ($row['description'] ?? '') : '' ?></textarea>
    </div>

    <div class="modal-footer" style="gap: 10px">
      <button type="submit" id="btn-submit"
        class="btn btn-primary btn-sm d-flex align-items-center">
        <i class="bx bx-check margin-r-2"></i>
        <span class="fw-normal fs-7"><?= ($form_type == 'edit' ? 'Update' : 'Save') ?></span>
      </button>
      <button type="button"
        class="btn btn-secondary btn-sm d-flex align-items-center"
        onclick="window.location.href='<?= base_url('invoice') ?>'">
        <i class="bx bx-arrow-back margin-r-2"></i>
        <span class="fw-normal fs-7">Back</span>
      </button>
    </div>
  </form>

  <hr>

  <!-- Form Detail + Table -->
  <?php if ($form_type == 'edit') : ?>
    <h5 class="mt-4">Invoice Detail</h5>
    <form id="form-detail" class="form" enctype="multipart/form-data">
      <input type="hidden" name="headerid" value="<?= $row['id'] ?>">
      <input type="hidden" id="detailid" name="detailid" value="">

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Product</label>
        <select id="productid" name="productid" class="form-select form-select-sm" required>
          <option value="">Select Product</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
              <?= $p['productname'] ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">UOM</label>
        <select id="uomid" name="uomid" class="form-select form-select-sm" required>
          <option value="" selected disabled>Select UOM</option>
          <?php foreach ($uoms as $u): ?>
            <option value="<?= $u['id'] ?>"><?= $u['uomnm'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Qty</label>
        <input type="number" step="0.001" id="qty" name="qty"
          class="form-control form-control-sm"
          value="<?= isset($detail['qty']) ? number_format($detail['qty'], 0, '.', '') : '' ?>" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Price</label>
        <input type="number" step="0.01" id="price" name="price"
          class="form-control form-control-sm"
          value="<?= isset($detail['price']) ? number_format($detail['price'], 0, '.', '') : '' ?>" required>
      </div>

      <div class="modal-footer" style="gap: 10px;">
        <button type="submit" id="btn-detail" class="btn btn-primary btn-sm d-flex align-items-center">
          <i class="bx bx-check margin-r-2"></i>
          <span class="fw-normal fs-7">Add</span>
        </button>
        <button type="button" class="btn btn-warning dflex align-center" id="btn-reset" onclick="return resetDetailForm()">
          <i class="bx bx-revision margin-r-2"></i>
          <span class="fw-normal fs-7">Reset</span>
        </button>
      </div>

    </form>
    <hr>

    <!-- Tabel Detail -->
    <div class="card mt-4 shadow-sm w-100 gap">
      <div class="card-body">
        <div class="table-responsive margin-t-14p">
          <table class="table table-bordered table-responsive-lg fs-7 w-100" id="detailTable">
            <thead>
              <tr>
                <th class="tableheader">No</th>
                <th class="tableheader">Product</th>
                <th class="tableheader">UOM</th>
                <th class="tableheader">Qty</th>
                <th class="tableheader">Price</th>
                <th class="tableheader">Actions</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->include('template/v_footer') ?>
<script>
  $(document).ready(function() {

    // Submit header
    $('#form-invoice').on('submit', function(e) {
      e.preventDefault();
      let csrf = decrypter($("#csrf_token").val());
      $("#csrf_token_form").val(csrf);

      let form_type = "<?= $form_type ?>";
      let link = (form_type === 'edit') ?
        "<?= getURL('invoice/update') ?>" :
        "<?= getURL('invoice/add') ?>";

      $.ajax({
        type: 'POST',
        url: link,
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res) {
          $("#csrf_token").val(encrypter(res.csrfToken));
          $("#csrf_token_form").val("");
          showNotif(res.sukses ? 'success' : 'error', res.pesan);

          if (res.sukses == 1) {
            window.location.href = "<?= base_url('invoice') ?>";
            close_modal('modaldetail');
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload();
            }
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showError(thrownError + ", please contact administrator.");
        }
      });
    });

    // Submit detail
    $('#form-detail').on('submit', function(e) {
      e.preventDefault();

      let url = $('#detailid').val() ?
        "<?= base_url('invoice/updateDetail') ?>" :
        "<?= base_url('invoice/addDetail') ?>";

      $('#btn-detail').prop('disabled', true);

      $.ajax({
        type: 'POST',
        url: url,
        data: new FormData(this),
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(res) {
          showNotif(res.sukses ? 'success' : 'error', res.pesan);

          if (res.sukses) {
            resetDetailForm();
            $('#grandtotal').text(res.grandtotal);

            // reload detail table
            if (detailTbl) {
              detailTbl.ajax.reload(null, false);
            }

            // reload header table kalau ada
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload(null, false);
            }

            // update CSRF
            $("#csrf_token").val(encrypter(res.csrfToken));
          }
        },
        error: function(xhr, ajaxOptions, thrownError) {
          showError(thrownError + ", please contact administrator.");
        },
        complete: function() {
          $('#btn-detail').prop('disabled', false);
        }
      });
    });
    // Select2 server-side
    $('#customerid').select2({
      minimumResultsForSearch: 0,
      dropdownParent: $('#form-invoice'),
      ajax: {
        url: '<?= base_url("invoice/customer/list") ?>',
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

    $('#productid').select2({
      placeholder: 'Select Product',
      minimumResultsForSearch: 0,
      dropdownParent: $('body'),
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

    $('#uomid').select2({
      placeholder: 'Select UOM',
      minimumResultsForSearch: 0, 
      dropdownParent: $('body'),
      ajax: {
        url: '<?= base_url("invoice/uomList") ?>',
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

    // Set initial value for customerid in edit mode
    <?php if ($form_type == 'edit' && !empty($row['customerid'])): ?>
      $('#customerid').val('<?= $row['customerid'] ?>').trigger('change');
    <?php endif; ?>

    <?php if ($form_type == 'edit'): ?>
      loadTable();
    <?php endif; ?>
  });

  let detailTbl;

  function loadTable() {
    detailTbl = $('#detailTable').DataTable({
      serverSide: true,
      processing: true,

      ajax: {
        url: '<?= base_url("invoice/detailDatatable/" . encrypting((string)($row['id'] ?? 0))) ?>',
        type: 'POST',
      },
    });
  }

  function reloadTable() {
    $('#detailTable').DataTable().ajax.reload();
  }

  function deleteDataDt(these, id) {
    modalDelete('Hapus Detail Invoice', {
      'link': '<?= base_url('invoice/deleteDetail') ?>',
      'id': id,
      'pagetype': 'tabledetail'
    });
  }

  function resetDetailForm() {
    $('#form-detail')[0].reset();
    $('#productid').val(null).trigger('change');
    $('#uomid').val(null).trigger('change');
    $('#price').val('');
    $('#detailid').val('');
    $('#btn-detail')
      .html('<i class="bx bx-check me-1"></i> Add')
      .removeClass('btn-warning')
      .addClass('btn-primary');
  }
</script>