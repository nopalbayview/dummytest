<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MCustomer;
use App\Models\MInvoiceHd;
use App\Models\MInvoiceDt;
use App\Models\MProduct;
use App\Models\MUOM;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Fpdf\Fpdf;
use Exception;

class Invoice extends BaseController
{
protected $db;
    protected $bc;
    protected $invoiceHeaderModel;
    protected $invoiceDetailModel;
    protected $customerModel;
    protected $productModel;
    protected $uomModel;

    public function __construct()
    {
        $this->invoiceHeaderModel = new MInvoiceHd();
        $this->invoiceDetailModel = new MInvoiceDt();
        $this->customerModel = new MCustomer();
        $this->productModel = new MProduct();
        $this->uomModel = new MUOM();

        $this->bc = [
            [
                'Setting',
                'Invoice'
            ]
        ];
    }

    public function detailDatatable()
    {
        $table = Datatables::method([MInvoiceDt::class, 'datatable'], 'searchable')
            ->make();
        $table->updateRow(function ($db, $no) {
            $subtotal = $db->qty * $db->price;
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"editDetail('{$db->id}', '{$db->productid}', '{$db->uomid}', '{$db->qty}', '{$db->price}', '{$db->productname}', '{$db->uomnm}')\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"deleteDataDt(this, '{$db->id}')\"><i class='bx bx-trash'></i></button>";
            return [
                $no,
                $db->productname,
                $db->uomnm,
                number_format($db->qty),
                formatNumber($subtotal, '.', ',', 3),
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function index()
    {
        return view('master/invoice/v_invoice', [
            'title' => 'Invoice',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting Invoice',
        ]);
    }

    public function datatable()
    {
        $table = Datatables::method([MInvoiceHd::class, 'datatable'], 'searchable')
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update Invoice - " . $db->transcode . "', 'modal-lg', '" . getURL('invoice/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete Invoice - " . $db->transcode . "', {'link':'" . getURL('invoice/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->transcode,
                $db->transdate,
                $db->customername ?? 'Unknown',
                $db->grandtotal,
                $db->description,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        $table->toJson();
    }

    public function forms($id = '')
    {
        $form_type = (empty($id) ? 'add' : 'edit');
        $row = [];
        if ($id != '') {
            $id = decrypting($id);
            $row = $this->invoiceHeaderModel->getOne($id);
            // Check if the data exists
            if (empty($row)) {
                throw new \CodeIgniter\Exceptions\PageNotFoundException("Invoice with ID $id not found.");
            }
        }

        // Get all customers, products, uoms for dropdown
        try {
            $customer = $this->customerModel->findAll();
            $products = $this->productModel->findAll();
            $uoms = $this->uomModel->findAll();
        } catch (\Throwable) {
            $customer = [];
            $products = [];
            $uoms = [];
        }

        $dt['view'] = view('master/invoice/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'invoiceid' => $id,
            'customers' => $customer,
            'products' => $products,
            'uoms' => $uoms,
        ]);
        $dt['csrfToken'] = csrf_hash();
        echo json_encode($dt);
    }

    public function addData()
    {
        $transcode = $this->request->getPost('transcode');
        $transdate = $this->request->getPost('transdate');
        $customerid = $this->request->getPost('customerid');
        $description = $this->request->getPost('description');
        $res = [];

        $this->db->transBegin();
        try {
            if (empty($transcode)) throw new Exception("Transaction code is required!");
            if (empty($transdate)) throw new Exception("Transaction date is required!");
            if (empty($customerid)) throw new Exception("Customer is required!");
            if (empty($description)) throw new Exception("Description is required!");

            $this->invoiceHeaderModel->store([
                'transcode' => $transcode,
                'transdate' => $transdate,
                'customerid' => $customerid,
                'description' => $description,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => getSession('userid'),
                'isactive' => true,
            ]);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => 'Failed to store invoice data'
                ]);
            } else {
                $this->db->transCommit();
                return $this->response->setJSON([
                    'sukses' => 1,
                    'pesan' => 'Invoice added successfully!',
                    'csrfToken' => csrf_hash()
                ]);
            }
        } catch (Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => $e->getMessage()
            ]);
        }
        $this->db->transComplete();
    }

    public function updateData()
    {
        $id = $this->request->getPost('id');
        $transcode = $this->request->getPost('transcode');
        $transdate = $this->request->getPost('transdate');
        $customerid = $this->request->getPost('customerid');
        $description= $this->request->getPost('description');
        $res = [];

        $this->db->transBegin();

        if (empty($id)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'ID is required!'
            ]);
        }
        if (empty($transcode)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Transcode is required!'
            ]);
        }
        if (empty($transdate)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Transdate is required!'
            ]);
        }
        if (empty($customerid)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Customer is required!'
            ]);
        }
        if (empty($description)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Description is required!'
            ]);
        }

        // Update data invoice
        $data = [
            'transcode' =>$transcode,
            'transdate' => $transdate,
            'customerid' => $customerid,
            'description' => $description,
            'updateddate' => date('Y-m-d H:i:s'),
            'updatedby' => getSession('userid'),
        ];

        $this->invoiceHeaderModel->edit($data, $id);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Failed to update invoice data'
            ]);
        } else {
            $this->db->transCommit();
            return $this->response->setJSON([
                'sukses' => 1,
                'pesan' => 'Invoice updated successfully!',
                'csrfToken' => csrf_hash()
            ]);
        }

        $this->db->transComplete();
    }

    public function deleteData()
    {
        $invoiceId = decrypting($this->request->getPost('id'));
        $res = [];

        $this->db->transBegin();

        $row = $this->invoiceHeaderModel->getOne($invoiceId);
        if (empty($row)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Invoice not found!'
            ]);
        }

        $this->invoiceHeaderModel->destroy('id', $invoiceId);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Failed to delete invoice data'
            ]);
        } else {
            $this->db->transCommit();
            return $this->response->setJSON([
                'sukses' => 1,
                'pesan' => 'Invoice deleted successfully!',
                'csrfToken' => csrf_hash()
            ]);
        }

        $this->db->transComplete();
    }

    // Detail methods
    public function addDetail()
    {
        $detailid = $this->request->getPost('headerid');
        $productid = $this->request->getPost('productid');
        $uomid = $this->request->getPost('uomid');
        $qty = $this->request->getPost('qty');
        $price = $this->request->getPost('price');

        $this->db->transBegin();
        try {
            if (empty($detailid)) throw new Exception("Header ID is required!");
            if (empty($productid)) throw new Exception("Product is required!");
            if (empty($uomid)) throw new Exception("UOM is required!");
            if (empty($qty) || $qty <= 0) throw new Exception("Quantity must be greater than 0!");
            if (empty($price) || $price <= 0) throw new Exception("Price must be greater than 0!");
            if (!is_numeric($price)) throw new Exception("Price must be a valid number!");

            $this->invoiceDetailModel->store([
                'headerid' => $detailid,
                'productid' => $productid,
                'uomid' => $uomid,
                'qty' => $qty,
                'price' => $price,
                'createddate' => date('Y-m-d H:i:s'),
                'createdby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => getSession('userid'),
                'isactive' => true,
            ]);

            // Check transaction status
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => 'Failed to store detail data'
                ]);
            } else {
                // Update grand total in header
                $grandtotal = $this->updateGrandTotal($detailid);

                $this->db->transCommit();
                return $this->response->setJSON([
                    'sukses' => 1,
                    'pesan' => 'Detail added successfully!',
                    'grandtotal' => $grandtotal,
                    'csrfToken' => csrf_hash()
                ]);
            }
        } catch (Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => $e->getMessage()
            ]);
        }
    }

    public function updateDetail()
    {
        $detailid = $this->request->getPost('detailid');
        $productid = $this->request->getPost('productid');
        $uomid = $this->request->getPost('uomid');
        $qty = $this->request->getPost('qty');
        $price = $this->request->getPost('price');
        $res = [];

        $this->db->transBegin();
        try {
            if (empty($detailid)) throw new Exception("Detail ID is required!");
            if (empty($productid)) throw new Exception("Product is required!");
            if (empty($uomid)) throw new Exception("UOM is required!");
            if (empty($qty) || $qty <= 0) throw new Exception("Quantity must be greater than 0!");
            if (empty($price) || $price <= 0) throw new Exception("Price must be greater than 0!");
            if (!is_numeric($price)) throw new Exception("Price must be a valid number!");

            $detail = $this->invoiceDetailModel->getOne($detailid);
            if (empty($detail)) throw new Exception("Detail not found!");

            $this->invoiceDetailModel->edit([
                'productid' => $productid,
                'uomid' => $uomid,
                'qty' => $qty,
                'price' => $price,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => getSession('userid'),
            ], $detailid);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => 'Failed to update detail data'
                ]);
            } else {
                // Update grand total in header
                $grandtotal = $this->updateGrandTotal($detail['headerid']);

                $this->db->transCommit();
                return $this->response->setJSON([
                    'sukses' => 1,
                    'pesan' => 'Detail updated successfully!',
                    'csrfToken' => csrf_hash()
                ]);
            }
        } catch (Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => $e->getMessage()
            ]);
        }
    }

    public function deleteDetail()
    {
        $detailid = $this->request->getPost('id');
        $res = [];

        $this->db->transBegin();

        if (empty($detailid)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Detail ID is required!'
            ]);
        }

        $detail = $this->invoiceDetailModel->getOne($detailid);
        if (empty($detail)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Detail not found!'
            ]);
        }

        $headerid = $detail['headerid'];
        $this->invoiceDetailModel->destroy('id', $detailid);

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Failed to delete detail data'
            ]);
        } else {
            // Update grand total in header
            $this->updateGrandTotal($headerid);

            $this->db->transCommit();
            return $this->response->setJSON([
                'sukses' => 1,
                'pesan' => 'Detail deleted successfully!',
                'csrfToken' => csrf_hash()
            ]);
        }

        $this->db->transComplete();
    }

    public function getDetails()
    {
        $headerid = $this->request->getPost('headerid');
        $res = [];

        $this->db->transBegin();
        try {
            if (empty($headerid)) throw new Exception("Header ID is required!");

            $details = $this->invoiceDetailModel->getByInvoiceId($headerid);
            $grandtotal = 0;

            $formattedDetails = [];
            foreach ($details as $index => $detail) {
                $subtotal = $detail['qty'] * $detail['price'];
                $grandtotal += $subtotal;

                $formattedDetails[] = [
                    'no' => $index + 1,
                    'id' => $detail['id'],
                    'productid' => $detail['productid'],
                    'product_name' => $detail['product_name'] ?? 'Unknown Product',
                    'uomid' => $detail['uomid'],
                    'uom_name' => 'UOM ' . $detail['uomid'], // Assuming you have UOM table
                    'qty' => $detail['qty'],
                    'price' => $detail['price'],
                    'subtotal' => $subtotal,
                    'action' => "<button type='button' class='btn btn-sm btn-danger btn-delete-detail' data-id='" . $detail['id'] . "'><i class='bx bx-trash'></i></button>"
                ];
            }

            // Update header grand total
            $this->invoiceHeaderModel->edit(['grandtotal' => $grandtotal], $headerid);

            $res = [
                'sukses' => '1',
                'pesan' => 'Details loaded successfully!',
                'details' => $formattedDetails,
                'grandtotal' => $grandtotal,
            ];

            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    public function customerList()
    {
        $search = $this->request->getPost('search') ?? '';
        if (empty($search)) {
            $customers = $this->customerModel->builder->limit(20)->get()->getResultArray();
        } else {
            $customers = $this->customerModel->searchSelect2($search);
        }
        $data = array_map(function($c) {
            return ['id' => $c['id'], 'text' => $c['text'] ?? $c['customername']];
        }, $customers);
        $this->response->setJSON(['items' => $data])->send();
        exit;
    }

    public function updateGrandTotal($headerid)
    {
        // Ambil detail dari model
        $details = $this->invoiceDetailModel->getDetailsByHeader($headerid);

        // Hitung grand total
        $grandtotal = 0;
        foreach ($details as $dt) {
            $grandtotal += $dt['qty'] * $dt['price'];
        }

        // Update ke header lewat model
        $this->invoiceHeaderModel->updateGrandTotal($headerid, $grandtotal);

        return $grandtotal;
    }

    public function productList()
    {
        $search = $this->request->getPost('search') ?? '';
        $products = $this->productModel->searchSelect2($search);
        $data = array_map(function($p) {
            return ['id' => $p['id'], 'text' => $p['text'] ?? $p['productname']];
        }, $products);
        $this->response->setJSON(['items' => $data])->send();
        exit;
    }

    public function uomList()
{
    $search = $this->request->getPost('search') ?? '';
    $uoms   = $this->uomModel->searchSelect2($search);

    $results = array_map(function($u) {
        return [
            'id'   => $u['id'],
            'text' => $u['text'] // sudah alias dari query
        ];
    }, $uoms);

    return $this->response->setJSON([
        'results' => $results,
        'pagination' => ['more' => false]
    ]);
}
}