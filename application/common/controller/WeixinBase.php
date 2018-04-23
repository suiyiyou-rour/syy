<?php
namespace app\common\controller;
/**
 * weixin模块基类
 */
class WeixinBase extends Base
{
    public function __construct()
    {
        parent::__construct();
        $origin = isset($_SERVER['HTTP_ORIGIN'])? $_SERVER['HTTP_ORIGIN'] : '';
        $allow_origin = array(
            'http://wx.suiyiyou.net',
            'http://localhost:8080'
        );
        if(in_array($origin, $allow_origin)){
            header('Access-Control-Allow-Origin:'.$origin);                          // 指定允许其他域名访问
            header("Access-Control-Allow-Methods", "GET,POST");                     // 响应类型
            header('Access-Control-Allow-Headers:x-requested-with,content-type'); // 响应头设置
            header('Access-Control-Allow-Credentials:true');                        // 是否允许请求带有验证信息
        }
        $res = array("code" => "100001","type" => 1 ,"isType" => 1);//用户
//        $res = array("code" => "54","type" => 3 ,"isType" => 2);
        cookie("user" ,$res , 604800);
    }
}
