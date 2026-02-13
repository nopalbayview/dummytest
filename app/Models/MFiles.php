<?php 

namespace App\Models;

use CodeIgniter\Model;

class MFiles extends Model
{
    protected $table = 'msfiles';
    protected $primaryKey = 'fileid';
    protected $dbs;
    protected $builder;
    protected $allowedFields = [
        'filename',
        'filerealname',
        'filedirectory',
        'created_date',
        'created_by',
        'updated_date',
        'updated_by',
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
            null,
            'msfiles.filename',
            'msfiles.filerealname',
            'msfiles.filedirectory',
            null,
            'msuser.username',
        ];
    }

    public function datatable()
    {
        $this->builder->resetQuery();
        $this->builder->select('msfiles.fileid, msfiles.filename, msfiles.filerealname, msfiles.filedirectory, msfiles.created_date, msuser.username as created_by');
        $this->builder->join('msuser', 'msuser.id = msfiles.created_by', 'left');
        return $this->builder;
    }

    public function getByName($name)
    {
        $this->builder->resetQuery();
        return $this->builder->where("lower(filename)", strtolower($name))->get()->getRowArray();
    }

    public function getOne($fileid)
    {
        $this->builder->resetQuery();
        return $this->builder->where("fileid", $fileid)->get()->getRowArray();
    }

    public function store($data)
    {
        return $this->builder->insert($data);
    }

    public function edit($data, $id)
    {
        $this->builder->resetQuery();
        $this->builder->where('fileid', $id);
        return $this->builder->update($data);
    }
    
    public function destroy($column, $value)
    {
        return $this->builder->delete([$column => $value]);
    }
}