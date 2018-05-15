<?php
namespace app\weixin\controller;
use app\common\controller\Base;
class Weixin extends Base {
    public function index(){
        return;
    }

    //微信接口
    public function listMessages(){
        if( $_SERVER["REQUEST_METHOD"] == "GET"){
            $echostr        =   $_GET["echostr"];
            $signature      =   $_GET["signature"];
            $timestamp      =   $_GET["timestamp"];
            $nonce          =   $_GET["nonce"];
            $token          =   config("token");
            if($this->checkSignature($signature,$timestamp,$nonce,$token)){
                echo  $echostr;
            }else{
                echo "";
            }
        }else if ($_SERVER["REQUEST_METHOD"] == "POST") {  //POST请求
            $postdata   =   $GLOBALS["HTTP_RAW_POST_DATA"];   //接受POST内
            libxml_disable_entity_loader(true);
            $postobj    =   simplexml_load_string($postdata, "SimpleXMLElement", LIBXML_NOCDATA);

            //    <xml>
// <ToUserName><![CDATA[toUser]]></ToUserName>
// <FromUserName><![CDATA[fromUser]]></FromUserName>
// <CreateTime>1348831860</CreateTime>
// <MsgType><![CDATA[image]]></MsgType>
// <PicUrl><![CDATA[this is a url]]></PicUrl>
// <MediaId><![CDATA[media_id]]></MediaId>
// <MsgId>1234567890123456</MsgId>
// </xml>
            switch ($postobj->MsgType) { //判断是什么消息类型
                case "text":        //自动回复消息
                    break;
                case "image":
                    break;
                case "event":       //如果是事件推送
                    switch ($postobj->Event) {  //判断是什么事件推送
                        case "subscribe":
                            break;
                        case "unsubscribe":
                            break;
                        case "CLICK":
                            if($postobj->EventKey == "BusinessCooperation"){
                                $clickStr = "<xml>       
                                                <ToUserName><![CDATA[{$postobj->FromUserName}]]></ToUserName>
                                                <FromUserName><![CDATA[{$postobj->ToUserName}]]></FromUserName>
                                                <CreateTime>{$postobj->CreateTime}</CreateTime>
                                                <MsgType><![CDATA[text]]></MsgType>
                                                <Content><![CDATA[随意游是福建领先的在线分销旅游平台公司。秉承着“让更多人享受旅游的乐趣”的理念，提供快速简便的旅游交易流程，依托其核心网络资源平台以及强大的传统营销渠道，为旅行社提供旅游最核心的库存价格，方便各旅游相关机构的销售。\n\n我们真诚期待与您合作！\n\n合作电话/微信：17759185562]]></Content>
                                            </xml>";
                                echo $clickStr;
                            }
                            break;
                        case "VIEW":
                            break;
                    }
                    break;
            }
        }
    }

    function checkSignature($signature,$timestamp,$nonce,$token)
    {
        $tmpArr     =   array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr     =   implode( $tmpArr );
        $tmpStr     =   sha1( $tmpStr );
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    //菜单更新
    public function menu(){//菜单配置
        $wxService      =   \think\Loader::model('WxApi','service');;
        $ACCESS_TOKEN   =   $wxService->getAccessToken();

        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$ACCESS_TOKEN}";
        $menu = '{
    "button":[
        {
            "name":"预订中心",
            "sub_button":[
                {
                    "type":"view",
                    "name":"商城首页",
                    "url":"http://wx.suiyiyou.net/#/home"
                },
                {
                    "type":"view",
                    "name":"跟团线路",
                    "url":"http://wx.suiyiyou.net/#/search/0"
                },
                {
                    "type":"view",
                    "name":"景区门票",
                    "url":"http://wx.suiyiyou.net/#/search/1"
                },
                {
                    "type":"view",
                    "name":"特惠酒店",
                    "url":"http://wx.suiyiyou.net/#/search/2"
                }
            ]
        },
        {
            "name":"随意游推荐",
            "sub_button":[
                {
                    "type":"view",
                    "name":"海报推广",
                    "url":"http://wx.suiyiyou.net/#/poster/0"
                }
            ]
        },
        {
            "name":"会员之家",
            "sub_button":[
                {
                    "type":"click",
                    "name":"商务合作",
                    "key":"BusinessCooperation"
                },
                {
                    "type":"view",
                    "name":"往期精选",
                    "url":"https://mp.weixin.qq.com/mp/homepage?__biz=MzI1ODkyMTg5MA==&hid=1&sn=d47aa8523ffc76184eaad89cd6c87165#wechat_redirect"
                }
            ]
        }
    ]
}';

//        var_dump($menu);
        $re =   $this->curl_post($url,$menu);
        var_dump($re);
    }

    public function code(){
        $code=I("code");
        echo $code;
    }


    function curl_post($url,$data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    //登陆
    function login(){
        header("Content-Type:text/html;charset=utf-8");
        $openId         =   $this->getOpenId(C("APP_ID"), C("APP_SECRET"), 1);
        $tokenclass     =   new \Org\Custom\AccessToken();
        $ACCESS_TOKEN   =   $tokenclass->gettoken();
        $url            =   "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$ACCESS_TOKEN&openid=$openId&lang=zh_CN";
        $content        =   file_get_contents($url);
        echo $content;
    }


    /**
     * 网页授权获取用户信息
     * @param type $appId
     * @param type $appSecret
     * @param type $sure 是否需要用户确认授权
     * @return type
     */
    function getOpenId($appId, $appSecret, $sure = '') {

        $type = empty($sure) ? 'snsapi_base' : 'snsapi_userinfo';
        $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        //get code
        if (!isset($_GET['code'])) {
            $redirect = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appId . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $type . '&state='.time().'#wechat_redirect';
            header('Location: ' . $redirect);
            exit;
        }

//        var_dump($_GET);
//        die;
        //判断是否CODE失效，刷新时间设置10秒
        /*微信code只能用一次，此判断解决刷新页面出现code错误的问题，这里是根据state做判断的，所以上边的授权链接这个参数必须写成时间戳*/
        if(( $_GET['state'] + 10) < time()){
            $url = str_replace('&code=', '&oldcode=', $url);
            $url = str_replace('&state=', '&oldstate=', $url);
            header('Location: ' . $url);
        }

        //get openid
        $url        = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $appSecret . "&code=" . $_GET['code'] . "&grant_type=authorization_code";
        $content    = file_get_contents($url);
        $ret        = json_decode($content, true);

        if (!isset($ret['openid'])) {
            echo 'get openID is fail';
            exit;
        }
        return $ret['openid'];
    }

}

