<?php

namespace App\Models;

use CodeIgniter\Model;

class MInvoiceHd extends Model
{
    protected $dbs;
    protected $builder;
    protected $table = 'trinvoicehd';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'transcode',
        'transdate',
        'customerid',
        'grandtotal',
        'description',
        'createdby',
        'createddate',
        'updatedby',
        'updateddate',
        'isactive',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->dbs = db_connect();
        $this->builder = $this->dbs->table($this->table);
    }

    public function searchable()
    {
        return [
            null,           // No
            'transcode',    // Transcode
            'transdate',    // Transdate
            'mscustomer.customername', //Search by customername
            'grandtotal',   // Grandtotal
            'description',  // Description
            null,           // Created By
            null,           // Updated By
            null            // Actions
        ];
    }


    public function datatable($filters = [])
    {
        $builder = $this->builder
            ->select('trinvoicehd.*, mscustomer.customername')
            ->join('mscustomer', 'mscustomer.id = trinvoicehd.customerid', 'left');

        if (!empty($filters['startDate']) && empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
        } elseif (empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('DATE(trinvoicehd.transdate) <=', $filters['endDate']);
        } elseif (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
            $builder->where('trinvoicehd.transdate <=', $filters['endDate']);
        }

        if (!empty($filters['customerId'])) {
            $builder->where('trinvoicehd.customerid', $filters['customerId']);
        }

        return $builder;
    }

    public function getOne($id)
    {
        return $this->builder
            ->select('trinvoicehd.*, c.customername')
            ->join('mscustomer c', 'c.id = trinvoicehd.customerid', 'left')
            ->where('trinvoicehd.id', $id)
            ->get()
            ->getRowArray();
    }

    public function getHeader($column = null, $value = null)
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
        return $this->builder->update($data, ['id' => $id]);
    }

    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }

    public function getAll()
    {
        return $this->builder->get()->getResultArray();
    }

    public function getInvoiceChunk($limit, $offset)
    {
        return $this->builder
            ->select('trinvoicehd.*, mscustomer.customername')
            ->join('mscustomer', 'mscustomer.id = trinvoicehd.customerid', 'left')
            ->orderBy('trinvoicehd.id', 'ASC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function getHeaderChunkWithFilters($limit, $offset, $filters = [])
    {
        $builder = $this->builder
            ->select('trinvoicehd.*, mscustomer.customername')
            ->join('mscustomer', 'mscustomer.id = trinvoicehd.customerid', 'left')
            ->orderBy('trinvoicehd.id', 'ASC');

        // Both dates provided
        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
            $builder->where('trinvoicehd.transdate <=', $filters['endDate']);
        }
        // Only startDate provided
        elseif (!empty($filters['startDate']) && empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
        }
        // Only endDate provided
        elseif (empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('DATE(trinvoicehd.transdate) <=', $filters['endDate']);
        }

        if (!empty($filters['customerId'])) {
            $builder->where('trinvoicehd.customerid', $filters['customerId']);
        }

        return $builder
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function countHeaderWithFilters($filters = [])
    {
        $builder = $this->builder
            ->select('trinvoicehd.id')
            ->join('mscustomer', 'mscustomer.id = trinvoicehd.customerid', 'left');

        // Both dates provided
        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
            $builder->where('trinvoicehd.transdate <=', $filters['endDate']);
        }
        // Only startDate provided
        elseif (!empty($filters['startDate']) && empty($filters['endDate'])) {
            $builder->where('trinvoicehd.transdate >=', $filters['startDate']);
        }
        // Only endDate provided
        elseif (empty($filters['startDate']) && !empty($filters['endDate'])) {
            $builder->where('DATE(trinvoicehd.transdate) <=', $filters['endDate']);
        }

        if (!empty($filters['customerId'])) {
            $builder->where('trinvoicehd.customerid', $filters['customerId']);
        }

        return $builder->countAllResults();
    }
}
