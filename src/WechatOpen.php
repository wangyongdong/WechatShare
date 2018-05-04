<?php
namespace WechatOpen;
use RedisManager;

class WechatOpen {
    private static $appId;
    private static $appSecret;
    private static $redis_config;

    /**
     * 获取分享凭证
     * @param $appId
     * @param $appSecret
     * @return array
     */
    public static function getShare($appId, $appSecret, $config = array()) {
        self::$appId = $appId;
        self::$appSecret = $appSecret;
        self::$redis_config = $config;

        return self::getSignPackage();
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

    //分享
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
        return json_encode($signPackage);
    }

    //票据
    private static function getJsApiTicket() {
        return RedisManager\RedisManager::load('jsapi_ticket',array(self::$appId, self::$appSecret), function() {
            $accessToken = WechatOpen::getAccessToken();
            // 如果是企业号用以下 URL 获取 ticket
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=" . $accessToken;
            $res = WechatOpen::httpSend($url);
            if ($res['ticket']) {
                return $res['ticket'];
            }
        }, self::$redis_config);
    }

    //accessToken
    public static function getAccessToken() {
        return RedisManager\RedisManager::load('access_token',array(self::$appId, self::$appSecret), function() {
            // 如果是企业号用以下URL获取access_token
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . self::$appId . "&secret=" . self::$appSecret . "";
            $res = WechatOpen::httpSend($url);
            if ($res['access_token']) {
                return $res['access_token'];
            }
        }, self::$redis_config);
    }

    public static function downloadWeixinFile($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header' => $httpinfo), array('body' => $package));
        return $imageAll;
    }

    public static function saveWeixinFile($filename, $filecontent) {
        $local_file = fopen($filename, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
            }
        }
    }

    private static function httpSend($url, $params = array(), $method = 'get') {
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

    private static function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
