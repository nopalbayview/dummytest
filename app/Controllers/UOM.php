<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MUOM;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class UOM extends BaseController
{
    protected $db;
    protected $bc;
    protected $uomModel;

    public function __construct()
    {
        $this->uomModel = new MUOM();
        $this->bc = [
            [
                'Setting',
                'UOM'
            ]
        ];
    }

    public function index()
    {
        return view('master/uom/v_uom', [
            'title' => 'UOM',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting UOM',
        ]);
    }

    public function search()
    {
        $search = $this->request->getGet('q') ?? $this->request->getPost('q')
               ?? $this->request->getGet('searchTerm') ?? $this->request->getPost('searchTerm');

        $res = array();

        try {
            // If no search term, return initial data (first 20 UOMs)
            if (empty($search)) {
                $results = $this->uomModel->getInitialUOMs();
            } else {
                $results = $this->uomModel->searchSelect2($search);
            }
            $formattedResults = [];
            foreach ($results as $result) {
                $formattedResults[] = [
                    'id' => $result['id'],
                    'text' => $result['uomname']
                ];
            }
            $res['data'] = $formattedResults;

            $res['csrfToken'] = csrf_hash();
        } catch (Exception $e) {
            $res = [
                'data' => [],
                'error' => $e->getMessage()
            ];
        }

        $this->response->setJSON($res)->send();
        exit;
    }

    public function get()
    {
        $id = $this->request->getPost('id');
        $res = array();

        try {
            if (empty($id)) {
                throw new Exception('UOM ID is required');
            }

            $uom = $this->uomModel->getOne($id);

            if (empty($uom)) {
                throw new Exception('UOM not found');
            }

            $res = [
                'success' => true,
                'uom' => $uom
            ];
        } catch (Exception $e) {
            $res = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $res['csrfToken'] = csrf_hash();
        $this->response->setJSON($res)->send();
        exit;
    }
}