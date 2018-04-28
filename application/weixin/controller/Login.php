<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Login extends WeixinBase
{
    public function index(){
        $mobile = "18060481803";
        $code = 123456;
        $arr =  array(
            'accessKeyId' =>config('aliyun')['sms_accessKeyId'],
            'accessKeySecret' => config('aliyun')['sms_accessKeySecret']
        );
        $smsObj = new \Aliyun\Sms($arr);
        // 配置阿里云短信 -- 模板参数


        $params["PhoneNumbers"] =  $mobile;
        $params["SignName"] = "随意游网络";
        $params["TemplateCode"] = "SMS_109705432";
        $params['TemplateParam'] = Array (
            "code" => $code
        );

        $result = $smsObj->sendVerify($params);
        var_dump($result);
    }

    //注册
    public function register(){
        $mobile = input('mobile');
        $pwd    = input('password');
        $verify = input('verify');

        if($mobile || $pwd || $verify){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }
        $pwd = md5($pwd);

        // 手机号码 不合格
        if(!is_phone($mobile)){
            return json(array("code" => 405,"msg" => "手机号码不正确"));
        }

        // 手机号 已注册
        $res = db('user')->where(array('account' => $mobile))->find();
        if($res){
            return json(array("code" => 405,"msg" => "该手机号码已被注册"));
        }

        // 验证码 失效
        $isSend = M('sms_code')->where(array('mobile' => $mobile))->find();
        if($isSend['time'] + 60 * 15 < time()){
            return json(array("code" => 405,"msg" => "验证码已失效，请重新发送。"));
        }

        // 验证码 不正确
        if($isSend['code'] != $verify){
            return json(array("code" => 405,"msg" => "验证码错误！"));
        }
    }

    //发送短信验证码
    //获取短信验证码
    public function useSendSms()
    {
        $mobile = I('mobile');

        // 手机号码验证
        if(!is_phone($mobile)){
            return json(array("code" => 405,"msg" => "手机号码不正确"));
        }

        $isSend = db('sms_code')->where(array('mobile' => $mobile))->find();
        if(!$isSend){
            $requestNum = 0;
        }else{
            if ($isSend['num'] > 5) {
                return json(array("code" => 405,"msg" => "发送次数上限"));
            }elseif($isSend['time'] + 60 > time()){
                return json(array("code" => 403,"msg" => "不能重复发送"));
            }
            $requestNum = $isSend['num'];
        }


        // 配置阿里云短信 -- 模板参数
        $code = rand(100000, 999999);

        $params["PhoneNumbers"] =  $mobile;
        $params["SignName"] = "随意游网络";
        $params["TemplateCode"] = "SMS_109705432";
        $params['TemplateParam'] = array (
            "code" => $code
        );

        $result = $this->sendVerify($params);

        if ($result) {
            $data['code']   =  $code;
            $data['mobile'] =  $mobile;
            $data['time']   =  time();
            $data['num']   =  $requestNum + 1;
            db('sms_code') -> where(array('c_mobile' => $mobile))->update($data);
            $this->ajaxReturn(array('code' => 200, 'msg' => '发送成功'));
        } else {
            $this->ajaxReturn(array('code' => 404, 'msg' => '发送失败'));
        }
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
