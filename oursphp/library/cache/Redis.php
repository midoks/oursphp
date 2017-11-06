<?php

// +----------------------------------------------------------------------
// | oursphp [ simple and fast ]
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: midoks <627293072@qq.com>
// +----------------------------------------------------------------------

namespace frame\cache;

use frame\Config;
use \frame\exception\CommonException;

class Redis {

    private static $_cache;
    private static $_config = NULL;
    private static $_node   = NULL;

    private function __construct(){}

    /**
     * 注册redis配置信息
     * @param string $config 配置文件KEY
     */
    public static function injectOption($opName){
        self::$_config = Config::get($opName);
        return true;
    }

    /**
     * 注册redis配置信息
     * @param array $config 配置信息
     */
    public static function injectConfig($config){
        self::$_config = $config;
        return true;
    } 

    /**
     * 获取指定配置节点的redis实例
     * @param string $nodeName
     * @return \Redis
     */
    public static function  getInstance($node = 'default') {

        if (!extension_loaded('Redis')) {
            throw new \BadFunctionCallException('not support: Redis');
        }
        
        $node = 'redis';
        if (self::$_node){
            $node = self::$_node;
        }

        if(!isset(self::$_cache[$node])) {

            $_redis     = new \Redis();

            if ( !empty(self::$_config) ){
                $options    = self::$_config;
            } else {
                $options    = Config::get($node);
            }

            if($options == false) {
                throw new CommonException("redis缓存相关节点未配置：".$node, 'redis');
            }
            list($host, $port,$passwd,$dbindex) = $options;
            $_redis->pconnect($host,$port);

            if(!empty($passwd)) {
                $_redis->auth($passwd);
            }

            if(is_numeric($dbindex) && $dbindex>0 ) {
                $_redis->select($dbindex);
            }
            self::$_cache[$node] = $_redis;
        }
        return self::$_cache[$node];
    }

    /**
     * notice 本缓存不会存 [0 false null] 不加锁
     * @param $key
     * @param $time
     * @param $get_data_func
     * @param array $func_params
     * @return mixed
     */
    public static function accessCache($key, $time, $get_data_func, $func_params = array(), $nodeName = 'default') {
        $_redis     = self::getInstance($nodeName);
        $data       = $_redis->get($key);
        
        if (empty($data) || isset($_GET['_refresh'])) {
            $data = call_user_func_array($get_data_func, $func_params);

            if(is_object($data)||is_array($data)){
                $data = serialize($data);
            }
            $_redis->set($key, $data, $time);
        }
        $data_serl = @unserialize($data);
        if(is_object($data_serl)||is_array($data_serl)){
            $data = $data_serl;
        }
        return $data;
    }

    /**
     * 本缓存不会存 [0 false null] 加锁
     * @param $key
     * @param $time
     * @param $get_data_func
     * @param array $func_params
     * @return mixed
     */
    public static function accessCacheWithLock($key, $time, $get_data_func, $func_params=array(),$nodeName='default') {
        $_redis=self::getInstance($nodeName);
        $data = $_redis->get($key);

        if (empty($data) || isset($_GET['_refresh'])) {
            if($_redis->setnx($key, null)) {
                $data = call_user_func_array($get_data_func, $func_params);

                if (!empty($data)) {
                    if(is_object($data)||is_array($data)){
                        $data = serialize($data);
                    }
                    $_redis->set($key, $data, $time);
                }

            } else {
                for($i=0; $i<10; $i++) { //5秒没有反应，就出白页吧，系统貌似已经不行了
                    sleep(0.5);
                    $data = $_redis->get($key);
                    if ($data !== false)
                        break;

                }
            }
        }

        $data_serl = @unserialize($data);
        if(is_object($data_serl)||is_array($data_serl)){
            $data = $data_serl;
        }
        return $data;
    }
}