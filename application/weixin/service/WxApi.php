<?php
namespace app\weixin\service;
class WxApi {
    private $APP_ID;
    private $APP_SECRET;

    public function __construct(){
        $this->APP_ID       = config("app_id");
        $this->APP_SECRET   = config("app_secret");
    }

    public function index(){
        echo $this->APP_ID;
        echo "<br/>";
        echo $this->APP_SECRET;
    }

    /**
     * 获取AccessToken
     */
    public function getAccessToken(){
        $result = cache("WxAccessToken");
        if($result){
            return $result;
        }

        $url      = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->APP_ID.'&secret='.$this->APP_SECRET;
        $acToken  = $this->curl_get_contents($url);
        $eq       = json_decode($acToken,true);
        if($eq["access_token"] && $eq["expires_in"]){
            cache('WXAccessToken', $eq["access_token"], 7150);
            return $eq["access_token"];
        }
        return false;
    }

    /**
     * 获取api_ticket
     */
    public function get_jsapi_ticket(){
        $result = cache("WxJsapiTicket");
        if($result){
            return $result;
        }

        $AccessToken = $this->getAccessToken();
        $url  = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$AccessToken";
        $jdk  = $this->curl_get_contents($url);
        $eq   = json_decode($jdk,true);
        if($eq["ticket"] && $eq["expires_in"]){
            cache('WXJsapiTicket', $eq["ticket"], 7150);
            return $eq["ticket"];
        }else{
            return false;
        }

    }

    /**
     * JSSDK 排序加密
     */
    public function JSSDK(){
        $jsapiTicket = $this->get_jsapi_ticket();

        // 注意 URL 一定要动态获取，不能 hardcode.
//        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url = "http://wx.suiyiyou.net";

        $timestamp = time();
        $nonceStr = $this->getRandStr(16);

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
//            "debug"     => true,
            "appId"     => $this->APP_ID,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
//            "url"       => $url,
            "signature" => $signature
//            "rawString" => $string
        );
        return json_encode($signPackage);

    }

    /**
     * curl get
     */
    public function curl_get_contents($url){
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
//        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
//        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
//        curl_setopt($curl, CURLOPT_URL, $url);
//
//        $res = curl_exec($curl);
//        curl_close($curl);
//        return $res;


        $curl = curl_init();
        curl_setopt ($curl, CURLOPT_URL, $url);
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $re = curl_exec($curl);
        curl_close($curl);
        return $re;

//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
//        curl_setopt($ch, CURLOPT_MAXREDIRS, 200);
//        curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
//        curl_setopt($ch, CURLOPT_REFERER, _REFERER_);
//        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        $r = curl_exec($ch);
//        curl_close($ch);
//        return $r;
    }

    /**
     * 随机字符串
     */
    function getRandStr($length){
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randString = '';
        $len = strlen($str)-1;
        for($i = 0;$i < $length;$i ++){
            $num = mt_rand(0, $len);
            $randString .= $str[$num];
        }
        return $randString;
    }
}
