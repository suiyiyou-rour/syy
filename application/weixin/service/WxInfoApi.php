<?php
namespace app\weixin\service;
class WxInfoApi {
    private $APP_ID;
    private $APP_SECRET;

    public function __construct(){
        $this->APP_ID       = config("app_id");
        $this->APP_SECRET   = config("app_secret");
    }

    //code获取openid
    public function getOpenid($code){
        $appId      =   $this->APP_ID;
        $appSecret  =   $this->APP_SECRET;
        $url        =   "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $appSecret . "&code=" . $code . "&grant_type=authorization_code";
        $content    =   file_get_contents($url);
        $res        =   json_decode($content, true);
        if (!isset($res['openid'])) {
            return false;
        }
        return $res['openid'];
    }

    //获取用户头像
    public function getHeadImg($openId){
        header("Content-Type:text/html;charset=utf-8");
        $token   =  getAccessToken();
        $url     =  "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openId&lang=zh_CN";
        $content =  file_get_contents($url);
        $res     =  json_decode($content,true);
        return $res['headimgurl'];
    }

    //直接获取openid
    public function openId($appId, $appSecret, $sure = '') {
        $type = empty($sure) ? 'snsapi_base' : 'snsapi_userinfo';
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        // get code
        if (!isset($_GET['code'])) {
            $redirect = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $type . '&state='.time().'#wechat_redirect';
            header('Location: ' . $redirect);
            exit;
        }

        // 判断是否CODE失效，刷新时间设置10秒
        /* 微信code只能用一次，此判断解决刷新页面出现code错误的问题，这里是根据state做判断的，所以上边的授权链接这个参数必须写成时间戳*/
        if(( $_GET['state'] + 10) < time()){
            $url = str_replace('&code=', '&oldcode=', $url);
            $url = str_replace('&state=', '&oldstate=', $url);
            header('Location: ' . $url);
        }

        // get openid
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $appSecret . "&code=" . $_GET['code'] . "&grant_type=authorization_code";
        $content = file_get_contents($url);
        $ret = json_decode($content, true);

        if (!isset($ret['openid'])) {
            echo 'get openID is fail';
            exit;
        }
        return $ret['openid'];
    }

    //获取微信临时图片
    public function getMediaImg($MEDIA_ID){
        $urlname  = "license/" .date("Ymd");    //数据库文件路径
        $dir_name = "image/".$urlname;           //实际文件路径
        //创建文件夹
        $dir = iconv("UTF-8", "GBK", $dir_name);
        if (!file_exists($dir)){//日期文件夹
            mkdir ($dir,0777,true);
        }

        $token = getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$MEDIA_ID;
        $arr = $this->downloadWeixinFile($url);

        $random  = md5(time().rand(1,100)).".jpg";
        $res = $this->saveWeixinFile($dir_name."/".$random , $arr['body']);
        if(!$res){
           return false;
        }
        return $urlname."/".$random;
    }

    //获取临时素材curl
    public function downloadWeixinFile($url)
    {
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

    //保存微信临时文件
    public function saveWeixinFile($filename, $filecontent)
    {
        $local_file = fopen($filename, 'w');
        if (false !== $local_file){
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
                return true;
            }
        }
        return false;
    }
}
