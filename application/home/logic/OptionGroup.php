<?php
namespace app\home\logic;

class OptionGroup
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
            case '1':
                //行程信息添加
                $output = $this->routeInfo();
                break;
            case '2':
                //产品特色添加
                $output = $this->sellingPoint();
                break;
            case '3':
                //自费项目添加
                $output = $this->chargedItem();
                break;
            case '4':
                //费用包含添加
                $output = $this->includeCost();
                break;
            case '5':
                //费用不包含添加
                $output = $this->notInCost();
                break;
            case '6':
                //特殊人群限制添加
                $output = $this->specialPeople();
                break;
            case '7':
                //预定须知添加
                $output = $this->advanceKnow();
                break;
            case '11':
                //价格库存
                $output = $this->ratesInventory();
                break;
            default:
                $output = json_encode(array("code" => 404,"msg" => "参数错误"));
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

    //行程信息 1
    public function routeInfo()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "查询商品号不能为空");
        }
        $address = db('goods_group')->field('begin_address,main_place')->where(array('goods_code' => $goodsCode))->find();
        if(!$address){
            return array("code" => 405,"msg" => "查询错误,请刷新页面");
        }
        $address["main_place"] = json_decode($address["main_place"]);
        return array("code" => 200,"data" => $address);

    }

    //产品特色 2
    public function sellingPoint()
    {
        return "sellingPoint";
    }

    //自费项目 3
    public function chargedItem()
    {
        return "chargedItem";
    }

    //费用包含 4
    public function includeCost()
    {
//        $goodsCode = input('post.goodsCode');
//        if(empty($goodsCode)){
//            return array("code" => 404,"msg" => "查询商品号不能为空");
//        }
//        $address = db('goods_group')->field('main_place')->where(array('goods_code' => $goodsCode))->find();
//        if(!$address){
//            return array("code" => 405,"msg" => "查询产品不存在，请联系管理员");
//        }
//        $output = json_decode($address['main_place']);
////        $address = json_decode($address["main_place"],true);
////        foreach ($address as $k){
////            $array = array("bol" => 0 ,"name" => $k["place"]);
////            $output[] = $array;
////        }
////        $output = $address["tickBox"];
//        return array("code" => 200,"data" => $output);
        return "includeCost";
    }

    //费用不包含 5
    public function notInCost()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "查询商品号不能为空");
        }
        $address = db('goods_group')->field('little_traffic')->where(array('goods_code' => $goodsCode))->find();
        if(!$address){
            return array("code" => 405,"msg" => "查询产品不存在，请联系管理员");
        }
        return array("code" => 200,"data" => $address);

    }

    //特殊人群限制 6
    public function specialPeople()
    {
        return "specialPeople";
    }

    //预定须知 7
    public function advanceKnow()
    {
        return "advanceKnow";
    }

    //价格库存 11
    public function ratesInventory(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 412,"msg" => "商品号不能为空");
        }
        $data = db('goods_group')->field('child_price_type')->where(array('goods_code' => $goodsCode))->find();
        if(!$data){
            return array("code" => 405,"msg" => "查询产品不存在，请联系管理员");
        }
        return array("code" => 200,"data" => $data);
    }
}