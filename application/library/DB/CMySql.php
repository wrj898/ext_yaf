<?php

/**
 * Created by PhpStorm.
 * User: jonson
 * Date: 14-2-20
 * Time: 上午8:33
 */
class DB_CMySql
{
    private $db;
    private $readDB;
    private $writeDB;
    private $readConfig;
    private $writeConfig;
    protected $tablePrefix;

    public function __construct()
    {
        $config = Yaf_Registry::get('config')->mysql;
        $this->readConfig = $config->read;
        $this->writeConfig = $config->write;
        $this->db = $this->readConnection();
    }

    private function readConnection()
    {
        if ($this->readDB != null && mysqli_ping($this->readDB)) {
            $this->db = $this->readDB;
        } else {
            $this->tablePrefix = $this->readConfig->prefix;
            $this->readDB = $this->getConnection($this->readConfig);
            $this->readDB->query("SET NAMES '{$this->readConfig->charset}'");
            $this->db = $this->readDB;
        }
        return $this->db;
    }

    private function writeConnection() //只写数据库连接
    {
        if ($this->writeDB != null && mysqli_ping($this->writeDB)) {
            $this->db = $this->writeDB;
        } else {
            $this->tablePrefix = $this->readConfig->prefix;
            $this->writeDB = $this->getConnection($this->writeConfig);
            $this->writeDB->query("SET NAMES '{$this->writeConfig->charset}'");
            $this->db = $this->writeDB;
        }
        return $this->db;
    }

    private function getConnection($DBConfig)
    {
        if (is_null($DBConfig)) {
            throw new Exception("数据库配置文件错误！");
        }
        $db = new mysqli($DBConfig->host, $DBConfig->username, $DBConfig->password, $DBConfig->dbname, $DBConfig->port);
        if (mysqli_connect_error()) {
            throw new Exception('连接数据库错误：(' . mysqli_connect_errno() . ')' . mysqli_connect_error());
        }
        return $db;
    }

    /**
     * @param string $sql
     * @return mixed
     * @throws Exception
     * 读写分离
     */
    protected function query($sql = '')
    {
        if (preg_grep('/^\s*select/i', explode(' ', $sql))) {
            $this->readConnection();
        } else {
            $this->writeConnection();
        }
        $result = $this->db->query($sql);
        if ($this->db->errno) {
            throw new Exception('Query Error:' . $this->db->errno . ":" . $this->db->error . " SQL:" . $sql);
        }
        return $result;
    }

    protected function query_array($sql)
    {
        $result = $this->query($sql);
        $returnArray = array();
        while ($row = $result->fetch_assoc()) {
            array_push($returnArray, $row);
        }
        return $returnArray;
    }

    protected function query_object($sql)
    {
        $result = $this->query($sql);
        while ($object = $result->fetch_object()) {
            return $object;
        }
        return null;
    }

    public function DB()
    {
        return $this->db;
    }
} 