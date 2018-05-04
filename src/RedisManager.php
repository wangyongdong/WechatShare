<?php
namespace RedisManager;
/**
 * 单例模式对 redis 实例的操作的进一步封装
 * Class RedisManager
 * @package RedisManager
 */
class RedisManager {
    private static $_instance = NULL;
    private static $_config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',       //密码
        'index' => 0,           //db库 0-15 共16个库
        'timeout' => 0,         //超时时间
        'persistent' => false,  //是否长链接
        'expire' => 7000,       //过期时间
    ];

    /**
     * 私有化构造函数，防止外界调用构造新的对象
     */
    private function __construct() {}

    /**
     * 获取redis连接的唯一出口
     */
    private static function getInstance($config) {
        if(!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        //设置配置参数
        self::$_config = array_merge(self::$_config, $config);

        // 调用私有化方法
        return self::$_instance->connect();
    }

    /**
     * 连接ocean 上的redis的私有化方法
     * @return Redis
     */
     private function connect() {
        try {
            $func = self::$_config['persistent'] ? 'pconnect' : 'connect';     //长链接

            $redis = new \Redis();
            $redis->$func(self::$_config['host'], self::$_config['port'], self::$_config['timeout']);

            //auth
            if (!empty(self::$_config['password'])) {
                $redis->auth(self::$_config['password']);
            }

            //选择库
            $redis->select(self::$_config['index']);
        } catch (Exception $e) {
            echo $e->getMessage().'<br/>';
        }

        return $redis;
    }

    /**
     * 获取缓存内容
     * @param $sKey 键值
     * @param $iDBIndex DB索引
     * return string
     */
    public static function get($sKey, $config = array()) {
        if (empty($sKey)) {
            return false;
        }

        $redis = self::getInstance($config);
        $data = $redis->get($sKey);
        $redis->close();

        return $data;
    }

    public static function set($sKey, $sValue, $config = array()) {
        if (empty($sKey) || !is_scalar($sValue)) {
            return false;
        }
        $redis = self::getInstance($config);

        $redis->set($sKey, $sValue);
        if (!empty($config['expire'])) {
            self::$_config['expire'] = !empty($config['expire']) ? $config['expire'] : self::$_config['expire'];
            $redis->expire($sKey, self::$_config['expire']);
        }
        $redis->close();
    }

    /**
     * @param $key              缓存key
     * @param string $params    参数
     * @param null $callback    回调函数
     * @param array $config     配置
     * @return bool|mixed
     */
    public static function load($key, $params = '', $callback = null, $config = array()) {
        if (!is_callable($callback)) {
            return false;
        }

        $redis = self::getInstance($config);
        $key = self::suffix($key, $params);

        $data = $redis->get($key);

        if (empty($data) && is_callable($callback)) {
            $data = $callback();
            if(!empty($data)) {
                $data = json_encode($data);
                $redis->set($key, $data);
            }
        }
        $redis->close();

        if(!empty($data)) {
            return json_decode($data, true);
        }

    }

    /**
     * 获取缓存键后缀
     * @param string | array $params
     * @return string
     */
    public static function suffix($key, $params = '') {
        $suffix = '';
        if(is_array($params)) {
            $suffix .= implode('_', $params);
        } else {
            $suffix .= $params;
        }
        return $key . ':' . md5(trim($suffix, '_'));
    }

    /**
     * 私有化克隆函数，防止类外克隆对象
     */
    private function __clone() {
        // TODO: Implement __clone() method.
    }

}