<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MUser;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class User extends BaseController
{
    protected $userModel;
    public function __construct()
    {
        $this->userModel = new MUser();
    }

    public function index()
    {
        return view('v_user', [
            'title' => 'Master User'
        ]);
    }

    public function viewLogin()
    {
        return view('v_login', [
            'title' => 'Login'
        ]);
    }

    public function loginAuth()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $res = array();
        $this->db->transBegin();
        try {
            if (empty($username) || empty($password)) throw new Exception("Username atau Password harus diisi!");
            $row = $this->userModel->getByName($username);
            if (empty($row)) throw new Exception("User tidak terdaftar di sistem!");
            if (password_verify($password, $row['password'])) {
                session()->set('userid', $row['id']);
                $res = [
                    'sukses' => '1',
                    'pesan' => 'Berhasil Login',
                    'link' => base_url('user'),
                    'dbError' => db_connect()->error()
                ];
            } else {
                throw new Exception("Password user salah, coba lagi!");
            }
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function datatable()
    {
        $table = Datatables::method([MUser::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button style='background-color: rgba(204, 167, 0);color:#fff;text-align:center;' onclick=\"editData('" . base_url('user/form/' . $db->id) . "')\">Edit</button>";
            $btn_hapus = "<button style='background-color: rgba(243, 61, 33);color:#fff;text-align:center;' onclick=\"hapusData($db->id)\">Hapus</button>";
            return [
                $no,
                $db->fullname,
                $db->username,
                $db->email,
                $db->telp,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function addData()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $fullname = $this->request->getPost('fullname');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('telp');
        $res = array();

        $this->db->transBegin();
        try {
            if (empty($username)) throw new Exception("Username dibutuhkan!");
            if (empty($password)) throw new Exception("Password dibutuhkan!");
            if (empty($fullname)) throw new Exception("Fullname masih kosong!");
            $row = $this->userModel->getByName($fullname);
            if (!empty($row)) throw new Exception("User dengan username ini sudah terdaftar!");
            $this->userModel->store([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'fullname' => $fullname,
                'email' => $email,
                'telp' => $phone,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => 1,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => 1,
            ]);
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses menambahkan user baru',
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function formEdit($userid)
    {
        $row = $this->userModel->getOne($userid);
        if (empty($userid)) {
            echo json_encode([
                'sukses' => '0',
                'pesan' => 'Data tidak ditemukan!',
                'dbError' => db_connect()->error()
            ]);
            die;
        }
        echo json_encode([
            'sukses' => '1',
            'pesan' => 'Berhasil ambil data!',
            'dbError' => db_connect()->error(),
            'row' => $row,
        ]);
    }

    public function updateData()
    {
        $userid = $this->request->getPost('userid');
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $fullname = $this->request->getPost('fullname');
        $email = $this->request->getPost('email');
        $phone = $this->request->getPost('telp');
        $res = array();

        $this->db->transBegin();
        try {
            if (empty($username)) throw new Exception("Username dibutuhkan!");
            if (empty($password)) throw new Exception("Password dibutuhkan!");
            if (empty($fullname)) throw new Exception("Fullname masih kosong!");
            $row = $this->userModel->getByName($fullname);
            if (!empty($row)) throw new Exception("User dengan username ini sudah terdaftar!");
            $data = [
                'username' => $username,
                'fullname' => $fullname,
                'email' => $email,
                'telp' => $phone,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => 1,
            ];
            if (!empty($password)) {
                $data += [
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ];
            }
            $this->userModel->edit($data, $userid);
            $res = [
                'sukses' => '1',
                'pesan' => 'Sukses update user baru',
                'dbError' => db_connect()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function deleteData()
    {
        $userid = $this->request->getPost('userid');
        $res = array();
        $this->db->transBegin();
        try {
            $row = $this->userModel->getOne($userid);
            if (empty($row)) throw new Exception("User tidak terdaftar!");
            $this->userModel->destroy('id', $userid);
            $res = [
                'sukses' => '1',
                'pesan' => 'Data berhasil dihapus!',
                'dbError' => db_connect()->error()
            ];
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function logOut()
    {
        $userid = session()->get('userid');
        $row = $this->userModel->getOne($userid);
        if (!empty($row)) {
            session()->destroy();
        }
        return redirect('login');
    }
}
