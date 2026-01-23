<?php

namespace App\Models;

use CodeIgniter\Model;

class MCustomer extends Model
{
    protected $db;
    protected $table = 'mscustomer';
    public function __construct()
    {
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function searchable()
    {
        return [
            null,
            "customername",
            "address",
            "phone",
            "email",
            "filepath",
            null,
            null,
        ];
    }

    public function datatable()
    {
        return $this->builder;
    }

    public function getByName($name)
    {
        return $this->builder->where("lower(customername)", strtolower($name))->get()->getRowArray();
    }

    public function getOne($customerid)
    {
        return $this->builder->where("id", $customerid)->get()->getRowArray();
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

    public function getInitialCustomers()
    {
        return $this->builder
            ->select('id, customername as text')
            ->orderBy('customername', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

    public function searchSelect2($search)
    {
        $query = $this->builder
            ->select('id, customername as text')
            ->orderBy('customername', 'ASC')
            ->limit(20);

        if (!empty($search)) {
            $query->like('customername', $search, 'both', null, 'LIKE');
        }

        return $query->get()->getResultArray();
    }
    
}
