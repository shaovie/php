<?php
/**
 * @Author shaowei
 * @Date   2015-07-18
 */

namespace src\common;

class Redis
{
    private $redis  = false;
    private $host   = '';
    private $port   = 0;
    private $prefix = '';

    //= public methods
    //
    public function __construct($host, $port, $prefix)
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
    }
    public function get($key)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->get($key);
    }
    public function mGet($keys)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->mGet($key);
    }
    public function set($key, $v)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->set($key, $v);
    }
    public function setEx($key, $expire, $v)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->setEx($key, $expire, $v);
    }
    public function expire($key, $expire)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->expire($key, $expire);
    }
    public function setTimeout($key, $timeout)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->setTimeout($key, $timeout);
    }
    public function del($key)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->del($key);
    }
    public function incr($key)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->incr($key);
    }
    public function lPush($key, $v)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->lPush($key, $v);
    }
    public function rPush($key, $v)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->rPush($key, $v);
    }
    public function lPop($key)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->lPop($key);
    }
    public function lTrim($key, $start, $stop)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->lTrim($key, $start, $stop);
    }
    public function lRange($key, $start, $end)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->lRange($key, $start, $end);
    }
    public function lSize($key)
    {
        if ($this->redis === false) {
            if ($this->connect() === false) {
                return false;
            }
        }
        return $this->redis->lSize($key);
    }

    //= private methods
    //
    private function connect()
    {
        if ($this->redis !== false) {
            return true;
        }
        $this->redis = new \Redis();
        try {
            $this->redis->pconnect($this->host, $this->port, 1.0);
            if (!empty($this->prefix)) {
                $this->redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
            }
        } catch (\RedisException $e) {
            $this->redis = false;
            $desc = 'redis - ' . $this->host . ':' . $this->port . ' - ' . $e->getMessage();
            Log::fatal($desc);
            return false;
        }
        return true;
    }
}
