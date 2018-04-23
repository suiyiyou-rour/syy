<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
//需要登录的 继承类 不需要直接继承 WeixinBase
class LoginBase extends WeixinBase
{
    public function __construct()
    {
        parent::__construct();
        if(empty(cookie("user"))){
            echo json_encode(array("code" => 499,"msg" => "你的登录状态已经失效，请重新登录"));
            die;
        }
    }



}
