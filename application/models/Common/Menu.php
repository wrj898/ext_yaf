<?php

/**
 * Created by PhpStorm.
 * User: jonson
 * Mail: jonson.xu@gmail.com
 * Date: 14-5-23
 * Time: 上午10:02
 */
class Common_MenuModel extends DB_CBaseModels
{
    private static $_instance;

    public function __construct()
    {
        $this->setTableName("menu");
        parent::__construct();
    }

    public static function getInstance()
    {
        return !self::$_instance ? new self() : self::$_instance;
    }
} 