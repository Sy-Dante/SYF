<?php
/**
 * Created by PhpStorm.
 * User: ShenYao
 * Date: 2016/7/6
 * Time: 10:04
 */

namespace Core;


use Common\Log;

class DB {
    /**
     * @var null|\PDO
     */
    protected static $db = null;
    protected static $sqlHistory = [];

    /**
     * 获取PDO实例
     * @return \PDO
     * @throws \Exception
     */
    protected static function getInstance() {
        if (static::$db === null) {
            $config = Config::get('DB');
            if ($config === null) {
                throw new \Exception('Undefined database config!');
            }

            //解析config start
            $option = isset($config['option']) ? $config['option'] : [];

            if (isset($config['user']) && isset($config['pass'])) {
                $user = $config['user'];
                $pass = $config['pass'];
            } else {
                throw new \Exception('undefined database user and password!');
            }

            $dsn = '';
            $host = '';
            $port = '';
            $db_name = '';
            if (isset($config['dsn'])) {
                $dsn = $config['dsn'];
            } elseif (isset($config['host']) &&
                isset($config['port']) &&
                isset($config['db'])) {
                $host = $config['host'];
                $port = $config['port'];
                $db_name = $config['db'];
            }
            //解析config end

            $type = empty($config['type']) ? 'mysql' : $config['type'];
            $type = strtolower($type);
            switch ($type) {
                case 'mysql':
                    if (empty($dsn)) {
                        $dsn = "mysql:host={$host};port={$port};dbname={$db_name}";
                    }
                    $obj = new \PDO($dsn, $user, $pass, $option);
                    $obj->exec('SET NAMES utf8');
                    //set default fetch mode【\PDO::FETCH_ASSOC】
                    $obj->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                    break;
                default:
                    throw new \Exception('Does not support database type!');
                    break;
            }
            static::$db = $obj;
        }
        return static::$db;
    }

    /**
     * 启动一个事务
     * @return bool
     * @throws \Exception
     */
    public static function beginTransaction() {
        if (static::$db === null) {
            static::getInstance();
        }
        return static::$db->beginTransaction();
    }

    /**
     * 查询是否处于事务中
     * @return bool
     * @throws \Exception
     */
    public static function inTransaction() {
        $db = static::getInstance();
        return $db->inTransaction();
    }

    /**
     * 提交事务
     * @throws \Exception
     */
    public static function commit() {
        if (static::$db === null) {
            static::getInstance();
        }
        static::$db->commit();
    }

    /**
     * 回滚事务
     * @throws \Exception
     */
    public static function rollBack() {
        if (static::$db === null) {
            static::getInstance();
        }
        static::$db->rollBack();
    }

    /**
     * 设置查询模式
     * @param int $mode PDO的FETCH常量
     * @throws \Exception
     */
    public static function setFetchMode($mode = \PDO::FETCH_BOTH) {
        if (static::$db === null) {
            static::getInstance();
        }
        static::$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $mode);
    }

    /**
     * 查询
     * @param string $table
     * @param array $where
     * @param string $field
     * @param string|int $limit
     * @param string $order
     * @return array
     * @throws \Exception
     */
    public static function select($table, $where = [], $field = '*', $limit = 0, $order = '') {
        $sql = "SELECT {$field} FROM {$table}";
        //where
        $where_data = static::bindParams($where);
        $_data = $where_data['data'];
        $_where = $where_data['query'];
        if (!empty($_where)) {
            $sql .= " WHERE {$_where}";
        }
        //limit
        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }
        //order
        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }

        $stmt = static::query($sql, $_data);
        return $stmt->fetchAll();
    }

    /**
     * 查询
     * @param string $table
     * @param array $where
     * @param string $field
     * @return array
     */
    public static function getOne($table, $where = [], $field = '*') {
        $res = static::select($table, $where, $field, 1);
        if (!empty($res)) {
            $res = $res[0];
        } else {
            $res = [];
        }
        return $res;
    }

    /**
     * where的参数绑定
     * @param array $arr
     *      可用操作包括：'>', '<', '=', '>=', '<=', '<>', '!=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'
     *      可用模式大致包括：
     *          ['field' => 'some value']  --> `field` = ?
     *          ['field' => ['>', 0]]  --> `field` > ?
     *          ['field_1' => 'one', 'field_2' => ['IN', [1, 2], 'OR']]  --> `field_1` = ? OR `field_2` IN (?, ?)
     *          ['field' => [['>', 0], ['<=', 10]]]  --> `field` > ? AND `field` <= ?
     * @return array
     */
    protected static function bindParams($arr) {
        $query = '';
        $params = [];
        $data = [];
        if (!empty($arr) && is_array($arr)) {
            $query = [];
            foreach ($arr as $k => $v) {
                $key = static::addBackQuote($k);
                if (is_string($v) || is_numeric($v)) {
                    //默认为AND
                    if (!empty($query)) {
                        $query[] = 'AND';
                    }
                    $query[] = "{$key} = ?";
                    $params[] = $key;
                    $data[] = $v;

                } elseif (is_array($v) && isset($v[0])) {
                    //为了支持同一个参数多个条件
                    if (is_array($v[0])) {
                        //多条件，可写为[['>', 0], ['<', 10]]
                        $_where_arr = $v;
                    } elseif (is_string($v[0])) {
                        //单条件可写为['>', 0]
                        $_where_arr = [$v];
                    } else {
                        $_where_arr = [];
                    }

                    //解析条件
                    foreach ($_where_arr as $_where) {
                        //连接符
                        $_connector = 'AND';
                        //第三个参数为连接符
                        if (isset($_where[2]) && in_array($_where[2], ['AND', 'OR'])) {
                            $_connector = $_where[2];
                        }

                        //第一个参数为操作符，第二个参数为数据
                        if (isset($_where[0]) && is_string($_where[0]) && //操作符
                            isset($_where[1]) //数据
                        ) {
                            //支持的操作
                            $_act = $_where[0];
                            $_value = $_where[1];
                            if (in_array($_act, ['>', '<', '=', '>=', '<=', '<>', '!='])) {
                                //默认为AND
                                if (!empty($query)) {
                                    $query[] = $_connector;
                                }
                                $query[] = "{$key} {$_act} ?";
                                $params[] = $key;
                                $data[] = strval($_value);;

                            } elseif (in_array($_act, ['IN', 'NOT IN'])) {
                                //构建数据和占位符
                                if (is_string($_value)) {
                                    $_value = explode(',', $_value);
                                }
                                $_placeholder = []; //占位符(?)
                                foreach ($_value as $item) {
                                    $_placeholder[] = '?';
                                    $data[] = trim($item);
                                }
                                $_placeholder = implode(', ', $_placeholder);

                                //默认为AND
                                if (!empty($query)) {
                                    $query[] = $_connector;
                                }
                                $query[] = "{$key} {$_act} ({$_placeholder})";
                                $params[] = $key;

                            } elseif (in_array($_act, ['BETWEEN', 'NOT BETWEEN']) &&
                                isset($_value[0]) &&
                                isset($_value[1])) {
                                //默认为AND
                                if (!empty($query)) {
                                    $query[] = $_connector;
                                }
                                $query[] = "{$key} {$_act} ? AND ?";
                                $params[] = $key;
                                $data[] = $_value[0];
                                $data[] = $_value[1];
                            }
                        }
                    }
                }
            }
            $query = implode(' ', $query);
        }
        return [
            'query' => $query,
            'params' => $params,
            'data' => $data,
        ];
    }

    /**
     * 给字段加上反引号
     * @param $key
     * @return string
     */
    protected static function addBackQuote($key) {
        $key_arr = explode('.', $key);
        foreach ($key_arr as $k => $v) {
            $key_arr[$k] = "`{$v}`";
        }
        return implode('.', $key_arr);
    }

    /**
     * 更新数据
     * @param string $table
     * @param array $data
     * @param array $where
     * @return bool|int
     * @throws \Exception
     */
    public static function update($table, $data, $where) {
        //构建SET数据
        $set_data = static::bindParams($data);
        $set = $set_data['query'];
        if (empty($set)) {
            return false;
        }
        //构建WHERE数据
        $where_data = static::bindParams($where);
        $_where = $where_data['query'];
        if (!empty($_where)) {
            $_where = " WHERE {$_where}";
        }
        //合并绑定数据
        $_data = array_merge($set_data['data'], $where_data['data']);
        //执行
        $sql = "UPDATE {$table} SET {$set} {$_where}";
        $stmt = static::query($sql, $_data);
        return $stmt->rowCount();
    }

    /**
     * 插入数据
     * @param string $table
     * @param array $data
     * @return bool|string
     * @throws \Exception
     */
    public static function insert($table, $data) {
        $bind_data = static::bindParams($data);
        $params = $bind_data['params'];
        if (empty($params)) {
            return false;
        }
        $field = implode(', ', $params);
        $mark = trim(str_repeat('?, ', count($params)), ', ');

        $sql = "INSERT INTO {$table} ({$field}) VALUES ($mark)";
        $stmt = static::query($sql, $bind_data['data']);
        return $stmt->fetch(); //static::getInstance()->lastInsertId();
    }

    /**
     * 获取最后插入的id
     * @return string
     * @throws \Exception
     */
    public static function getLastInsertId() {
        $db = static::getInstance();
        return $db->lastInsertId();
    }
    
    /**
     * 删除数据
     * @param string $table
     * @param array $where
     * @return bool|int
     * @throws \Exception
     */
    public static function del($table, $where) {
        $del_data = static::bindParams($where);
        $_where = $del_data['query'];
        if (empty($_where)) {
            return false;
        }

        $sql = "DELETE FROM {$table} WHERE {$_where}";
        $stmt = static::query($sql, $del_data['data']);
        return $stmt->rowCount();
    }

    /**
     * 执行sql，支持参数绑定
     * @param string $sql
     * @param array $bind_data
     * @return \PDOStatement
     * @throws \Exception
     */
    public static function query($sql, $bind_data = []) {
        static::$sqlHistory[] = [
            'type' => 'query',
            'sql' => $sql,
            'data' => $bind_data,
        ];

        $db = static::getInstance();
        $stmt = $db->prepare($sql);
        if ($stmt !== false) {
            $res = $stmt->execute($bind_data);
            if ($res === false) {
                $error = $stmt->errorInfo();
                static::logError($error, $sql);
            }
            return $stmt;
        } else {
            $error = $stmt->errorInfo();
            static::logError($error, $sql);
            throw new \Exception("{$error[1]} : {$error[2]}\n sql: {$sql}");
        }
    }

    /**
     * 直接执行sql语句
     * @param $sql
     * @return int
     * @throws \Exception
     */
    public static function execute($sql) {
        static::$sqlHistory[] = [
            'type' => 'execute',
            'sql' => $sql,
        ];
        $db = static::getInstance();
        return $db->exec($sql);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function getError() {
        $db = static::getInstance();
        return $db->errorInfo();
    }

    /**
     * @return array
     */
    public static function getHistory() {
        return static::$sqlHistory;
    }

    /**
     * @return array
     */
    public static function getLastSql() {
        return end(static::$sqlHistory);
    }

    /**
     * @param array $error PDOStatement::errorInfo()
     * @param string $sql
     * @throws \Exception
     */
    private static function logError($error, $sql) {
        $error_str = "{$error[1]} : {$error[2]}";
        Log::write("{$error_str} >> sql : {$sql}", 'SQL');
    }
}