<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Login extends WeixinBase
{
    public function index(){

    }

    public function userLogin(){
        $res = array("code" => "100001","type" => 1 ,"isType" => 1);//用户
//        $res = array("code" => "54","type" => 3 ,"isType" => 2);
        cookie("user" ,$res , 604800);
        return json(array("code" => 200,"data" => cookie("user")));
    }

    //检查登录
    public function checkLogin(){
        if(empty(cookie("user"))){
            return json(array("code" => 403,"msg" => "你的登录状态已经失效，请重新登录"));
        }
        return json(array("code" => 200,"data" => cookie("user")));
    }

    //测试使用
    public function switchRoles(){
        $user = cookie("user");
        if(empty($user)){
            $res = array("code" => "100001","type" => 1 ,"isType" => 1);//用户
            cookie("user" ,$res , 3600);
            return "设置 10001 用户成功";
        }else{
            if($user["isType"] == 1){
                cookie(null, 'user');
                $res = array("code" => "54","type" => 3 ,"isType" => 2);//经销商
                cookie("user" ,$res , 3600);
                return "设置 54 返佣型经销商成功";
            }else{
                cookie(null, 'user');
                $res = array("code" => "100001","type" => 1 ,"isType" => 1);//用户
                cookie("user" ,$res , 3600);
                return "设置 10001 用户成功";
            }
        }
    }

    //注销
    public function logout(){
        cookie(null, 'user');
    }


}
