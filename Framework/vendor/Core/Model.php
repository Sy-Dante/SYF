<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/6/14
 * Time: 11:10
 */

namespace Core;


class Model {
    public $table = null;
    public $primaryKey = null;
    protected $order = '';
    protected $fields = '*';
    protected $where = [];


    public function __construct() {}

    /**
     * @param array $where
     * @return array
     */
    public function getOne($where) {
        $data = $this->select($where, 1);
        return empty($data) ? [] : current($data);
    }

    /**
     * @return array
     */
    public function getAll() {
        $data = [];
        $list = $this->select($this->where, 0, $this->order);
        if (!empty($list) && $this->primaryKey !== null) {
            foreach ($list as $row) {
                //以主键为索引建立数据
                $data[$row[$this->primaryKey]] = $row;
            }
        } else {
            $data = $list;
        }
        return $data;
    }

    /**
     * @param array $where
     * @param int|string $limit
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public function select($where, $limit = 0, $order = '') {
        if ($this->table !== null) {
            return DB::select($this->table, $where, $this->fields, $limit, $order);
        } else {
            throw new \Exception('Table is null!');
        }
    }

    /**
     * 执行sql查询，返回查询数据
     * @param string $sql
     * @param array $bind_data
     * @return array
     */
    public function query($sql, $bind_data = []) {
        return DB::query($sql, $bind_data);
    }

    /**
     * 执行sql语句
     * @param string $sql
     * @param array $bind_data
     * @return mixed
     */
    public function execute($sql, $bind_data = []) {
        return DB::query($sql, $bind_data);
    }

    /**
     * 更新数据
     * @param array $data
     * @param array $where
     * @return mixed
     * @throws \Exception
     */
    public function update($data, $where = []) {
        if ($this->table !== null) {
            return DB::update($this->table, $data, $where);
        } else {
            throw new \Exception('Table is null!');
        }
    }

    /**
     * 插入数据
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function insert($data) {
        if ($this->table !== null) {
            return DB::insert($this->table, $data);
        } else {
            throw new \Exception('Table is null!');
        }
    }

    public function delete($where) {
        //
    }

    /**
     * 通过主键查询
     * @param mixed $id 主键值
     * @return array
     */
    public function getByPk($id) {
        return $this->getOne([$this->primaryKey => $id]);
    }

    /**
     * sql执行列表
     * @return array
     */
    public static function getSqlHistory() {
        return DB::getHistory();
    }

}