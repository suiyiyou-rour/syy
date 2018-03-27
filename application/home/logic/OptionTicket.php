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
                $output = $this->basicInfo();
                break;
            default:
                $output = array("code" => 404,"msg" => "参数错误");
        }
        return $output;
    }

    //基本信息 0
    public function basicInfo()
    {
        $sp_code = getSpCode();
        $contact = db('contact')->field('code,name,rate')->where(array('sp_code' => $sp_code))->select();
        if(!$contact){
            return array("code" => 405,"msg" => "合同加载错误,请联系管理员");
        }
        $data["contact"] = $contact;
        $data["hash"] = getFromHash();
        return array("code" => 200,"data" => $data);
    }


}