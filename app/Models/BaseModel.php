<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class BaseModel extends Model
{
    protected $db;
    protected $dbReader;

    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();

        $this->db       = Database::connect();
        $this->dbReader = Database::connect('dbReader');

        $this->db->query("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
        $this->dbReader->query("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
    }

    private function rowInternal($where = [], $order = [], $join = [], $params = [], $useReader = false)
    {
        $db         =   $useReader ? $this->dbReader : $this->db;
        $builder    =   $db->table($this->table);

        if (!empty($where)) {
            if (!is_array($where)) {
                $where = [$this->primaryKey => $where];
            }
            $builder->where($where);
        }

        if (!empty($order)) {
            if (!is_array($order)) {
                $order = [$order];
            }
            $builder->orderBy(implode(', ', $order));
        }

        if (!empty($join)) {
            if (is_array($join)) {
                $isMultiArray   =   is_array($join[0]);

                if ($isMultiArray) {
                    foreach($join as $arr) {
                        $builder->join($arr[0], $arr[1], $arr[2] ?? null);
                    }
                } else {
                    $builder->join($join[0], $join[1], $join[2] ?? null);
                }
            }
        }

        if (!empty($params['select'])) {
            $builder->select($params['select']);
        }

        if (!empty($params['groupBy'])) {
            $builder->groupBy($params['groupBy']);
        }        

        if (!empty($params['like'])) {
            $like   =   $params['like'];

            if (is_array($like)) {
                $isMultiArray   =   is_array($like[0]);

                $builder->groupStart();
                    if ($isMultiArray) {
                        foreach ($like as $keyV => $v) {
                            if (is_array($v[0])) {
                                foreach ($v as $keyX => $x) {
                                    $builder->groupStart();
                                        foreach ($x as $y) {
                                            $likeColumn =   $y[0];
                                            $likeValue  =   $y[1];
                                            $likeExp    =   $y[2] ?? null;
                                            $likeParam  =   strtolower($y[3] ?? null);

                                            if ($likeParam == null || $likeParam == 'and') {
                                                $builder->like($likeColumn, $likeValue, $likeExp);
                                            } else {
                                                $builder->orLike($likeColumn, $likeValue, $likeExp);
                                            }
                                        }
                                    $builder->groupEnd();;
                                }
                            } else {
                                $likeColumn =   $v[0];
                                $likeValue  =   $v[1];
                                $likeExp    =   $v[2] ?? null;
                                $likeParam  =   strtolower($v[3] ?? null);

                                if ($likeParam == null || $likeParam == 'and') {
                                    $builder->like($likeColumn, $likeValue, $likeExp);
                                } else {
                                    $builder->orLike($likeColumn, $likeValue, $likeExp);
                                }
                            }
                        }
                    } else {
                        $likeColumn =   $like[0];
                        $likeValue  =   $like[1];
                        $likeExp    =   $like[2] ?? null;
                        $likeParam  =   strtolower($like[3] ?? null);

                        if ($likeParam == null || $likeParam == 'and') {
                            $builder->like($likeColumn, $likeValue, $likeExp);
                        } else {
                            $builder->orLike($likeColumn, $likeValue, $likeExp);
                        }
                    }
                $builder->groupEnd();
            }

            foreach ($params['like'] as $like) {
                $builder->like($like[0], $like[1]);
            }
        }

        if (isset($params['orWhere'])) {
            if (!is_array($params['orWhere'])) {
                $params['orWhere']  =   [$this->primary_key => $params['orWhere']];
            }
            $builder->orWhere($params['orWhere']);
        }

        if (!empty($params['inWhere'])) {
            foreach ($params['inWhere'] as $in) {
                $builder->whereIn($in[0], $in[1]);
            }
        }

        if (array_key_exists('whereGroupOr', $params)) {
            $builder->groupStart();
                if (is_array($params['whereGroupOr'])) {
                    foreach ($params['whereGroupOr'] as $k => $v) {
                        $builder->groupStart();
                            $builder->where($v);
                        $builder->groupEnd();
                    }
                }

            $builder->groupEnd();
        }

        if (!empty($params['returnCount'])) {
            return $builder->countAllResults();
        }

        $query = $builder->get(1);

        if (!empty($params['returnType'])) {
            if (strtolower($params['returnType']) == 'object') {
                return $query->getRowObject();
            }
        }

        return $query->getRowArray();
    }

    public function row($where = [], $order = [], $join = [], $params = [])
    {
        return $this->rowInternal($where, $order, $join, $params);
    }

    public function rowReader($where = [], $order = [], $join = [], $params = [])
    {
        return $this->rowInternal($where, $order, $join, $params, true);
    }

    private function listInternal($where = [], $limit = null, $offset = null, $order = [], $join = [], $params = [], $useReader = false)
    {
        $db         =   $useReader ? $this->dbReader : $this->db;
        $builder    =   $db->table($this->table);

        if (empty($limit)) {
            $limit  =   PHP_INT_MAX;
        }

        if (empty($offset)) {
            $offset  =   0;
        }

        if (!empty($where)) {
            if (!is_array($where)) {
                $where = [$this->primaryKey => $where];
            }
            $builder->where($where);
        }

        if (!empty($order)) {
            if (!is_array($order)) {
                $order = [$order];
            }
            $builder->orderBy(implode(', ', $order));
        }

        if (!empty($join)) {
            if (is_array($join)) {
                $isMultiArray   =   is_array($join[0]);

                if ($isMultiArray) {
                    foreach($join as $arr) {
                        $builder->join($arr[0], $arr[1], $arr[2] ?? null);
                    }
                } else {
                    $builder->join($join[0], $join[1], $join[2] ?? null);
                }
            }
        }

        if (!empty($params['select'])) {
            $builder->select($params['select']);
        }

        if (!empty($params['groupBy'])) {
            $builder->groupBy($params['groupBy']);
        }

        if (!empty($params['like'])) {
            $like   =   $params['like'];

            if (is_array($like)) {
                $isMultiArray   =   is_array($like[0]);

                $builder->groupStart();
                    if ($isMultiArray) {
                        foreach ($like as $keyV => $v) {
                            if (is_array($v[0])) {
                                foreach ($v as $keyX => $x) {
                                    $builder->groupStart();
                                        foreach ($x as $y) {
                                            $likeColumn =   $y[0];
                                            $likeValue  =   $y[1];
                                            $likeExp    =   $y[2] ?? null;
                                            $likeParam  =   strtolower($y[3] ?? null);

                                            if ($likeParam == null || $likeParam == 'and') {
                                                $builder->like($likeColumn, $likeValue, $likeExp);
                                            } else {
                                                $builder->orLike($likeColumn, $likeValue, $likeExp);
                                            }
                                        }
                                    $builder->groupEnd();;
                                }
                            } else {
                                $likeColumn =   $v[0];
                                $likeValue  =   $v[1];
                                $likeExp    =   $v[2] ?? null;
                                $likeParam  =   strtolower($v[3] ?? null);

                                if ($likeParam == null || $likeParam == 'and') {
                                    $builder->like($likeColumn, $likeValue, $likeExp);
                                } else {
                                    $builder->orLike($likeColumn, $likeValue, $likeExp);
                                }
                            }
                        }
                    } else {
                        $likeColumn =   $like[0];
                        $likeValue  =   $like[1];
                        $likeExp    =   $like[2] ?? null;
                        $likeParam  =   strtolower($like[3] ?? null);

                        if ($likeParam == null || $likeParam == 'and') {
                            $builder->like($likeColumn, $likeValue, $likeExp);
                        } else {
                            $builder->orLike($likeColumn, $likeValue, $likeExp);
                        }
                    }
                $builder->groupEnd();
            }

            foreach ($params['like'] as $like) {
                $builder->like($like[0], $like[1]);
            }
        }

        if (isset($params['orWhere'])) {
            if (!is_array($params['orWhere'])) {
                $params['orWhere']  =   [$this->primary_key => $params['orWhere']];
            }
            $builder->orWhere($params['orWhere']);
        }

        if (!empty($params['inWhere'])) {
            foreach ($params['inWhere'] as $in) {
                $builder->whereIn($in[0], $in[1]);
            }
        }

        if (array_key_exists('whereGroupOr', $params)) {
            $builder->groupStart();
                if (is_array($params['whereGroupOr'])) {
                    foreach ($params['whereGroupOr'] as $k => $v) {
                        $builder->groupStart();
                            $builder->where($v);
                        $builder->groupEnd();
                    }
                }

            $builder->groupEnd();
        }

        if (!empty($params['notIn'])) {
            $builder->whereNotIn($params['notIn'][0], $params['notIn'][1]);
        }

        if (!empty($params['returnCount'])) {
            return $builder->countAllResults();
        }

        $query = $builder->get($limit, $offset);

        if (!empty($params['returnType'])) {
            if (strtolower($params['returnType']) == 'object') {
                return $query->getResultObject();
            }
        }

        return $query->getResultArray();
    }

    public function list($where = [], $limit = null, $offset = 0, $order = [], $join = [], $params = [])
    {
        return $this->listInternal($where, $limit, $offset, $order, $join, $params);
    }

    public function listReader($where = [], $limit = null, $offset = 0, $order = [], $join = [], $params = [])
    {
        return $this->listInternal($where, $limit, $offset, $order, $join, $params, true);
    }

    public function insertData($data, $return = true)
    {
        $this->db->table($this->table)->insert($data);

        if ($return) {
            if ($this->db->affectedRows() >= 1) {
                return $this->row($this->db->insertID());
            } else {
                return false;
            }   
        }

        return $builder->insertID();
    }

    public function batchInsert($data, $batchSize = 100)
    {
        $this->db->table($this->table)->insertBatch($data, null, $batchSize);

        return $this->db->affectedRows() > 0 ? true : false;
    }

    public function updateData($data, $where = null)
    {
        $builder    =   $this->db->table($this->table);

        if (!empty($where)) {
            if (!is_array($where)) {
                $where  =   [$this->primaryKey => $where];
            }
            $builder->where($where);
        } else {
            $builder->where($this->primaryKey, $this->id);
        }

        $builder->update($data);

        return $this->db->affectedRows() > 0 ? true : false;
    }

    public function deleteData($where = null, $softDelete = false)
    {
        if ($softDelete) {
            return $this->updateData(['deleted' => date('Y-m-d H:i:s')], $where);
        }

        $builder    =   $this->db->table($this->table);

        if (!empty($where)) {
            if (!is_array($where)) {
                $where  =   [$this->primaryKey => $where];
            }
            $builder->where($where);
        } else {
            $builder->where($this->primaryKey, $this->id);
        }

        $builder->delete();

        return $this->db->affectedRows() > 0 ? true : false;
    }

    protected function directQueryInternal($sql, $params = null, $useReader = false) 
    {
        $db = $useReader ? $this->dbReader : $this->db;

        if (!empty($params['returnType'])) {
            if (strtolower($params['returnType']) == 'object') {
                return $db->query($sql)->getResultObject();
            }
        }

        return $db->query($sql)->getResultArray();
    }

    public function directQuery($sql, $params = null)
    {
        return $this->directQueryInternal($sql, $params);
    }

    public function directQueryReader($sql, $params = null)
    {
        return $this->directQueryInternal($sql, $params, true);
    }
}