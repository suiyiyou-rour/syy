<?php
namespace app\home\controller;
// 发送短信
class Sms
{
    // 短信类
    private $sms = '';
    public function __construct(){
        $arr =  array(
            'accessKeyId' =>config('aliyun')['sms_accessKeyId'],
            'accessKeySecret' => config('aliyun')['sms_accessKeySecret']
        );
        $this->sms = new \Aliyun\Sms($arr);
    }

    public function test(){
       $url = $_SERVER['HTTP_HOST'];
       echo $url;
    }

    //获取短信验证码 
    public function useSendSms()
    {
        $mobile = input('mobile');
        //手机号码验证
        if(!is_phone($mobile)){
            return json(array('code' => 404, 'msg' => '手机号码不正确'));
        }
        $isSend = db('sms_code')->where(array('mobile' => $mobile))->find();

        // 不为空的情况
        //     先判断次数是否超过
        //         未超过 -- 再判断时间是否超过
        if(!empty($isSend)){
            if ((int)$isSend['num'] > 3) {
                return json(array('code' => 404, 'msg' => '当日次数已达上限'));
            }elseif($isSend['time'] + 60 > time()){
                return json(array('code' => 404, 'msg' => '不能重复发送'));
            }
            $requestNum = (int)$isSend['num'];
        }else{
            $requestNum = 0;
        }                                                                                                          

        // 配置阿里云短信 -- 模板参数
        $code = $this->generate_code();

        $params["PhoneNumbers"] =  $mobile;
        $params["SignName"] = "随意游网络";
        $params["TemplateCode"] = "SMS_109705432";
        $params['TemplateParam'] = Array (
            "code" => $code
        );
        
        $result = $this->sms->sendVerify($params);

        if ($result) {
            $data['code']   =  $code;
            $data['mobile'] =  $mobile;
            $data['time']   =  time();
            $data['num']   =  $requestNum + 1;
            db('sms_code')     -> where(array('mobile' => $mobile))->delete();
            $res = db('sms_code')->insert($data);
            return json(array('code' => 200, 'msg' => '发送成功'));
        } else {
            return json(array('code' => 404, 'msg' => '发送失败'));
        }
    }

    
    /** 用户购买发送给供应商 短信验证码 
     *  @param mobile：供应商手机号
     *  @param productName：产品名
     *  @param userName：用户名
     *  @param time：下单时间
     */ 
    public function SmsTo($mobile,$productName,$userName,$time)
    {                                                                                             
        // 参数校验
        if(!$mobile || !$productName || !$userName || !$time){
            return 0;
        }

        // 配置阿里云短信 -- 模板参数
        $params["PhoneNumbers"] =  $mobile;
        $params["SignName"] = "随意游网络";
        $params["TemplateCode"] = "SMS_112470379";
        $params['TemplateParam'] = Array (
            "productName" => $productName,
            "userName" => $userName,
            "time" => $time,
        );

        $result = $this->sms->sendVerify($params);
        if($result){
            return 1;
        }else{
            return 2;
        }
    }

    // 随机生成6位数验证码 pow() -> 求幂
    public function generate_code($length = 6)
    {
        return rand(100000, 999999);
    }

}