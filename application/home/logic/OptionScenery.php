<?php
namespace app\home\logic;

class OptionScenery
{
    /**
     * 状态分发
     */
    public function dispatcher($state)
    {
        //需要商品code
        $goodsCode = input('post.goodsCode');
        switch ($state) {
            case '0':
                //基本信息添加
                $output = $this->basicInfo();
                break;
            case '1':
                //打包内容
                $output = $this->packDetails();
                break;
            case '2':
                //套餐信息
                $output = $this->packageInfo();
                break;
            case '3':
                //价格库存
                $output = $this->ratesInventory();
                break;
            case '4':
                //商品设置
                $output = $this->productSet();
                break;
            case '5':
                //商品信息
                $output = $this->productInfo();
                break;
            default:
                $output = array("code" => 404, "msg" => "参数错误");
        }
        return $output;
    }

    //基本信息添加 0
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


    //打包内容 1
    public function packDetails(){
        $sp_code = getSpCode();//供应商code
        $data["hotel"] = db('hotel')->field("code,name")->where(array("sp_code"=>$sp_code))->select();
        if(empty($data["hotel"])){
            $data["hotel"] = array();
        }
        $data["view"] = db('view')->field("code,name")->where(array("sp_code"=>$sp_code))->select();
        if(empty($data["view"])){
            $data["view"] = array();
        }
        $data["meal"] = db('meal')->field("code,name")->where(array("sp_code"=>$sp_code))->select();
        if(empty($data["meal"])){
            $data["meal"] = array();
        }
        $data["vehicle"] = db('vehicle')->field("code,name")->where(array("sp_code"=>$sp_code))->select();
        if(empty($data["vehicle"])){
            $data["vehicle"] = array();
        }
        return array("code" => 200,"data" => $data);
    }

    // 套餐信息 2
    public function packageInfo(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json_encode(array("code" => 404,"msg" => "查询商品号不能为空"));
        }
        $output = db('goods_scenery')->field('hotel_code')->where(array("goods_code"=> $goodsCode))->find();
        if(empty($output)){
            return array("code" => 404,"msg" => "查询错误");
        }
        $output["hotel_code"] = json_decode($output["hotel_code"],true);
        return array("code" => 200,"data" => $output);
    }

    //价格库存 3
    public function ratesInventory(){
        return "ratesInventory";
    }

    //商品设置 4
    public function productSet(){
        return "productSet";
    }

    //商品信息 5
    public function productInfo(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "查询商品号不能为空");
        }
        $output = db('goods_scenery')->field('hotel_code,hotel_day,view_code,meal_code')->where(array("goods_code"=> $goodsCode))->find();
        if(empty($output)){
            return array("code" => 404,"msg" => "查询错误");
        }
        $output["hotel_code"] = json_decode($output["hotel_code"],true);
        $output["view_code"] = json_decode($output["view_code"],true);
        $output["meal_code"] = json_decode($output["meal_code"],true);
        return array("code" => 200,"data" => $output);
    }


}