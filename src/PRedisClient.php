<?php
namespace RedisClient;
/**
 * 单例模式对 redis 实例的操作的进一步封装
 * Class RedisManager
 * @package RedisManager
 */
class PRedisCli {

    /**
     * @var null
     */
    private static $_instance = NULL;

    /**
     * @var array
     */
    private static $_config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',       //密码
        'database' => 0,        //db库 0-15 共16个库
        'timeout' => 0,         //超时时间
        'expire' => 7000,       //过期时间
    );

    /**
     * @var null|\Redis
     */
    private $redis = null;

    /**
     * 私有化构造函数，防止外界调用构造新的对象，连接redis
     * RedisManager constructor.
     * @param array $config
     */
    private function __construct(array $config) {
        //设置配置参数
        if(!empty($config)) {
            self::$_config = array_merge(self::$_config, $config);
        }
        try {
            $this->redis = new \Predis\Client(self::$_config);

            //auth
            if (!empty(self::$_config['password'])) {
                $this->redis->auth(self::$_config['password']);
            }

        } catch (Exception $e) {
            echo $e->getMessage().'<br/>';
        }
    }


    /**
     * 获取redis连接的唯一出口
     * @param array $config
     * @return null|RedisManager
     */
    public static function getInstance($config = array()) {
        if(!self::$_instance instanceof self) {
            self::$_instance = new self($config);
        }
        return self::$_instance;
    }

    /**
     * 字符串 - 获取缓存内容
     * @param $sKey 键值
     * return string
     */
    public function get($sKey) {
        if (empty($sKey)) {
            return false;
        }

        $data = $this->redis->get($sKey);
        return !empty($data) ? json_decode($data, true) : $data;
    }

    /**
     * 字符串 - 设置缓存
     * @param $sKey
     * @param $sValue
     * @param string $expire
     * @return bool
     */
    public function set($sKey, $sValue, $expire = null) {
        if (empty($sKey)) {
            return false;
        }

        $this->redis->set($sKey, json_encode($sValue));

        if (!empty($expire)) {
            self::$_config['expire'] = !empty($expire) ? $expire : self::$_config['expire'];
            $this->redis->expire($sKey, self::$_config['expire']);
        }

    }

    public function load($key, $params = '', $callback = null, $expire = 0) {
        if (!is_callable($callback)) {
            return false;
        }

        $key = $this->prefix($key, $params);

        $data = $this->get($key);

        if (empty($data) && is_callable($callback)) {
            $data = $callback();

            if(!empty($data)) {
                $this->set($key, $data, $expire);
            }
        }
        return $data;
    }

    public function getAll($sKey) {
        $arr = $this->keys($sKey);
        return $this->redis->mget($arr);
    }

    /**
     * 获取key
     * @param $sKey
     * @return array|void
     */
    public function keys($sKey) {
        $sKey = $sKey . ':*';
        return $this->redis->keys($sKey);
    }

    /**
     * 删除键值
     * @param array|string $key
     */
    public function del($key) {
        if(is_array($key) || is_string($key)) {
            $this->redis->del($key);  //(string|arr)删除key，支持数组批量删除【返回删除个数】
        }
    }

    /**
     * 清空整个redis
     */
    public function flushAll() {
        $this->redis->flushAll();
    }

    /**
     * 清空当前redis库
     */
    public function flushDB() {
        $this->redis->flushDB();
    }

    /**
     * 获取keys
     * @param string | array $params
     * @return string
     */
    public function prefix($key, $params = '') {
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

    /**
     * 析构函数：销毁redis链接
     */
    public function __destruct () {
        $this->redis->close();
        $this->redis = NULL;
    }
}
