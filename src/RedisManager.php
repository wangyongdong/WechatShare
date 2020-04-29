<?php
namespace RedisClient;

/**
 * 单例模式对 redis 实例的操作的进一步封装
 * Class RedisManager
 * @package RedisManager
 */
class RedisManager {
    public static $config = array();

    public static function setConfig($config = array()) {
        if(!empty($config)) {
            self::$config = $config;
        }
    }

    public static function getRedis($config = array()) {
        if(class_exists('\Redis')) {
            return \RedisClient\RedisCli::getInstance($config);
        }
        if(class_exists('\Predis\Client')) {
            return \RedisClient\PRedisCli::getInstance($config);
        }
    }

    public static function load($key, $params, $cb, $expire) {
        $redis = self::getRedis(self::$config);
        return $redis->load($key, $params, $cb, $expire);
    }
}
