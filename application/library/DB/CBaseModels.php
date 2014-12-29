<?php

/**
 * Created by PhpStorm.
 * User: jonson
 * Date: 14-2-20
 * Time: 上午8:44
 */
class DB_CBaseModels extends DB_CMySql
{
    private $tableName;
    private $tableField;
    private $attributes;
    private $_showFields;
    public $limit;
    public $currentPage;
    public $totalPage;
    private $startRow;
    private $totalRows;

    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->tablePrefix . $this->getTableName();
        if (Redis_CServer::getRedis()->get($this->tableName) == null) {
            $sql = 'show fields from ' . $this->getTableName();
            $this->tableField = $this->query_array($sql);
            Redis_CServer::getRedis()->set($this->getTableName(), json_encode($this->tableField));
        }
        $this->tableField = json_decode(Redis_CServer::getRedis()->get($this->getTableName()));
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($value)
    {
        $this->tableName = $value;
    }

    public function getTableField()
    {
        return $this->tableField;
    }

    public function setAttributes($value)
    {
        $value = (array)$value;
        $this->attributes = new stdClass();
        foreach ($this->tableField as $field) {
            $field = (object)$field;
            if (array_key_exists($field->Field, $value)) {
                if ($field->Null == "NO" && (!isset($value[$field->Field]) || empty($value[$field->Field])) && $field->Extra != "auto_increment") {
                    $info = new stdClass();
                    $info->code = 3;
                    $info->data = $field->Field . "字段不能为空";
                    exit(json_encode($info));
                }
                $this->attributes->$field->Field = $value[$field->Field];
            }
        }
    }

    private function checkPRI($key)
    {
        foreach ($this->tableField as $value) {
            $value = (object)$value;
            if ($value->Field == $key && $value->Key = "PRI") {
                return true;
            }
        }
        return false;
    }

    public function insert()
    {
        $key_array = array();
        $value_array = array();
        if (empty($this->attributes)) return false;
        foreach ($this->attributes as $key => $value) {
            array_push($key_array, $key);
            array_push($value_array, $value);
        }
        $sql = "insert into " . $this->tableName . "(" . implode(",", $key_array) . ") values(" . implode(",", $value_array) . ")";
        $this->query($sql);
        return $this->DB()->insert_id;
    }

    public function update($setValue = null, $condition = array())
    {
        $sql = "update " . $this->tableName . " set ";
        $where = "";
        $hasWhere = false;
        $hasSet = false;
        if (is_null($setValue) || empty($setStr)) {
            if (empty($this->attributes)) return false;
            foreach ($this->attributes as $key => $value) {
                if ($this->checkPRI($key)) {
                    $where .= $hasWhere ? ' and ' : ' where ';
                    $where .= $this->tableName . "." . $key . "='" . addslashes($value) . "'";
                    $hasWhere = true;
                } else {
                    $setValue .= $hasSet ? "," : null;
                    $setValue .= $this->tableName . "." . $key . "='" . addslashes($value) . "'";
                    $hasSet = true;
                }
            }
        } else {
            if (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    $where .= $hasWhere ? ' and ' : ' where ';
                    $where .= $this->tableName . "." . $key . "='" . addslashes($value) . "'";
                    $hasWhere = true;
                }
            } else {
                $where .= $hasWhere ? " and " : " where ";
                $where .= $condition;
                $hasWhere = true;
            }
            if (is_array($setValue)) {
                foreach ($setValue as $key => $value) {
                    $setValue .= $hasSet ? "," : null;
                    $setValue .= $this->tableName . "." . $key . "='" . addslashes($value) . "'";
                    $hasSet = true;
                }
            }
        }
        if (empty($where)) return false;
        $sql .= $setValue . $where;
        return $this->query($sql);
    }

    public function delete()
    {
        $sql = "delete from " . $this->tableName;
        $where = "";
        $hasWhere = false;
        if (empty($this->attributes)) return false;
        foreach ($this->attributes as $key => $value) {
            if (!empty($value)) {
                $where .= $hasWhere ? " and " : " where ";
                $where .= $this->tableName . "." . $key . "='" . addslashes($value) . "'";
                $hasWhere = true;
            }
        }
        if (empty($where)) return false;
        $sql .= $where;
        return $this->query($sql);
    }

    public function findByPk($id)
    {
        $sql = "select * from " . $this->tableName;
        $where = "";
        foreach ($this->tableField as $value) {
            $value = (object)$value;
            if ($value->Key == "PRI" && $value->Extra == "auto_increment") {
                $where = " where ";
                $where .= $this->tableName . "." . $value->Field . "='" . addslashes($id) . "'";
            }
        }
        if (empty($where)) return false;
        $sql .= $where;
        return $this->query_object($sql);
    }

    public function findByCondition($option = array(), $relation = array())
    {
        $this->_showFields = array();
        $sql = $this->sql($option, $relation);
        $c = $this->query_array($sql);
        $this->totalRows = $c[0]['c'];
        if (!empty($this->limit) || $this->limit > 0) {
            $this->totalPage = ceil($this->totalRows / $this->limit);
        }
        $this->startRow = ((empty($this->currentPage) ? 1 : $this->currentPage) - 1) * $this->limit;
        $this->_showFields = array_merge($this->Show($option), $this->_showFields);
        if (empty($this->_showFields)) {
            array_push($this->_showFields, "*");
        }
        $sql = str_replace('count(*) as c', implode(',', $this->_showFields), $sql);
        if (!is_null($this->limit) && !empty($this->limit)) {
            $sql .= ' limit ' . $this->startRow . ',' . $this->limit;
        }
        return $this->query_array($sql);
    }

    private function sql($option = array(), $relation = array())
    {
        $sql = 'select count(*) as c from ' . $this->tableName;
        $option = (object)$option;
        $relation = (object)$relation;
        $sql .= $this->Relation($relation);
        $sql .= $this->Condition($option);
        $sql .= $this->Group($option);
        $sql .= $this->Order($option);
        return $sql;
    }

    private function Condition($option)
    {
        $option = (object)$option;
        if (isset($option->condition)) {
            if (is_array($option->condition)) {
                $where = "";
                $hasCondition = false;
                foreach ($option->condition as $condition) {
                    $condition = (object)$condition;
                    if (strtolower($condition->op) != 'or') {
                        $where .= ($hasCondition ? ' ' . $condition->op . ' ' : ' where ') . $this->processRule($condition->rule);
                        $hasCondition = true;
                    }
                }
                $where .= $this->ConditionOr($option, $hasCondition);
                return $where;
            } else {
                return $option->condition;
            }
        }
    }

    private function processRule($condition = array())
    {
        $rule = $condition;
        $where = '';
        switch ($rule->op) {
            case 'like':
                foreach ($rule as $key => $value) {
                    if ($key != 'op') {
                        $where .= ((isset($value->function) && $value->function) ? "" : $key . '.');
                        foreach ($value as $k => $v) {
                            ($k != "function") ? $where .= $k . ' ' . $rule->op . ' "%' . addslashes($v) . '%"' : null;
                        }
                    }
                }
                break;
            case 'between':
                foreach ($rule as $key => $value) {
                    if ($key != 'op') {
                        $where .= ((isset($value->function) && $value->function) ? "" : $key . '.');
                        foreach ($value as $k => $v) {
                            ($k != "function") ? $where .= $k . ' ' . 'between "' . addslashes($v->start) . '" and "' . addslashes($v->end) . '"' : null;
                        }
                    }
                }
                break;
            default:
                foreach ($rule as $key => $value) {
                    if ($key != 'op') {
                        $where .= ((isset($value->function) && $value->function) ? "" : $key . '.');
                        foreach ($value as $k => $v) {
                            ($k != "function") ? $where .= $k . $rule->op . '"' . addslashes($v) . '"' : null;
                        }
                    }
                }
        }
        return $where;
    }

    private function ConditionOr($option, $hasCondition)
    {
        $where = "";
        if (isset($option->condition) && !empty($option->condition)) {
            $where = $hasCondition ? ' and (' : ' where (';
            $firstOr = true;
            $condition = $option->condition;
            if (!is_array($condition)) return '';
            foreach ($condition as $condition_value) {
                if (strtolower($condition_value->op === "or")) {
                    $where .= ($firstOr ? "" : " " . $condition_value->op);
                    $where .= " " . $this->processRule($condition_value->rule);
                    $firstOr = false;
                }
            }
            $where .= ")";
            $where = $firstOr ? "" : $where;
        }
        return $where;
    }

    //分组
    private function Group($option)
    {
        $option = (object)$option;
        $hasGroup = false;
        $s = "";
        if (isset($option->group)) {
            foreach ($option->group as $key => $group) {
                foreach ($group as $v) {
                    $s .= $hasGroup ? "," : " group by ";
                    $s .= $key . "." . $v;
                }
            }
        }
        return $s;
    }

    //排序
    private function Order($option)
    {
        $option = (object)$option;
        $order = "";
        $hasOrder = false;
        if (isset($option->order)) {
            foreach ($option->order as $key => $value) {
                foreach ($value as $k => $v) {
                    $order .= $hasOrder ? "," : " order by ";
                    $order .= $key . "." . $k . " " . $v;
                }
            }
        }
        return $order;
    }

    //显示字段
    private function Show($option)
    {
        $option = (object)$option;
        $_show = array();
        if (isset($option->show)) {
            foreach ($option->show as $key => $show) {
                foreach ($show as $value) {
                    array_push($_show, $key . "." . $value);
                }
            }
        };
        if (isset($option->operation)) {
            foreach ($option->operation as $key => $operation) {
                switch ($key) {
                    case "sum":
                        foreach ($operation as $oKey => $value) {
                            array_push($_show, "sum(" . $oKey . "." . $value . ") as sum_" . $value);
                        }
                        break;
                    case "count":
                        foreach ($operation as $oKey => $value) {
                            array_push($_show, "count(" . $oKey . "." . $value . ") as count_" . $value);
                        }
                        break;
                    case "avg":
                        foreach ($operation as $oKey => $value) {
                            array_push($_show, "avg(" . $oKey . "." . $value . ") as avg_" . $value);
                        }
                        break;
                    case "max":
                        foreach ($operation as $oKey => $value) {
                            array_push($_show, "max(" . $oKey . "." . $value . ") as max_" . $value);
                        }
                        break;
                    case "min":
                        foreach ($operation as $oKey => $value) {
                            array_push($_show, "min(" . $oKey . "." . $value . ") as min_" . $value);
                        }
                        break;
                }
            }
        };
        return $_show;
    }

    private function Relation($relation)
    {
        $join = '';
        foreach ($relation as $relation_key => $relation_value) {
            $join .= (!isset($relation_value->relationType) || empty($relation_value->relationType) ? ' join ' : ' ' . $relation_value->relationType . ' ') . $relation_key;
            $hasManyOn = false;
            foreach ($relation_value->relation as $key => $value) {
                $join .= ($hasManyOn ? ' and ' : ' on ') . $relation_key . '.' . $key . '=' . $value;
                $hasManyOn = true;
            }
            if (isset($relation_value->show) && !empty($relation_value->show)) {
                foreach ($relation_value->show as $value) {
                    array_push($this->_showFields, $relation_key . '.' . $value);
                }
            }
        }
        return $join;
    }
} 