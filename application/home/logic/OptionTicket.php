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
        if(!getSpType()) $where["sp_code"] = getSpCode(); //供应商code
        $where["is_del"] = ['<>', "1"];  //未删除

        $contract = db('contract')->field('code,name,rate')->where($where)->select();
        if(!$contract){
            return array("code" => 405,"msg" => "合同加载错误,请联系管理员");
        }
        $data["contact"] = $contract;
        $data["hash"] = getFromHash();
        return array("code" => 200,"data" => $data);
    }


}