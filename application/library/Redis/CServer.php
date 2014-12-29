<?php
/**
 * Created by PhpStorm.
 * User: jonson
 * Date: 14-2-16
 * Time: 上午11:19
 */
class Redis_CServer {
    private static $_redis;
    public static function getRedis(){
        if(empty(self::$_redis)||is_null(self::$_redis)){
            $config = Yaf_Registry::get('config')->redis;
            self::$_redis=new Redis();
            self::$_redis->connect($config->host,$config->port);
        }
        return self::$_redis;
    }
} 