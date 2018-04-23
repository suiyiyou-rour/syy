<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Login extends WeixinBase
{
    public function index(){

    }

    //检查登录
    public function checkLogin(){
        if(empty(cookie("user"))){
            return json(array("code" => 403,"msg" => "你的登录状态已经失效，请重新登录"));
        }
        return json(array("code" => 200,"data" => cookie("user")));
    }



}
