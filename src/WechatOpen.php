<?php
namespace WechatShare;
use RedisManager;

/**
 * 获取微信分享信息
 * Class WechatOpen
 * @package WechatShare
 * Created on 2018/5/10 17:09
 * Created by wangyongdong
 */
class WechatOpen {
    /*
     * @var string
     */
    private static $appId;

    /*
     * @var string
     */
    private static $appSecret;

    /*
     * @var array
     */
    private static $redis_config;

    /**
     * @var int
     */
    private static $expire = 7000; //有效时间是7200秒

    /**
     * 获取分享凭证
     * @param $appId
     * @param $appSecret
     * @param array $array
     * @param array $config
     * @return array
     */
    public static function getShare($appId, $appSecret, $aDefault = array(), $config = array()) {
        self::$appId = $appId;
        self::$appSecret = $appSecret;
        self::$redis_config = $config;

        $aSignPackage = self::getSignPackage();
        return !empty($aDefault) ? array_merge($aSignPackage, $aDefault) : $aSignPackage;
    }

    /**
     * 获取用户基本信息
     * @param $openid
     * @param $access_token
     * @return bool|mixed|string
     */
    public static function getUserInfo($openid, $access_token) {
        if(empty($openid) || empty($access_token)) {
            return false;
        }
        $url= 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $access_token . '&openid=' . $openid . '&lang=zh_CN';
        return self::httpSend($url);
    }

    /**
     * @return mixed|string|void
     */
    private static function getSignPackage() {
        $jsapiTicket = self::getJsApiTicket();
        // 注意 URL 一定要动态获取，不能 hardcode.
        $sProtocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = $sProtocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $timestamp = time();
        $nonceStr = self::createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        //$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $arr = array('jsapi_ticket' => $jsapiTicket, 'noncestr' => $nonceStr, 'timestamp' => $timestamp, 'url' => $url);
        ksort($arr);
        $string = '';
        foreach ($arr as $k => $v){
            $string .= $string ? "&" .$k ."=" .$v : $k ."=" .$v;
        }

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => self::$appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 票据
     * @return bool|mixed
     */
    private static function getJsApiTicket() {
        $appid = WechatOpen::$appId;
        $appSecret = WechatOpen::$appSecret;
        $redis = RedisManager\RedisManager::getInstance();
        return $redis->load('wxshare_jsapi_ticket',array($appid, $appSecret), function() {
            $accessToken = \WechatShare\WechatOpen::getAccessToken();

            // 如果是企业号用以下 URL 获取 ticket
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=" . $accessToken;
            $res = \WechatShare\WechatOpen::httpSend($url);
            if (!empty($res['ticket'])) {
                return $res['ticket'];
            }
        }, self::$expire);
    }

    /**
     * @return bool|mixed
     */
    public static function getAccessToken() {
        $appid = WechatOpen::$appId;
        $appSecret = WechatOpen::$appSecret;

        $redis = RedisManager\RedisManager::getInstance();
        return $redis->load('wxshare_access_token', array($appid, $appSecret), function() use ($appid, $appSecret) {
            // 如果是企业号用以下URL获取access_token
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appSecret;
            $res = \WechatShare\WechatOpen::httpSend($url);
            if (!empty($res['access_token'])) {
                return $res['access_token'];
            }
        }, self::$expire);
    }

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @return array|mixed|object
     */
    public static function httpSend($url, $params = array(), $method = 'get') {
        $ch = curl_init();
        if($method == 'get') {
            $sParm = http_build_query($params);
            if(!empty($sParm)) {
                $url = $url.'?'.$sParm;
            }
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            if(is_array($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        try {
            $response = curl_exec($ch);
            if(!$response) {
                $response = json_encode(array());
            }
            curl_close($ch);
        } catch (Exception $e) {
            $response = json_encode(array());
        }

        return json_decode($response, true);
    }

    /**
     * @param int $length
     * @return string
     */
    private static function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
