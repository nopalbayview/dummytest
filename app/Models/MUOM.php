<?php

namespace App\Models;

use CodeIgniter\Model;

class MUOM extends Model
{
    protected $db;
    protected $table = 'msuom';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uomnm',
        'description',
        'createddate',
        'createdby',
        'updateddata',
        'updatedby',
        'isactive'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
    }

    public function searchable()
    {
        return [
            null,
            'uomnm',
            'description',
            null,
            null,
            null,
            null,
            null,
        ];
    }

    public function datatable()
    {
        return $this->builder;
    }

    public function getOne($id)
    {
        return $this->builder->where("id", $id)->get()->getRowArray();
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
        return $this->builder->where('isactive', true)->get()->getResultArray();
    }

    public function getInitialUOMs()
    {
        return $this->builder
            ->select('id, uomname as text')
            ->where('isactive', true)
            ->orderBy('uomname', 'ASC')
            ->limit(20)
            ->get()
            ->getResultArray();
    }

   public function searchSelect2($search)
    {
    $builder = $this->builder()
        ->select('id, uomnm as text')
        ->orderBy('uomnm', 'ASC')
        ->limit(20);
    if (!empty($search)) {
        // Case-insensitive partial match dengan LOWER() dan wildcard
        $builder->where('LOWER(uomnm) LIKE', '%' . strtolower($search) . '%');
    }
    return $builder->get()->getResultArray();
    }
}