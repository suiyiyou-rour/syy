<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Login extends WeixinBase
{
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
        $obj = \think\Loader::model('WxInfoApi','service');
        $openId = $obj->getOpenid($wxcode);
        if($openId === false){
            return json(array("code" => 404,"msg" => "微信授权错误,请联系管理员"));
        }
        $head_img = $obj->getHeadImg($openId);

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

        $arr = array(
            "code"      => $data["code"],
            "type"      => 1 ,
            "head_img"  => $data["head_img"],
            "name"      => "",
            "mobile"    => $mobile,
            "isType"    => 1
        );
//        $output["code"]       =  $data["code"];
//        $output["type"]       =  1;
//        $output["isType"]     =  1;
//        $output["head_img"]   =  $data["head_img"];

        cookie("user" ,$arr , 315360000);
        return json(array("code" => 200,"data" => $arr, "msg" => "注册成功"));
    }

    //用户登陆
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
            //用户
            $arr = array(
                "code"      => $user["code"],
                "type"      => $user["type"] ,
                "head_img"  => $user["head_img"],
                "name"      => "",
                "mobile"    => $user["account"],
                "isType"    => 1
            );
        }else{
            $retail = db('retail')->where(array('account_num' => $mobile,"pwd" => $pwd))->find();
            if(!$retail) return json(array("code" => 403,"msg" => "账号或者密码错误"));
            if($retail["open"] == 0) return json(array("code" => 403,"msg" => "账户已经被关闭"));
            if($retail["check"] !== 1) return json(array("code" => 403,"msg" => "账户审核状态没有通过"));
            //经销商
            $arr = array(
                "code"      => $retail["code"],
                "type"      => $retail["type"] ,
                "head_img"  => $retail["head_img"],
                "name"      => $retail["com_name"],
                "mobile"    => $retail["mobile"],
                "isType"    => 2
             );
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

    //注销
    public function logout(){
        cookie(null, 'user');
        return json(array("code" => 200 ));
    }

    //获取用户 检查登录
    public function getUserInfo(){
        $arr = cookie("user");
        if($arr){
            return json(array("code" => 200,"data" => $arr));
        }
        return json(array("code" => 404));
    }

    //经销商注册
    public function jxsRegister(){
        if(empty(cookie("user"))){
            return json(array("code" => 403,"msg" => "你还没有登陆，不能成为分销商"));
        }
        $com_name   = input("post.com_name");
        $mobile     = input("post.mobile");
        $media_id   = input("post.wxurl");
        if(!$com_name || !$mobile || !$media_id){
            return json(array("code" => 404,"msg" => "参数不能为空"));
        }
        // 手机号码 不合格
        if(!is_phone($mobile)){
            return json(array("code" => 405,"msg" => "手机号码不正确"));
        }

        //获取用户信息
        $usercode = getUserCode();
        $userinfo = db("user")->field("account,pwd,wx_code,head_img")->where(array("code" => $usercode))->find();
        if(!$userinfo){
            return json(array("code" => 403,"msg" => "您的当前登录状态异常，请重新登录后，再次申请"));
        }

        //判断提交申请
        $checkRetail = db("retail")->field("check")->where(array("account_num" => $userinfo["account"]))->find();
        if($checkRetail){
            if($checkRetail["check"] == 0){
                return json(array("code" => 403,"msg" => "您的账号，已经提交过申请，请等待结果"));
            }else if($checkRetail["check"] == 1){
                return json(array("code" => 403,"msg" => "您的账号，已经是经销商"));
            }else{
                return json(array("code" => 403,"msg" => "您的账号，已经提交过申请，但是没有通过，请联系管理员"));
            }
        }

        //获取营业执照信息
        $obj = \think\Loader::model('WxInfoApi','service');
        $image = $obj->getMediaImg($media_id);
        if($image == false){
            return json(array("code" => 403,"msg" => "图片信息保存失败，请联系管理员"));
        }


        $data["account_num"]     =  $userinfo["account"];
        $data["pwd"]              =  $userinfo["pwd"];
        $data["type"]             =  1;
        $data["wx_code"]          =  $userinfo["wx_code"];
        $data["head_img"]         =  $userinfo["head_img"];
        $data["file"]             =  $image;                    //营业执照
        $data["code"]              = $this->creatJxsCode();
        $data["com_name"]          =  $com_name;                //
        $data["mobile"]            =  $mobile;                  //联系手机
        $data["reg_time"]   =  time();

        try{
            db('retail')->insert($data);
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "提交申请失败，请联系管理员"));
        }
        return json(array("code" => 200, "msg" => "提交申请成功"));
    }

    //获取经销商信息
    public function jxsInfo(){
        $code = getPid();
        $res = db('retail')->field("head_img,com_name,mobile")->where(array('code' => $code))->find();
        if(!$res){
            return json(array("code" => 404,"msg" => "经销商信息拉取失败，经销商请重新登录，游客请从正常链接进入"));
        }
        return json(array("code" => 200,"data" => $res));

    }

    //获取新用户code
    private function creatJxsCode(){
        $id = db('retail')->order("id desc")->value('id');
        $id += 10001;
        return $id;
    }


}
