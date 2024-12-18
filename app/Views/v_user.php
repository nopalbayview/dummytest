<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?= base_url('public/datatable/jquery.dataTables.css') ?>">
    <link rel="stylesheet" href="<?= base_url('public/datatable/buttons.dataTables.min.css') ?>">
</head>

<body>
    <h1 style="text-align: center;">Master User</h1>
    <hr>
    <div style="display: flex;align-items:center;justify-content: space-between;">
        <div style="display: flex;justify-content: start;width:100%;align-items:center">
            <input type="hidden" id="userid">
            <input type="hidden" id="linksubmit" value="<?= base_url('user/add') ?>">
            <label for="fullname">Fullname :&nbsp;</label>
            <input type="text" name="fullname" id="fullname" placeholder="@ex: user full name" style="margin-right: 8px;">
            <label for="username">Username :&nbsp;</label>
            <input type="text" name="username" id="username" placeholder="@ex: username" style="margin-right: 8px;">
            <label for="password">Password :&nbsp;</label>
            <input type="password" name="password" id="password" placeholder="•••••••••••" style="margin-right: 8px;">
            <label for="email">Email :&nbsp;</label>
            <input type="text" name="email" id="email" placeholder="@ex: user email" style="margin-right: 8px;">
            <label for="phone">Phone :&nbsp;</label>
            <input type="text" name="phone" id="phone" placeholder="@ex: user telephone" style="margin-right: 8px;">
            <button style="width: max-content;height: 35px;display:flex;align-items:center;justify-content:center;padding-inline:1rem;" onclick="submitData()">
                Simpan
            </button>
        </div>
        <button style="width: max-content;height: 35px;display:flex;align-items:center;justify-content:center;padding-inline:1rem;" onclick="logOut('<?= session()->get('userid') ?>')">
            Log Out
        </button>
    </div>
    <hr>
    <div class="table-responsive">
        <table class="table table-bordered table-master w-100">
            <thead>
                <tr>
                    <td class="tableheader">No</td>
                    <td class="tableheader">Name</td>
                    <td class="tableheader">Username</td>
                    <td class="tableheader">Email</td>
                    <td class="tableheader">Telephone</td>
                    <td class="tableheader">Actions</td>
                </tr>
            </thead>
        </table>
    </div>
</body>

</html>
<script src="<?= base_url('public/js/jquery.js') ?>"></script>
<script src="<?= base_url('public/datatable/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('public/datatable/dataTables.buttons.min.js') ?>"></script>
<script>
    var tbl;

    function submitData() {
        let link = $('#linksubmit').val(),
            username = $('#username').val(),
            password = $('#password').val(),
            fullname = $('#fullname').val(),
            email = $('#email').val(),
            telp = $('#phone').val(),
            userid = $('#userid').val();

        $.ajax({
            url: link,
            type: 'post',
            dataType: 'json',
            data: {
                username: username,
                password: password,
                fullname: fullname,
                email: email,
                telp: telp,
                userid: userid,
            },
            success: function(res) {
                if (res.sukses == '1') {
                    alert(res.pesan);
                    $('#fullname').val("");
                    $('#email').val("");
                    $('#phone').val("");
                    $('#userid').val("");
                    $('#username').val("");
                    $('#password').val("");
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

    function editData(link) {
        $.ajax({
            url: link,
            type: 'post',
            dataType: 'json',
            success: function(res) {
                if (res.sukses == '1') {
                    let row = res.row;
                    $('#fullname').val(row.fullname);
                    $('#email').val(row.email);
                    $('#username').val(row.email);
                    $('#phone').val(row.telp);
                    $('#userid').val(row.id);
                    $('#linksubmit').val('<?= base_url('user/update') ?>');
                } else {
                    alert(res.pesan);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                alert(thrownError);
            }
        })
    }

    function hapusData(userid) {
        var conf = confirm("Apakah anda yakin ingin hapus data ini?");
        if (conf) {
            $.ajax({
                url: '<?= base_url('user/delete') ?>',
                type: 'post',
                dataType: 'json',
                data: {
                    userid: userid,
                },
                success: function(res) {
                    if (res.sukses == '1') {
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
    }

    function logOut(userid) {
        window.location.href = '<?= base_url('user/logout') ?>';
    }

    $(document).ready(function() {
        tbl = $('.table-master').DataTable({
            serverSide: true,
            destroy: true,
            autoWidth: false,
            ajax: {
                url: '<?= current_url(true) ?>/table',
                type: 'post',
                dataType: 'json',
                data: function(param) {
                    return param;
                },
                "deferRender": true,
                dataSrc: function(json) {
                    return json.data
                }
            }
        })
    })
</script>