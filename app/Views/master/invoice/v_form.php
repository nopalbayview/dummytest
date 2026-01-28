<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<style>
  .main-content {
    max-height: 85vh;
    /* tinggi maksimal 85% layar */
    overflow-y: auto;
    /* scroll vertikal otomatis */
    overflow-x: hidden;
    /* sembunyikan scroll horizontal */
    padding-right: 8px;
    /* biar scrollbar tidak nutup konten */
    
  }
</style>

 

<div class="main-content content margin-t-4">
  <!-- CSRF Token -->
  <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>">
  <input type="hidden" id="csrf_token_form" name="csrf_test_name" value="">
  
  <!-- Form Header -->
  <h5 class="fw-bold mb-3"><?= ($form_type == 'edit') ? 'Edit Invoice' : 'Tambah Invoice' ?></h5>
  <form id="form-invoice" class="form" enctype="multipart/form-data">
    <?php if ($form_type == 'edit') : ?>
      <input type="hidden" id="id" name="id" value="<?= $headerid ?? ($row['id'] ?? '') ?>">
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
        <?php if (!empty($customers ?? [])): ?>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= ($form_type == 'edit' && $row['customerid'] == $c['id']) ? 'selected' : '' ?>>
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

    <div class="form-footer mt-3">
      <button type="submit" id="btn-submit" class="btn btn-primary btn-sm d-flex align-items-center">
        <i class="bx bx-check me-1"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>  
    </div>
  </form>

  <hr>

  <!-- Form Detail + Table -->
  <?php if ($form_type == 'edit') : ?>
    <?php if (empty($row['id'])): ?>
      <div class="alert alert-warning">
        <strong>Warning:</strong> Invoice ID not found. Detail table cannot be loaded.
      </div>
    <?php else: ?>
      <h5 class="mt-4">Invoice Detail</h5>
    <?php endif; ?>
    <form id="form-detail" class="form" enctype="multipart/form-data">
      <input type="hidden" name="headerid" value="<?= $row['id'] ?>">
      <input type="hidden" id="detailid" name="detailid" value="">

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Product</label>
        <select id="productid" name="productid" class="form-select form-select-sm" required>
          <option value="">Select Product</option>
          <?php foreach (($products ?? []) as $p): ?>
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
          <?php foreach (($uoms ?? []) as $u): ?>
            <option value="<?= $u['id'] ?>"><?= $u['uomnm'] ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Qty</label>
        <input type="number" step="0.001" id="qty" name="qty" class="form-control form-control-sm" required>
      </div>

      <div class="form-group mb-3">
        <label class="form-label fw-bold">Price</label>
        <input type="number" id="price" name="price" class="form-control form-control-sm" min="0" step="0.01" required>
      </div>

      <div class="modal-footer">
        <button type="submit" id="btn-detail" class="btn btn-primary btn-sm d-flex align-items-center">
          <i class="bx bx-check me-1"></i> Add
        </button>
        <button type="button" class="btn btn-warning dflex align-center" id="btn-reset">
          <i class="bx bx-refresh me-1"></i> Reset
        </button>
      </div>
    </form>
    <hr>

    <!-- Tabel Detail -->
    <?php if (!empty($row['id'])): ?>
    <div class="card mt-4 shadow-sm w-100 gap">
      <div class="card-body">
        <div class="table-responsive margin-t-14p">
          <table class="table table-bordered table-responsive-lg table-master fs-7 w-100" id="detailTable">
            <thead class="table-light">
              <tr>
                <td class="tableheader">No</td>
                <td class="tableheader">Product</td>
                <td class="tableheader">UOM</td>
                <td class="tableheader">Qty</td>
                <td class="tableheader">Price</td>
                <td class="tableheader">Actions</td>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?= $this->include('template/v_footer') ?>

<script>
  $(document).ready(function() {

    // Submit header
    $('#form-invoice').on('submit', function(e) {
      e.preventDefault();
      let csrf = $("#csrf_token").val(); // No decrypter for CSRF token
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
            // Both add and edit modes redirect to listing
            setTimeout(function() {
              window.location.href = '<?= base_url("invoice") ?>';
            }, 1500); // Wait 1.5 seconds for notification
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
            // reset form ke mode Add
            $('#form-detail')[0].reset();
            $('#productid, #uomid').val(null).trigger('change');
            $('#detailid').val('');
            $('#btn-detail')
              .html('<i class="bx bx-check me-1"></i> Add')
              .removeClass('btn-warning')
              .addClass('btn-primary');

            // update grandtotal di form
            $('#grandtotal').text(res.grandtotal);
            
            // reload detail table
            $('#detailTable').DataTable().ajax.reload(null, false);

            // reload header table kalau perlu
            if (typeof tbl !== 'undefined') {
              tbl.ajax.reload(null, false);
            }

            // 
            $("#csrf_token").val(encrypter(res.csrfToken));
          }
        }
      });
    });

    // Select2 dari server-side
    $('#customerid').select2({
      minimumResultsForSearch: 0,
      dropdownParent: $(document.body),
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

    // Set customer yang sudah dipilih di Select2 (untuk mode edit)
    <?php if ($form_type == 'edit'): ?>
    let selectedCustomerId = '<?= $row['customerid'] ?? '' ?>';
    let selectedCustomerName = '<?= $row['customername'] ?? '' ?>';

    if (selectedCustomerId) {
        if ($('#customerid').find(`option[value="${selectedCustomerId}"]`).length === 0) {
            $('#customerid').append(new Option(selectedCustomerName || 'Customer', selectedCustomerId, true, true));
        }
        $('#customerid').val(selectedCustomerId).trigger('change');
    }
    <?php endif; ?>

    $('#productid').select2({
      placeholder: '-- Select Product --',
      minimumResultsForSearch: 0,
      dropdownParent: $(document.body),
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
      placeholder: '-- Select UOM --',
      minimumResultsForSearch: 0,
      dropdownParent: $(document.body),
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

    // Delay DataTable initialization to avoid conflicts
    setTimeout(function() {
        loadTable();
    }, 100);
    
    // Validasi input angka untuk field price
    $('#price').on('input', function() {
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
    $('#price').on('paste', function(e) {
      let pasteData = e.originalEvent.clipboardData.getData('text');
      if (!/^[0-9.]+$/.test(pasteData)) {
        e.preventDefault();
      }
    });

    // Initialize detail table only in edit mode
    <?php if ($form_type == 'edit'): ?>
    // Ensure DataTable is properly initialized
    if (typeof $.fn.DataTable !== 'undefined') {
// loadTable() will be called after initialization
    } else {
      console.error('DataTable library not loaded');
    }
    <?php endif; ?>
  });

  function loadTable() {
    // Check if DataTable already initialized
    if ($.fn.DataTable.isDataTable('#detailTable')) {
      // Destroy existing DataTable before reinitializing
      $('#detailTable').DataTable().destroy();
    }
    
    $('#detailTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '<?= base_url("invoice/detailDatatable") ?>',
        type: 'POST',
        data: function(d) {
          var formId = $('input[name="id"]').val() || '<?= $row['id'] ?? 0 ?>';
          d.headerid = formId;
          d.csrf_test_name = '<?= csrf_hash() ?>';
          console.log('Detail table headerid:', d.headerid);
        },
        error: function(xhr, error, thrown) {
          console.error('DataTables Error:', xhr.status, error, thrown);
          console.error('Response:', xhr.responseText);
          
          // Show user-friendly error
          if (xhr.status === 400) {
            console.error('Bad Request - Header ID issue');
          } else if (xhr.status === 404) {
            console.error('Route not found - check URL');
          } else {
            console.error('Server error occurred');
          }
        }
      },
      columns: [
        { data: 0 },
        { data: 1 },
        { data: 2 },
        { data: 3 },
        { data: 4 },
        { data: 5 }
      ]
    });


    $('#btn-reset').on('click', function() {
      resetDetailForm();
    });
  }

  // Show error message if headerid is 0
  <?php if ($form_type == 'edit' && empty($row['id'])): ?>
  console.error('Header ID is empty, cannot load detail table');
  <?php endif; ?>

  function deleteDataDt(these, params) {
    let id = params;

    // Gunakan modalDelete yang sama dengan datatable utama
    modalDelete(
        'Delete Detail Item',
        {
            'link': '<?= base_url("invoice/deleteDetail") ?>',
            'id': id,
            'pagetype': 'detail'
        }
    );
  }

  function reloadTable() {
    $('#detailTable').DataTable().destroy();
    loadTable();
  }

  function openEditModal(detailId) {
    console.log('openEditModal called with detailId:', detailId);
    
    // Load detail data via AJAX
    $.ajax({
        url: '<?= base_url("invoice/getSingleDetail") ?>',
        type: 'POST',
        data: {
            detailid: detailId,
            csrf_test_name: $('#csrf_token').val()
        },
        dataType: 'json',
        success: function(res) {
            console.log('AJAX response:', res);
            if (res.sukses == 1) {
                // Generate modal form HTML with data
                let formHtml = generateEditDetailForm(res.data);
                console.log('Generated form HTML length:', formHtml.length);
                
                // Manually show modal using existing infrastructure
                $('#modaldetail-title').html('<h4>Edit Invoice Detail</h4>');
                $('#modaldetail-form').html(formHtml);
                $('#modaldetail-size').removeClass().addClass('modal-dialog modal-lg');
                
                console.log('About to show modal...');
                $('#modaldetail').modal('show');
                
                // Initialize Select2 after modal is shown
                setTimeout(function() {
                    console.log('Initializing Select2...');
                    initializeModalSelect2(res.data);
                    initializeModalValidation();
                }, 300);
            } else {
                showNotif('error', res.pesan);
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            console.error('AJAX Error:', thrownError);
            showError(thrownError + ", please contact administrator.");
        }
    });
  }

  function generateEditDetailForm(data) {
    return `
      <form id="edit-detail-form" class="form">
        <input type="hidden" id="edit-detailid" name="detailid" value="${data.id}">
        
        <div class="form-group mb-3">
          <label class="form-label fw-bold">Product</label>
          <select id="edit-productid" name="productid" class="form-select form-select-sm" required>
            <option value="">Select Product</option>
            <option value="${data.productid}" selected>${data.productname}</option>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">UOM</label>
          <select id="edit-uomid" name="uomid" class="form-select form-select-sm" required>
            <option value="">Select UOM</option>
            <option value="${data.uomid}" selected>${data.uomnm}</option>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">Qty</label>
          <input type="number" step="0.001" id="edit-qty" name="qty" class="form-control form-control-sm" value="${data.qty}" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label fw-bold">Price</label>
          <input type="number" id="edit-price" name="price" class="form-control form-control-sm" min="0" step="0.01" value="${data.price}" required>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="resetEditForm();">Reset</button>
          <button type="button" class="btn btn-warning" onclick="submitEditDetailForm()">
            <i class="bx bx-check me-1"></i> Update
          </button>
        </div>
      </form>
    `;
  }

  function submitEditDetailForm() {
    let formData = {
        detailid: $('#edit-detailid').val(),
        productid: $('#edit-productid').val(),
        uomid: $('#edit-uomid').val(),
        qty: $('#edit-qty').val(),
        price: $('#edit-price').val(),
        csrf_test_name: $('#csrf_token').val()
    };

    $.ajax({
        type: 'POST',
        url: '<?= base_url("invoice/updateDetail") ?>',
        data: formData,
        dataType: "json",
        success: function(res) {
            if (res.sukses == 1) {
                showNotif('success', res.pesan);
                
                // Close modal using existing function
                close_modal('modaldetail');
                
                // Reload detail table
                $('#detailTable').DataTable().ajax.reload(null, false);
                
                // Update CSRF token
                $("#csrf_token").val(encrypter(res.csrfToken));
            } else {
                showNotif('error', res.pesan);
                if (res.csrfToken) {
                    $("#csrf_token").val(encrypter(res.csrfToken));
                }
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            showError(thrownError + ", please contact administrator.");
        }
    });
  }

  function initializeModalSelect2(data) {
    // Initialize Product Select2
    $('#edit-productid').select2({
        placeholder: '-- Select Product --',
        minimumResultsForSearch: 0,
        dropdownParent: $('#modaldetail'),
        ajax: {
            url: '<?= base_url("invoice/product/list") ?>',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: params => ({
                search: params.term
            }),
            processResults: function(data) {
                return {
                    results: data.items
                };
            }
        }
    });

    // Initialize UOM Select2
    $('#edit-uomid').select2({
        placeholder: '-- Select UOM --',
        minimumResultsForSearch: 0,
        dropdownParent: $('#modaldetail'),
        ajax: {
            url: '<?= base_url("invoice/uomList") ?>',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: params => ({
                search: params.term
            }),
            processResults: function(data) {
                return {
                    results: data.results
                };
            }
        }
    });
  }

  function resetEditForm() {
    console.log('Resetting edit form...');
    
    // Reset all form fields
    $('#edit-detailid').val('');
    $('#edit-productid').val(null).trigger('change');
    $('#edit-uomid').val(null).trigger('change');
    $('#edit-qty').val('');
    $('#edit-price').val('');
    
    console.log('Form reset completed');
  }
  
  function initializeModalValidation() {
    // Validasi input angka untuk price field
    $('#edit-price').on('input', function() {
        let value = $(this).val();
        let cleanedValue = value.replace(/[^0-9.]/g, '');
        let parts = cleanedValue.split('.');
        if (parts.length > 2) {
            cleanedValue = parts[0] + '.' + parts.slice(1).join('');
        }
        $(this).val(cleanedValue);
    });

    // Prevent paste non-numeric characters for price field
    $('#edit-price').on('paste', function(e) {
        let pasteData = e.originalEvent.clipboardData.getData('text');
        if (!/^[0-9.]+$/.test(pasteData)) {
            e.preventDefault();
        }
    });
  }

  function resetDetailForm() {
    $('#form-detail')[0].reset();
    $('#productid, #uomid').val(null).trigger('change');
    $('#price').val('');
    $('#detailid').val('');
    $('#btn-detail')
      .html('<i class="bx bx-check me-1"></i> Add')
      .removeClass('btn-warning')
      .addClass('btn-primary'); // ubah tombol kembali ke Add
  }
</script>

<!-- Notification functions (moved from footer to avoid conflicts) -->
<script>
// Initialize Notyf if not exists
if (typeof notyf === 'undefined') {
    var notyf = new Notyf({
        position: {
            x: 'right',
            y: 'top',
        },
        types: [{
            type: 'process',
            background: 'grey',
            icon: {
                className: 'bx bx-radio-circle bx-burst bx-md text-white',
                tagName: 'i',
                text: ''
            }
        }]
    });
}

function showNotif(type, msg, duration = 2000) {
    notyf.open({
        type: type,
        message: msg,
        duration: duration
    });
}

function showSuccess(msg) {
    notyf.success(msg);
}

function showError(msg) {
    notyf.error(msg);
}

// CSRF functions - check if exists first
if (typeof decrypter === 'undefined') {
    function decrypter(encrypted) {
        // Simple pass-through for now since we're not encrypting
        return encrypted;
    }
}

if (typeof encrypter === 'undefined') {
    function encrypter(text) {
        // Simple pass-through for now since we're not encrypting
        return text;
    }
}

// Modal delete function (exact same as footer for consistency)
if (typeof modalDelete === 'undefined') {
    function modalDelete(title, datas) {
        // Create modal dynamically if not exists (exact same structure as footer)
        if ($('#modaldel').length === 0) {
            $('body').append(`
                <div class="modal fade" id="modaldel" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-backdrop-class="modal-backdrop-custom">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div class="row w-100 dflex justify-between" style="padding:0px;height:max-content;">
                                    <div class="col-10 dflex align-center">
                                        <span class="modal-title fs-6set fw-normal" id="modaldel-title">
                                        </span>
                                    </div>
                                    <div class="col-1 dflex align-center justify-end">
                                        <button type="button" class="btn text-dark" style="font-size: 24px;width:max-content;height:max-content;padding: 0px;margin-right:8px;" onclick="close_modal('modaldel')">×</button>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-body">
                                <span class="fw-normal fs-7set text-dark">Apakah anda yakin akan hapus data ?</span>
                                <div class="plus-message">
                                </div>
                                <div id="modaldel-assets">
                                </div>
                            </div>
                            <div class="modal-footer margin-t-2 p-x-2">
                                <button type="button" class="btn btn-secondary" id="cancel-delete" onclick="close_modal('modaldel')"><span class="fw-normal fs-7">No, Keep It</span></button>
                                <button type="button" class="btn btn-danger" id="confirm-delete"><span class="fw-normal fs-7">Yes, Delete It</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        $("#modaldel-title").html(`<h4>${title}</h4>`);
        $("#modaldel-assets").html("");
        
        let keys = Object.keys(datas);
        for (let x of keys) {
            if (x == 'plus-message') {
                $('.plus-message').html(datas[x]);
                continue;
            }
            $("#modaldel-assets").append(`<span class="re-set" key="${x}" vals="${datas[x]}"></span>`);
        }
        
        $('#modaldel').modal('show');
    }
}

// Close modal function (exact same as footer)
if (typeof close_modal === 'undefined') {
    function close_modal(modalid) {
        $('#' + modalid).modal('hide');
        if (modalid == 'modaldel') {
            $("#modaldel-assets").html("");
            $("#modaldel-title").text("")
        }
    }
}

// Confirm delete button handler (for detail delete)
$(document).ready(function() {
    $(document).on('click', '#confirm-delete', function() {
        let link = "";
        let id = "";
        let pagetype = "";
        let reloadpage = "";
        let reloadurl = "";
        let table_cls = "";
        
        $(".re-set").each(function() {
            let k = $(this).attr('key');
            let v = $(this).attr('vals');
            if (k == 'link') {
                link = v
            } else if (k == 'id') {
                id = v;
            } else if (k == 'pagetype') {
                pagetype = v;
            } else if (k == 'reloadpage') {
                reloadpage = v;
            } else if (k == 'reloadurl') {
                reloadurl = v;
            } else if (k == 'table-cls') {
                table_cls = v;
            }
        });

        $.ajax({
            url: link,
            type: 'post',
            data: {
                id: id,
                csrf_test_name: $('[name="csrf_test_name"]').val()
            },
            dataType: 'json',
            success: function(res) {
                if (res.sukses != '0' || res.sukses != 0) {
                    close_modal('modaldel');
                    var pesan = (res.pesan !== undefined ? res.pesan : 'Data Berhasil dihapus');
                    showSuccess(pesan);
                    
                    if (pagetype == 'pages') {
                        $('#' + reloadpage).load(reloadurl, function() {
                            // dp('#' + reloadpage);
                        });
                    } else if (pagetype == 'table') {
                        if (typeof tbl !== 'undefined') {
                            tbl.ajax.reload();
                        }
                    } else if (pagetype == 'tablespecific') {
                        if (table_cls == 'tbl_sub') {
                            if (typeof tbl_sub !== 'undefined') {
                                tbl_sub.ajax.reload(function() {
                                    $("#grand_total").text($("#span-gt").data('gt'));
                                });
                            }
                        }
                    } else if (pagetype == 'tablecredit') {
                        if (typeof tbl_dt !== undefined) {
                            tbl_dt.ajax.reload()
                        }
                    } else if (pagetype == 'tabledetail') {
                        if (typeof tbl_sub !== undefined) {
                            tbl_sub.ajax.reload();
                        }
                    } else if (pagetype == 'detail') {
                        console.log('Reloading detail tables...');

                        // Reload detail table
                        if ($('#detailTable').length && $.fn.DataTable.isDataTable('#detailTable')) {
                            $('#detailTable').DataTable().ajax.reload(function() {
                                console.log('Detail table reloaded successfully');
                            }, false);
                        } else {
                            console.warn('Detail table DataTable not found');
                        }

                        // Reload header table
                        if (typeof tbl !== 'undefined' && tbl.ajax) {
                            tbl.ajax.reload(function() {
                                console.log('Header table reloaded successfully');
                            });
                        } else {
                            console.warn('Header table DataTable not found');
                        }
                    }
                } else {
                    if (res.csrfToken) {
                        $("#csrf_token").val(encrypter(res.csrfToken));
                    }
                    var pesan = (res.pesan !== undefined ? res.pesan : 'Data gagal dihapus');
                    showNotif('error', pesan);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                close_modal('modaldel');
                showError(thrownError + ", please contact administrator.");
            }
        });
    });
});
</script>

</body>
</html>
