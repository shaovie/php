<?php
/**
 * @Author shaowei
 * @Date   2015-07-20
 */

namespace src\common;

class Mongodb
{
    private $client = false;
    private $db = false;
    private $connected = false;
    private $collection = false;

    private $dbName = '';
    private $host   = '';

    // host 127.0.0.1:6111
    function __construct($host, $dbName)
    {
        $this->host = $host;
        $this->dbName = $dbName;
    }
    private function connect()
    {
        if (empty($this->host)) {
            return false;
        }

        try {
            $this->client = new \MongoClient('mongodb://' . $this->host);
        } catch (MongoConnectionException $e) {
            $this->dbErr('connect db ' . $this->host . ' failed! - ' . $e->getMessage());
            return false;
        }

        try {
            $this->db = $this->client->selectDB($this->dbName);
        } catch (Exception $e) {
            $this->dbErr('select db ' . $this->dbName . ' failed! - ' . $e->getMessage());
            return false;
        }
        $this->connected = true;
        return true;
    }

    private function selectCol($tb)
    {
        try {
            $this->collection = $this->db->selectCollection($tb);
        } catch (Exception $e) {
            $this->dbErr('select collection ' . $tb . ' failed! - ' . $e->getMessage());
            return false;
        }
        return true;
    }
    private function init($tb)
    {
        if (!$this->connected) {
            if (!$this->connect()) {
                return false;
            }
        }
        if (!$this->select_col($tb)) {
            return false;
        }
        return true;
    }
    private function dbErr($err = '*')
    {
        Log::error('mongodb - ' . $err);
    }

    //=
    public function find($tb, $key, $fileds = array())
    {
        if (!$this->init($tb)) {
            return false;
        }

        try {
            $result = array();
            $ret = $this->collection->find($key, $fileds);
            foreach($ret as $row) {
                $result[] = $row;
            }
            return $result;
        } catch (MongoConnectionException $e) {
            $this->dbErr('find ' . $tb . '['
                . json_encode($key)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoExecutionTimeoutException $e) {
            $this->dbErr('find ' . $tb . '['
                . json_encode($key)
                . '] timeout! - '
                . $e->getMessage());
        }
        return false;
    }
    public function find_one($tb, $key, $fileds = array())
    {
        if (!$this->init($tb)) {
            return false;
        }

        $ret = false;
        try {
            $ret = $this->collection->findOne($key, $fileds, array('maxTimeMS' => 1500));
        } catch (MongoConnectionException $e) {
            $this->dbErr('find one ' . $tb . '['
                . json_encode($key)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoExecutionTimeoutException $e) {
            $this->dbErr('find one ' . $tb . '['
                . json_encode($key)
                . '] timeout! - '
                . $e->getMessage());
        }
        return $ret;
    }
    // $safe 是否安全操作 false:等待服务器的响应直接返回
    //                     true:等待服务器的响应(数据非常重要时推荐)
    // $fsync 操作后是否立即更新到碰盘,默认情况下由服务器决定
    public function insert($tb, $sets, $safe = false, $fsync = false)
    {
        if (!$this->init($tb)) {
            return false;
        }
        $ret = false;
        try {
            $this->collection->insert($sets, array('w' => (int)$safe, 'fsync' => $fsync));
            $ret = true;
        } catch (MongoException $e) {
            $this->dbErr('insert ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoCursorException $e) {
            $this->dbErr('insert ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoCursorTimeoutException $e) {
            $this->dbErr('insert ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        }
        return $ret;
    }
    // $safe 是否安全操作 false:等待服务器的响应直接返回
    //                     true:等待服务器的响应(数据非常重要时推荐)
    // $fsync 操作后是否立即更新到碰盘,默认情况下由服务器决定
    public function update($tb, $where, $sets, $safe = false, $fsync = false)
    {
        if (!$this->init($tb)) {
            return false;
        }
        $ret = false;
        try {
            $this->collection->update($where,
                array('$set' => $sets),
                array('w' => (int)$safe, 'fsync' => $fsync));
            $ret = true;
        } catch (MongoException $e) {
            $this->dbErr('update ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoCursorException $e) {
            $this->dbErr('update ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        } catch (MongoCursorTimeoutException $e) {
            $this->dbErr('update ' . $tb . '['
                . json_encode($sets)
                . '] exception! - '
                . $e->getMessage());
        }
        return $ret;
    }
}

