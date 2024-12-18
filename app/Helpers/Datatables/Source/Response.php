<?php


namespace App\Helpers\Datatables\Source;

use CodeIgniter\Database\ResultInterface;
use Config\Services;

class Response
{

    protected $draw;

    /* @var ResultInterface */
    protected $total;

    /* @var ResultInterface */
    protected $data;

    /* @var ResultInterface */
    protected $filtered;

    protected $jsonData = array();

    protected $request;

    protected $response;

    public function __construct($total, $data, $filtered)
    {
        $this->total = $total;
        $this->data = $data;
        $this->filtered = $filtered;
        $this->request = new Request();
        $this->response = Services::response();

        $this->_set();
    }

    private function _set()
    {
        $this->jsonData = array();

        $datas = ArrCollect::collect($this->data->getResultObject());

        foreach ($datas->all() as $db) {
            $data = array();
            foreach ($this->request->columns() as $column) {
                if ($column->isDataNotEmpty() && !$column->isDataNumeric())
                    $data[$column->data()] = $db->{$column->data()};
                else $data[] = null;
            }
            $this->jsonData[] = $data;
        }
    }

    public function updateRow(callable $callable)
    {
        $datas = ArrCollect::collect($this->data->getResultObject());

        $this->jsonData = array();
        foreach ($datas->all() as $i => $data)
            $this->jsonData[] = call_user_func_array($callable, array($data, $i + $this->request->start() + 1));

        return $this;
    }

    public function updateMultipleRow(callable $callable)
    {
        $datas = ArrCollect::collect($this->data->getResultObject());

        $this->jsonData = array();
        foreach ($datas->all() as $i => $data) {
            $results = call_user_func_array($callable, array($data, $i + $this->request->start() + 1));
            foreach ($results as $res)
                $this->jsonData[] = $res;
        }

        return $this;
    }

    public function prependRow($data)
    {
        array_unshift($this->jsonData, $data);

        return $this;
    }

    public function appendRow($data)
    {
        $this->jsonData[] = $data;

        return $this;
    }

    public function addColumn($name, $callable = null)
    {
        foreach ($this->jsonData as $data) {
            if (is_callable($name)) {
                $data[] = call_user_func($name, $data);
            } else {
                $data[$name] = call_user_func($callable, $data);
            }
        }
    }

    public function number()
    {
        return $this->request->start();
    }

    public function toJson($tambahan = [])
    {
        echo json_encode(array_merge([
            'draw' => $this->request->draw(),
            'recordsFiltered' => $this->filtered,
            'recordsTotal' => $this->total,
            'data' => $this->jsonData,
            'csrfToken' => csrf_hash(),
            'tambahan' => $tambahan
        ]));
    }
}
