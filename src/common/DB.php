<?php
/**
 * @Author shaowei
 * @Date   2015-07-18
 */

namespace src\common;

class DB
{
    private $db      = false;

    private $dsn     = '';
    private $user    = '';
    private $passwd  = '';

    //= static methods
    public static function get($db = 'r')
    {
        static $rdb = false;
        static $wdb = false;
        if ($db == 'r') {
            if ($rdb === false) {
                $rdb = new DB(DB_R_DSN, DB_R_USER, DB_R_PASSWD);
            }
            return $rdb;
        } elseif ($db == 'w') {
            if ($wdb === false) {
                $wdb = new DB(DB_W_DSN, DB_W_USER, DB_W_PASSWD);
            }
            return $wdb;
        }
        return false;
    }

    //= public methods
    //
    public function __construct($dsn, $user, $passwd)
    {
        $this->dsn     = $dsn;
        $this->user    = $user;
        $this->passwd  = $passwd;
    }

    // fetch_one('tb',
    //          'c1,c2',
    //          array('user', 'passwd'),
    //          array($user, $passwd),
    //          array('and'))
    // select c1,c2 from tb where user='xx' and passwd='yy';
    //
    // return array('col' => val[, ...]) or false on failure
    public function fetchOne(
        $table,
        $fileds,
        $condNames, $condValues,
        $relation = false
    ) {
        $rows = $this->fetch(
            $table,
            $fileds,
            $condNames, $condValues,
            $relation,
            false, false,
            array(1)
        );
        if ($rows === false) {
            return false;
        }
        return empty($rows) ? array() : $rows[0];
    }

    // fetchAll('tb',
    //          'c1,c2',
    //          array('user', 'passwd', 'ctime>'),
    //          array('xx', 'yy', 12),
    //          array('and', 'or'),
    //          array('id', 'ctime'),
    //          array('asc', 'desc');
    // select c1,c2 from tb where user='xx' and passwd='yy' or ctime>12 order by id asc, ctime desc;
    //
    // return array(array('col' => val)[, ...]) or false on failure
    public function fetchAll(
        $table,
        $fileds,
        $condNames, $condValues,
        $relation = false,
        $orderCols = false, $orderTypes = false
    ) {
        return $this->fetch(
            $table,
            $fileds,
            $condNames, $condValues,
            $relation,
            $orderCols, $orderTypes,
            false,
            array(1)
        );
    }

    // fetchSome('tb',
    //          'c1,c2',
    //          array('user', 'passwd', 'ctime>'),
    //          array('xx', 'yy', 12),
    //          array('and', 'or'),
    //          array('id', 'ctime'),
    //          array('asc', 'desc'),
    //          array(0, 10));
    // select c1,c2 from user where user='xx' and passwd='yy' or ctime>12 order by id asc, ctime desc limit 0,10;
    //
    // return array(array('col' => val)[, ...]) or false on failure
    public function fetchSome(
        $table,
        $fileds,
        $condNames, $condValues,
        $relation = false,
        $orderCols = false, $orderTypes = false,
        $limit = false
    ) {
        return $this->fetch(
            $table,
            $fileds,
            $condNames, $condValues,
            $relation,
            $orderCols, $orderTypes,
            $limit
        );
    }

    // fetchCount('user',
    //            array('user', 'passwd', 'ctime>'),
    //            array('xx', 'yy', 12),
    //            array('and', 'or'));
    // select c1,c2 from user where user='xx' and passwd='yy' or ctime>12 order by id asc, ctime desc limit 0,10;
    // 
    // return (int)count or false on failure
    public function fetchCount(
        $table,
        $condNames, $condValues,
        $relation = false
    ) {
        $rows = $this->fetch(
            $table,
            'count(*) as c',
            $condNames, $condValues,
            $relation
        );
        if ($rows === false) {
            return false;
        }
        return empty($rows) ? 0 : (int)$rows[0]['c'];
    }

    // executes an SQL statement , return array(), or false on failure;
    public function rawQuery($sql)
    {
        if (empty($sql)) {
            return false;
        }
        return $this->query($sql);
    }

    // executes an SQL statement , return the number of affected rows
    // just for update/delete
    public function rawExec($sql)
    {
        if (empty($sql)) {
            return false;
        }
        return $this->exec($sql);
    }

    // insertOne('tb',
    //          array('user' => 'cui', 'passwd' => 'shaowei')
    //          );
    //
    // return last insert id
    public function insertOne($tb, $data)
    {
        if (empty($tb) || empty($data)) {
            return false;
        }
        return $this->insert($tb, $data);
    }

    // update('tb',
    //       array('user' => 'cui', 'passwd' => 'shaowei'),
    //       array('id', 'name'), array(12, 'xyx'),
    //       array('and')
    //       )
    //
    // return affect rows on ok, return false on fail!
    public function update($tb,
        $data,
        $condNames, $condValues,
        $relation = false
    ) {
        if (empty($tb) || empty($data)) {
            return false;
        }
        return $this->_update($tb, $data, $condNames, $condValues, $relation);
    }

    // returns true on success or false on failure
    public function beginTransaction()
    {
        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        return $this->db->beginTransaction();
    }

    // returns true on success or false on failure
    public function rollBack()
    {
        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        return $this->db->rollBack();
    }

    // returns true on success or false on failure
    public function commit()
    {
        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        return $this->db->commit();
    }


    //= private method
    //
    private function connect()
    {
        if ($this->db) {
            return true;
        }
        $options = array(
            \PDO::ATTR_PERSISTENT => true,
            \PDO::ATTR_TIMEOUT    => 3,
        );
        try {
            $this->db = new \PDO($this->dsn, $this->user, $this->passwd, $options);
        } catch (\PDOException $e) {
            $desc = 'mysql dsn=' . $this->dsn . ' connection failed: ' . $e->getMessage();
            Log::fatal($desc);
            $this->db = false;
            return false;
        }
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $this->db->exec("SET NAMES utf8");
        return true;
    }
    private function fetch(
        $table,
        $fileds,
        $condNames, $condValues,
        $relation = false,
        $orderCols = false, $orderTypes = false,
        $limit = false
    ) {
        if (empty($table)
            || count($condNames) !== count($condValues)
            || (!empty($relation) && (count($condNames) - 1 != count($relation)))
            || count($orderCols) !== count($orderTypes)
            || (!empty($limit) && !is_array($limit))) {
            Log::error('db - fetch params error! ' . json_encode(func_get_args()));
            return false;
        }

        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        $sql = 'select ' . (empty($fileds) ? '*' : $fileds)
            . ' from ' . $table;
        if (!empty($condNames)) {
            $sql .= ' where ';
        }
        $itor = 0;
        if (!empty($condNames)) {
            foreach ($condNames as $condName) {
                if ($itor > 0) {
                    $sql .= ' ' . (empty($relation) ? 'and' : $relation[$itor - 1]) . ' ';
                }
                $opt = '';
                if (strpbrk($condName, '><!=') === false) {
                    $opt = ' =';
                }
                $sql .= $condName . $opt . ' ?';
                ++$itor;
            }
        }
        if (!empty($orderCols)) {
            $sql .= ' order by';
            $itor = 0;
            foreach ($orderCols as $orderCol) {
                if ($itor > 0) {
                    $sql .= ',';
                }
                $sql .= ' ' . $orderCol . ' ' . $orderTypes[$itor];
                ++$itor;
            }
        }
        if (!empty($limit)) {
            $sql .= ' limit ' . $limit[0];
            if (isset($limit[1])) {
                $sql .= ',' . $limit[1];
            }
        }
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            $err = $this->db->errorInfo();
            Log::error('db - prepare sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        $ret = $stmt->execute($condValues);
        if ($ret === false) {
            $err = $stmt->errorInfo();
            Log::error('db - execute sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        $rows = array();
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function query($sql)
    {
        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        $rows = array();
        $stmt = $this->db->query($sql, \PDO::FETCH_ASSOC);
        if ($stmt === false) {
            Log::error('db - raw query sql[' . $sql . '] failure!');
            $this->close();
            return false;
        }

        foreach ($stmt as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    private function exec($sql)
    {
        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        $ret = $this->db->exec($sql);
        if ($ret === false) {
            Log::error('db - raw exec sql[' . $sql . '] failure!');
            $this->close();
            return false;
        }
        return $ret;
    }

    // safety insert
    private function insert($table, $data)
    {
        if (empty($table)
            || empty($data)) {
            Log::error('db - insert params error! ' . json_encode(func_get_args()));
            return false;
        }

        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholder = substr(str_repeat('?,', count($keys)), 0, -1);
        $sql = "insert into $table($fields)values($placeholder)";
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            $err = $this->db->errorInfo();
            Log::error('db - prepare sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        $ret = $stmt->execute(array_values($data));
        if ($ret === false) {
            $err = $stmt->errorInfo();
            Log::error('db - execute sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        return $this->db->lastInsertId();
    }

    // safety update
    private function _update($table, $data, $condNames, $condValues, $relation)
    {
        if (empty($table)
            || empty($data)
            || count($condNames) !== count($condValues)
            || (!empty($relation) && (count($condNames) - 1 != count($relation)))
        ) {
            Log::error('db - update params error! ' . json_encode(func_get_args()));
            return false;
        }

        if (!$this->db) {
            if (!$this->connect()) {
                return false;
            }
        }

        $fields = '';
        foreach ($data as $key => $val) {
            $fields .= $key . '=?,';
        }
        if (empty($fields)) {
            return false;
        }
        $fields = substr($fields, 0, -1);
        $sql = "update $table set $fields";
        if (!empty($condNames)) {
            $sql .= ' where ';
        }
        $itor = 0;
        if (!empty($condNames)) {
            foreach ($condNames as $condName) {
                if ($itor > 0) {
                    $sql .= ' ' . (empty($relation) ? 'and' : $relation[$itor - 1]) . ' ';
                }
                $opt = '';
                if (strpbrk($condName, '><!=') === false) {
                    $opt = ' =';
                }
                $sql .= $condName . $opt . ' ?';
                ++$itor;
            }
        }
        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            $err = $this->db->errorInfo();
            Log::error('db - prepare sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        $ret = $stmt->execute(array_merge(array_values($data), $condValues));
        if ($ret === false) {
            $err = $stmt->errorInfo();
            Log::error('db - execute sql[' . $sql . '] failure!' . $err[2]);
            $this->close();
            return false;
        }
        return $stmt->rowCount();
    }

    private function close()
    {
        $this->db = false;
    }
}
