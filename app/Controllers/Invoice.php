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

    public function index()
    {
        return view('master/invoice/v_invoice', [
            'title'      => 'Invoice',
            'akses'      => null,
            'breadcrumb' => $this->bc,
            'section'    => 'Setting Invoice',
        ]);
    }

    public function datatable()
    {
        $columnIndex = $this->request->getPost('order')[0]['column'];
        $columnOrder = $this->request->getPost('order')[0]['dir'];
        $arrayColumn = [null, "trinvoicehd.transcode", "trinvoicehd.transdate", "c.customername", "trinvoicehd.grandtotal", "trinvoicehd.description", "trinvoicehd.isactive"];
        $columnName = $arrayColumn[$columnIndex];

        $table = Datatables::method([$this->invoiceHeaderModel::class, 'datatable'], 'searchable')
            ->setParams(null, ['columnName' => $columnName, 'columnOrder' => $columnOrder])
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning'
            onclick=\"window.location.href='" . getURL('invoice/form/' . encrypting($db->id)) . "'\">
            <i class='bx bx-edit-alt'></i></button>";

            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger'
            onclick=\"modalDelete('Delete Invoice - " . $db->transcode . "', {'link':'" . getURL('invoice/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\">
            <i class='bx bx-trash'></i></button>";

            $btn_pdf = "<button type='button' class='btn btn-sm btn-info'
            onclick=\"window.open('" . getURL('invoice/pdf/' . encrypting($db->id)) . "', '_blank')\">
            <i class='bx bx-printer'></i></button>";
            return [
                $no,
                $db->transcode,
                $db->transdate,
                $db->customername,
                'Rp' . number_format($db->grandtotal, 2, ',', '.'),
                $db->description,
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_pdf&nbsp;$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });

        return $table->toJson();
    }

    public function detaildatatable($param = "")
    {
        $headerid = decrypting($param);

        if (empty($headerid)) {
            return $this->response->setJSON([
                'draw'            => intval($this->request->getPost('draw')),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => []
            ]);
        }

        $orderData   = $this->request->getPost('order')[0] ?? [];
        $columnIndex = $orderData['column'] ?? 1;
        $columnOrder = $orderData['dir'] ?? 'asc';
        $arrayColumn  = [null, "p.productname", "u.uomnm", "trinvoicedt.qty", "trinvoicedt.price"];
        $columnName = $arrayColumn[$columnIndex] ?? "trinvoicedt.id";

        // panggil Datatables helper dengan instance model
        $table = Datatables::method([$this->invoiceDetailModel, 'datatable'], 'searchable')
            ->setParams($headerid, ['columnName' => $columnName, 'columnOrder' => $columnOrder])
            ->make();

        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button type='button' class='btn btn-sm btn-warning' 
            onclick=\"modalForm('Update Detail - " . $db->productname . "', 'modal-lg', '" . getURL('invoice/detailform/' . $db->id) . "', {identifier: this})\">
            <i class='bx bx-edit-alt'></i></button>";

            $btn_hapus = "<button type='button' class='btn btn-sm btn-danger'
            onclick=\"deleteDataDt(this,'" . encrypting($db->id) . "')\">
            <i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->productname,
                $db->uomnm,
                number_format($db->qty, 0, ',', '.'),
                'Rp' . number_format($db->price, 2, ',', '.'),
                "<div style='display:flex;align-items:center;justify-content:center;'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });

        return $table->toJson();
    }

    public function detailForm($id)
    {
        $detail = $this->invoiceDetailModel->getDetail('trinvoicedt.id', $id)
            ->get()
            ->getRowArray();

        if (empty($detail)) {
            return $this->response->setJSON([
                'error'     => "Detail dengan ID $id tidak ditemukan",
                'csrfToken' => csrf_hash()
            ]);
        }

        // data master untuk dropdown
        $products = $this->productModel->findAll();
        $uoms     = $this->uomModel->findAll();

        // Format qty & price sama seperti di datatable
        $detail['qty_formatted']   = $detail['qty'];
        $detail['price_formatted'] = $detail['price'];

        $dt = [
            'view'      => view('master/invoice/v_edit_detail_form', [
                'detail'   => $detail,
                'products' => $products,
                'uoms'     => $uoms,
            ]),
            'csrfToken' => csrf_hash()
        ];

        return $this->response->setJSON($dt);
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

        return view('master/invoice/v_form', [
            'form_type' => $form_type,
            'row' => $row,
            'invoiceid' => $id,
            'customers' => $customer,
            'products' => $products,
            'uoms' => $uoms,
            'title' => ($form_type == 'edit' ? 'Edit Invoice' : 'Add Invoice'),
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => ($form_type == 'edit' ? 'Edit Invoice' : 'Add Invoice'),
        ]);
    }

    public function addData()
    {
        $transcode = $this->request->getPost('transcode');
        $transdate = $this->request->getPost('transdate');
        $customerid = $this->request->getPost('customerid');
        $description = $this->request->getPost('description');

        $this->db->transBegin();
        try {
            if (empty($transcode)) throw new Exception("Transaction code is required!");
            if (empty($transdate)) throw new Exception("Transaction date is required!");
            if (empty($customerid)) throw new Exception("Customer is required!");
            if (empty($description)) throw new Exception("Description is required!");

            $spam = $this->invoiceHeaderModel
                ->where('transcode',  $transcode)
                ->first();

            if ($spam) {
                throw new Exception("Transcode sudah terdaftar!");
            }

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
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'sukses' => 0,
                        'pesan' => 'Failed to store invoice data'
                    ]);
                } else {
                    return redirect()->back()->with('error', 'Failed to store invoice data');
                }
            } else {
                $this->db->transCommit();
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'sukses' => 1,
                        'pesan' => 'Invoice added successfully!',
                        'csrfToken' => csrf_hash()
                    ]);
                } else {
                    return redirect()->to(base_url('invoice'))->with('success', 'Invoice added successfully!');
                }
            }
        } catch (Exception $e) {
            $this->db->transRollback();
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => $e->getMessage()
                ]);
            } else {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
    }

    public function addDetail()
    {
        $headerid = $this->request->getPost('headerid');
        $productid = $this->request->getPost('productid');
        $uomid = $this->request->getPost('uomid');
        $qty = $this->request->getPost('qty');
        $price = $this->request->getPost('price');

        $this->db->transBegin();
        try {
            if (empty($headerid)) throw new Exception("Header ID is required!");
            if (empty($productid)) throw new Exception("Product is required!");
            if (empty($uomid)) throw new Exception("UOM is required!");
            if (empty($qty) || $qty <= 0) throw new Exception("Quantity must be greater than 0!");
            if (!is_numeric($price) || $price <= 0) throw new Exception("Price must be a valid number greater than 0!");

            $this->invoiceDetailModel->store([
                'headerid' => $headerid,
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
                $grandtotal = $this->updateGrandTotal($headerid);

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

    public function updateData()
    {
        $id = $this->request->getPost('id');
        $transcode = $this->request->getPost('transcode');
        $transdate = $this->request->getPost('transdate');
        $customerid = $this->request->getPost('customerid');
        $description = $this->request->getPost('description');

        $this->db->transBegin();

        try {
            if (empty($id)) throw new Exception("ID is required!");
            if (empty($transcode)) throw new Exception("Transcode is required!");
            if (empty($transdate)) throw new Exception("Transdate is is required!");
            if (empty($customerid)) throw new Exception("Customer ID is required");
            if (empty($description)) throw new Exception("Description is required");

            //$spam = $this->invoiceHeaderModel
            //    ->where('transcode',  $transcode)
            //    ->first();
            //if ($spam) {
            //    throw new Exception("Transcode Tidak terdaftar!");
            //}

            $data = $this->invoiceHeaderModel->getOne($id);
            if (empty($data)) throw new Exception("Detail not found!");

            $this->invoiceHeaderModel->edit([
                'id' => $id,
                'transcode' => $transcode,
                'transdate' => $transdate,
                'customerid' => $customerid,
                'description' => $description,
                'updateddate' => date('Y-m-d H:i:s'),
                'updatedby' => getSession('userid'),
            ], $id);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'sukses' => 0,
                        'pesan' => 'Failed to update invoice data'
                    ]);
                } else {
                    return redirect()->back()->with('error', 'Failed to update invoice data');
                }
            } else {
                // Update grand total in header
                $this->updateGrandTotal($data['id']);

                $this->db->transCommit();
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'sukses' => 1,
                        'pesan' => 'Invoice updated successfully!',
                        'csrfToken' => csrf_hash()
                    ]);
                } else {
                    return redirect()->to(base_url('invoice'))->with('success', 'Invoice updated successfully!');
                }
            }
        } catch (Exception $e) {
            $this->db->transRollback();
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => $e->getMessage()
                ]);
            } else {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }
    }

    public function deleteData()
    {
        $invoiceId = decrypting($this->request->getPost('id'));

        $this->db->transBegin();

        $row = $this->invoiceHeaderModel->getOne($invoiceId);
        if (empty($row)) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Invoice not found!'
            ]);
        }

        $this->invoiceDetailModel->destroy('headerid', $invoiceId);
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
    }

    public function deleteDetail()
    {
        $detailid = decrypting($this->request->getPost('id'));

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
    }

    public function updateDetail()
    {
        $detailid = $this->request->getPost('detailid');
        $productid = $this->request->getPost('productid');
        $uomid = $this->request->getPost('uomid');
        $qty = $this->request->getPost('qty');
        $price = $this->request->getPost('price');

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
                $this->updateGrandTotal($detail['headerid']);

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

    public function getDetails()
    {
        $headerid = $this->request->getPost('headerid');
        $res = [];

        $this->db->transBegin();
        try {
            if (empty($headerid)) throw new Exception("Header ID is required!");

            $details = $this->invoiceDetailModel->getAllDetail($headerid);
            $grandtotal = 0;

            $formatDetails = [];
            foreach ($details as $index => $detail) {
                $subtotal = $detail['qty'] * $detail['price'];
                $grandtotal += $subtotal;

                $formatDetails[] = [
                    'no' => $index + 1,
                    'id' => $detail['id'],
                    'productid' => $detail['productid'],
                    'product_name' => $detail['product_name'] ?? 'Unknown Product',
                    'uomid' => $detail['uomid'],
                    'uom_name' => 'UOM ' . $detail['uomid'], // Assuming you have UOM table
                    'qty' => $detail['qty'],
                    'price' => $detail['price'],
                    'subtotal' => $subtotal,
                    'action' =>
                    "<button type='button' class='btn btn-sm btn-danger btn-delete-detail' data-id='" . $detail['id'] . "'><i class='bx bx-trash'></i></button>"
                ];
            }

            // Update header grand total
            $this->invoiceHeaderModel->edit(['grandtotal' => $grandtotal], $headerid);

            $res = [
                'sukses' => '1',
                'pesan' => 'Details loaded successfully!',
                'details' => $formatDetails,
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
        $data = array_map(function ($c) {
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
        $this->invoiceHeaderModel->update($headerid, ['grandtotal' => $grandtotal]);

        return $grandtotal;
    }

    public function productList()
    {
        $search = $this->request->getPost('search') ?? '';
        $products = $this->productModel->searchSelect2($search);
        $data = array_map(function ($p) {
            return ['id' => $p['id'], 'text' => $p['text'] ?? $p['productname']];
        }, $products);
        $this->response->setJSON(['items' => $data])->send();
        exit;
    }

    public function uomList()
    {
        $search = $this->request->getPost('search') ?? '';
        $uoms   = $this->uomModel->searchSelect2($search);

        $data = array_map(function ($u) {
            return [
                'id'   => $u['id'],
                'text' => $u['text'] // sudah alias dari query
            ];
        }, $uoms);

        $this->response->setJSON(['items' => $data])->send();
        exit;
    }

    public function printPDF($id = null)
    {
        if (empty($id)) {
            throw new Exception('Invoice ID is required');
        }

        $header = $this->invoiceHeaderModel->getOne(decrypting($id));
        $details = $this->invoiceDetailModel->getDetailsByHeader(decrypting($id));

        // === CONFIG STATIS ===
        $managername = 'Winna Oktavia P.';  //nama manager
        $revision = '01'; //nomor revisi
        $logoPath = FCPATH . 'images/hyperdata.png'; //path gambar logo
        $ttdPath = FCPATH . 'images/ttd.png'; //path gambar ttd

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(10, 10, 10);

        //HEADER
        $pdf->SetFont('Arial', 'B', 11);

        // LOGO (tinggi 24)
        $pdf->Cell(35, 20, '', 1, 0, 'C');
        $pdf->Image($logoPath, $pdf->GetX() - 34, $pdf->GetY() + 1, 34, 17);

        // JUDUL (tinggi 24)
        $pdf->Cell(70, 20, 'INVOICE INFO', 1, 0, 'C');

        // ttd
        $pdf->SetFont('Arial', '', 8);

        // Baris 1
        $pdf->Cell(25, 5, 'Document', 1, 0);
        $pdf->Cell(30, 5, 'Bukti Transaksi', 1, 0);
        $pdf->MultiCell(30, 2.5, "Disetujui oleh: Manager Mutu", 1, 'C');

        // Baris 2
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Revisi', 1, 0);
        $pdf->Cell(30, 5, '001', 1, 0);
        $pdf->Cell(30, 5, '', 'LR', 1, 'C');
        $pdf->Image($ttdPath, $pdf->GetX() + 162, $pdf->GetY() - 4, 27, 10);

        // Baris 3
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Tanggal Terbit', 1, 0);
        $pdf->Cell(30, 5, date('d F Y', strtotime($header['transdate'])), 1, 0);
        $pdf->Cell(30, 5, '', 'LR', 1, 'C');

        // Baris 4
        $pdf->Cell(105, 5, '', 0, 0);
        $pdf->Cell(25, 5, 'Halaman', 1, 0);
        $pdf->Cell(30, 5, $revision, 1, 0);
        $pdf->Cell(30, 5, $managername, 1, 1, 'C');

        //pemisah
        $pdf->Ln(4);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        //Info Transaksi
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(30, 6, 'No. Invoice', 0, 0);
        $pdf->Cell(70, 6, ': ' . $header['transcode'], 0, 0);
        $pdf->Cell(30, 6, 'Invoice Date', 0, 0);
        $pdf->Cell(50, 6, ': ' . date('d F Y', strtotime($header['transdate'])), 0, 1);

        $pdf->Cell(30, 6, 'Customer', 0, 0);
        $pdf->Cell(50, 6, ': ' . $header['customername'], 0, 1);

        $pdf->Ln(2);

        //Nama Kolom Tabel
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(10, 6, 'INVOICE DETAIL', 0, 1, 'L');
        $pdf->Cell(10, 6, 'No', 1, 0, 'C');
        $pdf->Cell(65, 6, 'Product Name', 1, 0, 'C');
        $pdf->Cell(20, 6, 'UOM', 1, 0, 'C');
        $pdf->Cell(20, 6, 'Qty', 1, 0, 'C');
        $pdf->Cell(37.5, 6, 'Price', 1, 0, 'C');
        $pdf->Cell(37.5, 6, 'Total', 1, 1, 'C');

        //Isi Tabel
        $pdf->SetFont('Arial', '', 10);
        $no = 1;
        $subtotal = 0;

        foreach ($details as $detail) {
            $total = $detail['qty'] * $detail['price'];
            $subtotal += $total;

            $qty   = number_format($detail['qty'], 0, '.', '.');
            $price = number_format($detail['price'], 2, ',', '.');
            $total = number_format($total, 2, ',', '.');

            $pdf->Cell(10, 6, $no++, 1, 0, 'C');
            $pdf->Cell(65, 6, $detail['productname'], 1, 0, 'C');
            $pdf->Cell(20, 6, $detail['uomnm'], 1, 0, 'C');
            $pdf->Cell(20, 6, $qty, 1, 0, 'C');
            $pdf->Cell(37.5, 6, 'Rp ' . $price, 1, 0, 'R');
            $pdf->Cell(37.5, 6, 'Rp ' . $total, 1, 1, 'R');
        }

        //form subtotal dan grandtotal
        $pdf->Ln(4);
        $pdf->SetX(120);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 8, 'SubTotal', 0, 0, 'R');
        $pdf->Cell(30, 8, 'Rp ' . number_format($subtotal, 2, ',', '.'), 0, 1, 'R');
        $pdf->Cell(160.5, 8, 'Diskon', 0, 0, 'R');
        $pdf->Cell(12, 8, 'Rp ', 0, 0, 'R');
        $pdf->Cell(17.5, 8, '0', 0, 1, 'R');

        $pdf->SetX(120);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(145, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(130);
        $pdf->Cell(40, 8, 'GrandTotal', 0, 0, 'R');
        $pdf->Cell(30, 8, 'Rp ' . number_format($subtotal, 2, ',', '.'), 0, 1, 'R');
        $pdf->Output('I');
        exit;
    }

    
    public function exportExcel()
    {
        $limit  = 500;
        $offset = 0;
        $row    = 2;
        $no     = 1;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ===== COLUMN WIDTH =====
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(30);

        // ===== STYLE =====
        $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => '4CAF50'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];

        // ===== HEADER =====
        $headers = ['No', 'Transcode', 'Transdate', 'Customer Name', 'Grand Total', 'Description'];
        $columns = range('A', 'F');

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($column . '1', $headers[$key]);
        }
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // ================= CHUNK LOOP =================
        while (true) {

            $invoices = $this->invoiceHeaderModel->getInvoiceChunk($limit, $offset);

            if (empty($invoices)) {
                break;
            }

            foreach ($invoices as $invoice) {
                $sheet->setCellValue('A' . $row, $no++)
                    ->setCellValue('B' . $row, $invoice['transcode'])
                    ->setCellValue('C' . $row, $invoice['transdate'])
                    ->setCellValue('D' . $row, $invoice['customername'])
                    ->setCellValue('E' . $row, $invoice['grandtotal'])
                    ->setCellValue('F' . $row, $invoice['description']);
                $row++;
            }

            log_message('info', 'Export chunk offset: ' . $offset);

            $offset += $limit;
            unset($invoices);
        }
        // =============================================

        // APPLY DATA STYLE SETELAH SEMUA DATA MASUK
        $sheet->getStyle('A2:F' . ($row - 1))->applyFromArray($dataStyle);

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="invoices.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
    
    public function getHeaderChunk()
    {
        $limit = (int) $this->request->getGet('limit');
        $offset = (int) $this->request->getGet('offset');
    
        // Jika offset >= 999999, hitung total records (pakai query builder reset)
        if ($offset >= 999999) {
            $builder = $this->invoiceHeaderModel->builder();
            $total = $builder->countAllResults();
            return $this->response->setJSON([
                'rows' => [],
                'count' => 0,
                'total' => $total
            ]);
        }
    
        $data = $this->invoiceHeaderModel->getInvoiceChunk($limit, $offset);
    
        return $this->response->setJSON([
            'rows' => $data,
            'count' => count($data),
            'offset' => $offset
        ]);
    }

    public function formImport()
    {
        
    }
}

