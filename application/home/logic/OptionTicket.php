<?php
namespace app\home\logic;

class OptionTicket
{
    /**
     * 状态分发
     */
    public function dispatcher($state){
        switch ($state) {
            case '0':
                //基本信息添加
                return $this->basicInfo();
            default:
                return json_encode(array("code" => 404,"msg" => "参数错误"));
        }
    }

    //基本信息 0
    public function basicInfo()
    {
        $contact = db('contact')->field('code,name,rate')->where(array('sp_code' => '1234567'))->select();
        if(!$contact){
            return json_encode(array("code" => 405,"msg" => "合同加载错误,请联系管理员"));
        }
        $data["contact"] = $contact;
        $data["hash"] = getFromHash();
        return json_encode(array("code" => 200,"data" => $data));
    }


}