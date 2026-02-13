<?php 

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MFiles;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Files extends BaseController
{
    protected $db;
    protected $bc;
    protected $fileModel;

    public function __construct()
    {
        $this->fileModel = new MFiles();
        $this->db = db_connect();

        $this->bc = [
            [
                'Setting',
                'Files'
            ]
        ];
    }

    public function index()
    {
        return view('master/files/v_files', [
            'title'      => 'Files',
            'akses'      => null,
            'breadcrumb' => $this->bc,
            'section'    => 'Setting Files',
        ]);
    }

    public function datatable()
    {
        $table = Datatables::method([MFiles::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $encryptedId = encrypting($db->fileid);
            $downloadUrl = base_url('files/download/' . $encryptedId);
            
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update file - " . $db->filerealname . "', 'modal-lg', '" . getURL('files/form/' . $encryptedId) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete file - " . $db->filerealname . "', {'link':'" . getURL('files/delete') . "', 'id':'" . $encryptedId . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";

            // Button Download
            $btn_download = "<a href='" . $downloadUrl . "' class='btn btn-sm btn-success' target='_blank'><i class='bx bx-download'></i></a>";
            
            // Button Preview - hanya untuk gambar, buka di new tab
            $extension = strtolower(pathinfo($db->filename, PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
            if (in_array($extension, $imageExtensions)) {
                $btn_preview = "<a href='" . base_url($db->filedirectory) . "' target='_blank' class='btn btn-sm btn-info'><i class='bx bx-image'></i></a>";
            } else {
                $btn_preview = "<button type='button' class='btn btn-sm btn-secondary' disabled title='Preview hanya untuk gambar'><i class='bx bx-image'></i></button>";
            }
            
            return [
                $no,
                $db->filerealname,
                $db->filedirectory,
                $db->created_date,
                $db->created_by,
                "<div style='display:flex;align-items:center;justify-content:center;gap:4px;'>$btn_preview&nbsp;$btn_download&nbsp;$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');
        $row = [];
        $decryptedId = '';
        if ($id != '') {
            $decryptedId = decrypting($id);
            $row = $this->fileModel->getOne($decryptedId);
        }
        $dt['view'] = view('master/files/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'fileid' => $decryptedId,
            'encrypted_id' => $id
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function upload()
    {
        $file = $this->request->getFile('filedirectory');
        
        $this->db->transBegin();
        try {
            if (!$file->isValid()) {
                throw new Exception("File tidak valid!");
            }
            
            $originalName = $file->getClientName();
            $extension = $file->getExtension();
            $newName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = 'uploads/files/';
            
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            $file->move($uploadPath, $newName);
            $filePath = $uploadPath . $newName;
            
            $this->fileModel->store([
                'filename' => $newName,
                'filerealname' => $originalName,
                'filedirectory' => $filePath,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by' => getSession('userid'),
            ]);
            
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                echo json_encode([
                    'sukses' => '0',
                    'pesan' => 'Gagal menyimpan file',
                    'csrfToken' => csrf_hash()
                ]);
                return;
            }
            
            $this->db->transCommit();
            echo json_encode([
                'sukses' => '1',
                'pesan' => 'File berhasil diupload!',
                'csrfToken' => csrf_hash()
            ]);
            return;
            
        } catch (Exception $e) {
            $this->db->transRollback();
            echo json_encode([
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ]);
            return;
        }
    }

    public function updateData()
    {
        $id = decrypting($this->request->getPost('id'));
        $newFile = $this->request->getFile('filedirectory');

        $this->db->transBegin();
        try {
            if (empty($id)) {
                throw new Exception("ID file diperlukan! ID: " . $this->request->getPost('id'));
            }

            $row = $this->fileModel->getOne($id);
            if (empty($row)) {
                throw new Exception("File tidak ditemukan! ID: " . $id);
            }

            $data = [
                'update_date' => date('Y-m-d H:i:s'),
                'update_by' => getSession('userid'),
            ];

            if ($newFile && $newFile->isValid()) {
                $oldFilePath = $row['filedirectory'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }

                $extension = $newFile->getExtension();
                $newName = uniqid() . '_' . time() . '.' . $extension;
                $uploadPath = 'uploads/files/';
                $newFile->move($uploadPath, $newName);

                $data['filename'] = $newName;
                $data['filerealname'] = $newFile->getClientName();
                $data['filedirectory'] = $uploadPath . $newName;
            }

            $this->fileModel->edit($data, $id);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                echo json_encode([
                    'sukses' => '0',
                    'pesan' => 'Gagal update file',
                    'csrfToken' => csrf_hash()
                ]);
                return;
            }

            $this->db->transCommit();
            echo json_encode([
                'sukses' => '1',
                'pesan' => 'File berhasil diupdate!',
                'csrfToken' => csrf_hash()
            ]);
            return;

        } catch (Exception $e) {
            $this->db->transRollback();
            echo json_encode([
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ]);
            return;
        }
    }

    public function deleteData()
    {
        $id = decrypting($this->request->getPost('id'));
        
        $this->db->transBegin();
        try {
            if (empty($id)) throw new Exception("ID file diperlukan!");
            
            $row = $this->fileModel->getOne($id);
            if (empty($row)) throw new Exception("File tidak ditemukan!");
            
            $filePath = $row['filedirectory'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->fileModel->destroy('fileid', $id);
            
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                echo json_encode([
                    'sukses' => '0',
                    'pesan' => 'Gagal hapus file',
                    'csrfToken' => csrf_hash()
                ]);
                return;
            }
            
            $this->db->transCommit();
            echo json_encode([
                'sukses' => '1',
                'pesan' => 'File berhasil dihapus!',
                'csrfToken' => csrf_hash()
            ]);
            return;
            
        } catch (Exception $e) {
            $this->db->transRollback();
            echo json_encode([
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ]);
            return;
        }
    }

    public function download($id = '')
    {
        if (empty($id)) {
            throw new Exception("ID file diperlukan!");
        }
        
        $id = decrypting($id);
        $row = $this->fileModel->getOne($id);
        
        if (empty($row)) {
            throw new Exception("File tidak ditemukan!");
        }
        
        $filePath = $row['filedirectory'];
        $fileRealName = $row['filerealname'];
        
        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan di server!");
        }
        
        return $this->response->download($filePath, null)->setFileName($fileRealName);
    }
}
