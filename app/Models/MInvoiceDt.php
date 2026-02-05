<?php

namespace App\Models;

use CodeIgniter\Model;

class MInvoiceDt extends Model
{
    protected $dbs;
    protected $builder;
    protected $table      = 'trinvoicedt';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'headerid',
        'productid',
        'uomid',
        'qty',
        'price',
        'createddate',
        'createdby',
        'updateddate',
        'updatedby',
        'isactive'
    ];

    public function __construct()
    {
        parent::__construct(); // panggil constructor Model
        $this->dbs     = db_connect();
        $this->builder = $this->dbs->table($this->table);
    }

    public function searchable()
    {
        return [
            null,           // No
            'p.productname', // Product
            'u.uomnm',       // UOM
            'trinvoicedt.qty',  // Qty
            'trinvoicedt.price',      // Price
            null,           // Actions
            null,           // Created By
            null,           // Updated By
            null,           // Created Date
            null,           // Updated Date
            null,           // Is Active
            null,           // Header ID
        ];
    }


    public function datatable($headerid = null, $invoice = [])
    {
        $build = $this->builder
            ->select('trinvoicedt.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = trinvoicedt.productid', 'left')
            ->join('msuom u', 'u.id = trinvoicedt.uomid', 'left');

        // Apply headerid filter
        if (!empty($headerid)) {
            $build->where('trinvoicedt.headerid', $headerid);
        }
        //if (!empty($headerid['columnName'])) {
        //    $build->orderBy($headerid['columnName'], $headerid['columnOrder']);
        //}
        if (!empty($invoice['columnName'])) {
            $build->orderBy($invoice['columnName'], $invoice['columnOrder']);
        } else {
            $build->orderBy('trinvoicedt.id', 'asc');
        }
        return $build;
    }

    public function getAllDetail($headerid)
    {
        return $this->builder
            ->select('trinvoicedt.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = trinvoicedt.productid', 'left')
            ->join('msuom u', 'u.id = trinvoicedt.uomid', 'left')
            ->where('trinvoicedt.headerid', $headerid)
            ->get()
            ->getResultArray();
    }

    public function getOne($id)
    {
        return $this->builder
            ->select('trinvoicedt.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = trinvoicedt.productid', 'left')
            ->join('msuom u', 'u.id = trinvoicedt.uomid', 'left')
            ->where('trinvoicedt.id', $id)
            ->get() 
            ->getRowArray();
    }

    public function getDetailsByHeader($headerid)
    {
        return $this->builder
            ->select('trinvoicedt.*, p.productname, u.uomnm')
            ->join('msproduct p', 'p.id = trinvoicedt.productid', 'left')
            ->join('msuom u', 'u.id = trinvoicedt.uomid', 'left')
            ->where('trinvoicedt.headerid', $headerid)
            ->get()
            ->getResultArray();
    }

    public function getDetail($column = null, $value = null)
    {
        $builder = $this->datatable();

        if (!empty($column) && !empty($value)) {
            $builder->where($column, $value);
        }

        return $builder;
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($data, $id)
    {
        return $this->builder
        ->where('trinvoicedt.id', $id)->update($data);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }
}