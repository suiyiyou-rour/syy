<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Login extends WeixinBase
{
    public function index(){
        var_dump(cookie("user"));

    }

    //发送短信验证码
    public function useSendSms()
    {
        $mobile = input('mobile');

        // 手机号码验证
        if(!is_phone($mobile)){
            return json(array("code" => 405,"msg" => "手机号码不正确"));
        }

        $isUser = db('user')->where(array('account' => $mobile))->find();
        if($isUser){
            return json(array("code" => 405,"msg" => "该手机号已经是用户了"));
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
        $arr =  array(
            'accessKeyId' =>config('aliyun')['sms_accessKeyId'],
            'accessKeySecret' => config('aliyun')['sms_accessKeySecret']
        );
        $smsObj = new \Aliyun\Sms($arr);

        $code = rand(100000, 999999);
        $params["PhoneNumbers"] =  $mobile;
        $params["SignName"]     = "随意游网络";
        $params["TemplateCode"] = "SMS_109705432";
        $params['TemplateParam'] = Array (
            "code" => $code
        );

        $result = $smsObj->sendVerify($params);

        if ($result) {
            $data['code']   =  $code;
            $data['mobile'] =  $mobile;
            $data['time']   =  time();
            $data['num']   =  $requestNum + 1;
            if($isSend){
                db('sms_code') -> where(array('mobile' => $mobile))->update($data);
            }else{
                db('sms_code') ->insert($data);
            }
            return json(array('code' => 200, 'msg' => '发送成功'));
        } else {
            return json(array('code' => 403, 'msg' => '发送失败'));
        }
    }

    //普通用户注册
    public function register(){
        $mobile = input('mobile');
        $pwd    = input('password');
        $verify = input('verify');
        $wxcode = input('wxcode');

        if(!$mobile || !$pwd || !$verify){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }
        if(!$wxcode){
            return json(array("code" => 404,"msg" => "微信授权识别参数错误"));
        }

        $pwd = md5($pwd);

        // 手机号码 不合格
        if(!is_phone($mobile)){
            return json(array("code" => 405,"msg" => "手机号码不正确"));
        }

        // 手机号 已注册
        $user = db('user')->field("id")->where(array('account' => $mobile))->find();
        if($user){
            return json(array("code" => 405,"msg" => "该手机号码已被注册"));
        }

        // 验证码 失效
        $isSend = db('sms_code')->where(array('mobile' => $mobile))->find();
        if($isSend['time'] + 60 * 15 < time()){
            return json(array("code" => 405,"msg" => "验证码已失效，请重新发送。"));
        }

        // 验证码 不正确
        if($isSend['code'] != $verify){
            return json(array("code" => 405,"msg" => "验证码错误！"));
        }

        //获取用户信息
        $openId = $this->getOpenid($wxcode);
        if($openId === false){
            return json(array("code" => 404,"msg" => "微信授权错误,请联系管理员"));
        }
        $head_img = $this->getHeadImg($openId);

        $data["code"]           =  $this->creatUserCode();
        $data["account"]        =  $mobile;
        $data["pwd"]            =  $pwd;
        $data["wx_code"]        =  $openId;
        $data["head_img"]       =  $head_img;
        $data["regist_time"]   =  time();
        $data["type"]           =  1;

        try{
            db('user')->insert($data);
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "注册失败，请联系管理员"));
        }
        $output["code"]       =  $data["code"];
        $output["type"]       =  1;
        $output["isType"]     =  1;
        $output["head_img"]   =  $data["head_img"];

        cookie("user" ,$output , 315360000);
        return json(array("code" => 200,"data" => $output, "msg" => "注册成功"));
    }

    //普通用户登陆
    public function userLogin(){
        $mobile = input('mobile');
        $pwd    = input('password');
        $type   = input("type");
        if(!$mobile || !$pwd || !$type){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }
        $pwd = md5($pwd);

        if($type == 1){
            $user = db('user')->where(array('account' => $mobile,"pwd" => $pwd))->find();
            if(!$user) return json(array("code" => 403,"msg" => "账号或者密码错误"));

            $arr = array("code" => $user["code"],"type" => $user["type"] , "head_img" => $user["head_img"] ,"isType" => 1);  //用户
        }else{
            $retail = db('retail')->where(array('account_num' => $mobile,"pwd" => $pwd))->find();
            if(!$retail) return json(array("code" => 403,"msg" => "账号或者密码错误"));
            if($retail["open"] == 0) return json(array("code" => 403,"msg" => "账户已经被关闭"));
            if($retail["check"] !== 1) return json(array("code" => 403,"msg" => "账户审核状态没有通过"));

            $arr = array("code" => $retail["code"],"type" => $retail["type"] ,"head_img" => $retail["head_img"], "isType" => 2);  //经销商
        }
        cookie("user" ,$arr , 315360000);
        return json(array("code" => 200,"data" => $arr,"msg" => "登陆成功"));
    }

    //获取用户code
    private function creatUserCode(){
        $id = db('user')->order("id desc")->value('id');
        $id += 10001;
        return $id;
    }

    //获取jssdk
    public function jssdk(){
        $wxService      =   \think\Loader::model('WxApi','service');;
        $jssdk          =   $wxService->JSSDK();
        return json(array("code" => 200 , "data" => $jssdk));
    }

    //经销商注册
    public function jxsRegister(){

//        `code` varchar(16) NOT NULL COMMENT '商户标识',
//  `type` int(1) NOT NULL COMMENT '用户类型  1经销商 2经销商员工 3返利经销商',
//  `assgin_account` int(11) DEFAULT '0' COMMENT '上级供应商 经销商id',
//  `account_num` varchar(20) NOT NULL COMMENT '商户账号',
//  `pwd` varchar(64) NOT NULL COMMENT '密码',
//  `com_name` varchar(32) DEFAULT NULL COMMENT '公司名称',
//  `province` varchar(8) DEFAULT NULL COMMENT '公司所在地省',
//  `city` varchar(8) DEFAULT NULL COMMENT '公司所在城市',
//  `address` varchar(50) DEFAULT NULL COMMENT '公司地址',
//  `name` varchar(32) DEFAULT NULL COMMENT '联系人姓名',
//  `mobile` varchar(11) DEFAULT NULL COMMENT '手机号',
//  `qq` varchar(12) DEFAULT NULL COMMENT 'qq号',
//  `email` varchar(32) DEFAULT NULL COMMENT '邮箱',
//  `file` text COMMENT '营业执照',
//  `image` text,
//  `wx_code` varchar(128) DEFAULT NULL,
//  `check` int(1) DEFAULT '0' COMMENT '审核中 1通过 0未通过 2驳回',
//  `open` int(1) DEFAULT '1' COMMENT '是否开启1 开启 0 关闭',
//  `reg_time` int(11) unsigned DEFAULT NULL COMMENT '注册时间',
    }



    public function rootUserLogin(){
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

    //获取用户头像
    private function getHeadImg($openId){
        header("Content-Type:text/html;charset=utf-8");
        $obj = \think\Loader::model('WxApi','service');
        $token = $obj->getAccessToken();
        $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openId&lang=zh_CN";
        $content = file_get_contents($url);
        $content = json_decode($content,true);
        return $content['headimgurl'];
    }

    //获取openid
    private function getOpenid($code){
        $appId      =   config("app_id");
        $appSecret  =   config("app_secret");
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appId . "&secret=" . $appSecret . "&code=" . $code . "&grant_type=authorization_code";
        $content = file_get_contents($url);
        $ret = json_decode($content, true);

        if (!isset($ret['openid'])) {
            return false;
        }
        return $ret['openid'];
    }

}
