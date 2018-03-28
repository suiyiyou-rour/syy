<?php
namespace app\home\logic;

class ShowScenery
{
    /**
     * 状态分发
     */
    public function dispatcher($state)
    {
        //需要商品code
        $goodsCode = input('post.goodsCode');
        if ($state != '0' && $state != '10') {
            if (empty($goodsCode)) {
                return json_encode(array("code" => 412, "msg" => "显示商品，商品号不能为空"));
            }
        }
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
            case '10':
                $output = $this->showGoodsHead();
                break;
            default:
                $output = array("code" => 404, "msg" => "参数错误");
        }
        return $output;
    }

    //基本信息添加 0
    public function basicInfo()
    {
        $goodsCode = input('post.goodsCode');
        if($goodsCode){
            //有商品code 查询
            $goodsField = "a.contact_code,a.inside_code";
            $ticketField = "b.add_type,b.settlement_type";
            $allField = $goodsField.','.$ticketField;
            $join = [
                ['syy_goods_scenery b','a.code = b.goods_code']
            ];
            $where = [
                "a.code"         => $goodsCode,
                "a.goods_type"  =>  '3',
                "a.is_del"       =>  ["<>","1"]  //未删除
            ];
            $data = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
            if(!$data){
                return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
            }

            $data["state"] = '0';
            $data["tab"] = $this->getGoodsTab($goodsCode);
            $data["goodsCode"] = $goodsCode;
            return array("code" => 200,"data" => $data);
        }

        //没有商品code
        $join = [['syy_goods_create b','a.code = b.goods_code']];
        $where = [
            "a.check_type"  =>  "0",         //制作中
            "a.goods_type"  =>  "3",         //景酒
            "a.sp_code"     =>  getSpCode(),  //供应商code
            "a.is_del"      =>  ["<>","1"]   //未删除
        ];
        $goodsField = "a.code,a.show_title,a.inside_code";
        $createField = "b.tab";
        $allField = $goodsField.','.$createField;
        $res = db('goods')->alias("a")->field($allField)->where($where)->join($join)->order('a.last_edit_time desc')->select();
        //有 未填完信息
        if($res){
            foreach ($res as &$k){
                $k["tab"] = $k["tab"] + 1;
                if(empty($k["show_title"])){
                    $k["show_title"] = "未填写标题";
                }
            }
            return array("code" => 201,"data" => $res);
        }
        //没有 未填完信息
        return array("code" => 202);
    }

    //打包内容 1
    public function packDetails(){
        $goodsCode = input('post.goodsCode');
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 1){
            return array("code" => 203,"data" => array("tab"=>$tab));
        }
        $output = db('goods_scenery')->field('hotel_code,view_code,meal_code,vehicle_code')->where(array("goods_code"=> $goodsCode))->find();
        if(empty($output)){
            return array("code" => 404,"msg" => "查询错误");
        }
        $output["hotel_code"] = json_decode($output["hotel_code"],true);
        $output["view_code"] = json_decode($output["view_code"],true);
        $output["meal_code"] = json_decode($output["meal_code"],true);
        $output["vehicle_code"] = json_decode($output["vehicle_code"],true);
        $output["tab"] = $tab;
        $output["state"] = '1';
        $output["goodsCode"]    = $goodsCode;
        return array("code" => 200,"data" => $output);
    }

    // 套餐信息 2
    public function packageInfo(){
        $goodsCode = input('post.goodsCode');
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 2){
            return array("code" => 203,"data" => array("tab"=>$tab));
        }
        $output = db('goods_scenery')->field('hotel_day,apply_man_num,apply_child_num,show_market_price,trip_info')->where(array("goods_code"=> $goodsCode))->find();
        if(empty($output)){
            return array("code" => 404,"msg" => "查询错误");
        }
        $output["show_market_price"] = (float)$output["show_market_price"];
        $output["trip_info"] = json_decode($output["trip_info"],true);
        $output["tab"] = $tab;
        $output["state"] = '2';
        $output["goodsCode"]    = $goodsCode;
        return array("code" => 200,"data" => $output);
    }

    //价格库存 3
    public function ratesInventory(){
        $goodsCode = input('post.goodsCode');
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 3){
            return array("code" => 203,"data" => array("tab"=>$tab));
        }
        $res = db('scenery_calendar')
            ->field(['id','date'],true)
            ->field("FROM_UNIXTIME(date,'%Y-%c-%d') as date")
            ->where(array("goods_code" => $goodsCode))
            ->order("date asc")
            ->select();
        if($res){
            foreach ($res as &$k){
                $k["plat_price"] = (float)$k["plat_price"];
                $k["settle_price"] = (float)$k["settle_price"];
//                $k["date"] = date("Y-m-d",$k["date"]);
            }
            return array("code" => 200,"data" => $res);
        }
        return "";
    }

    //商品设置 4
    public function productSet(){
        $goodsCode = input('post.goodsCode');
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 4){
            return array("code" => 203,"data" => array("tab"=>$tab));
        }
        $goodsField = "a.advance_time";
        $ticketField = "b.stock_confirm_time,b.min_buy_num,b.max_buy_is_open,b.max_buy_num,b.refund,b.refund_info,b.contact_info,b.play_people_info,b.friendship_hints,b.book_notice";
        $allField = $goodsField.','.$ticketField;
        $join = [
            ['syy_goods_scenery b','a.code = b.goods_code']
        ];
        $where = [
            "a.code"         => $goodsCode,
            "a.goods_type"  => 3,
            "a.is_del"       =>  ["<>","1"]  //未删除
        ];
        $data = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
        if(!$data){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }

        $data["stock_confirm_time"] = json_decode($data["stock_confirm_time"],true);
        $data["contact_info"] = json_decode($data["contact_info"],true);
        $data["play_people_info"] = json_decode($data["play_people_info"],true);
        $data["state"] = '4';
        $data["tab"] = $this->getGoodsTab($goodsCode);
        $data["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $data);
    }

    //商品信息 5
    public function productInfo(){
        $goodsCode = input('post.goodsCode');
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 4){
            return array("code" => 203,"data" => array("tab"=>$tab));
        }

        $goodsField = "a.show_title,a.on_time,a.off_time";
        $ticketField = "b.recommend_account,b.class_label";
        $supplyField = "c.image";
        $allField = $goodsField.','.$ticketField.','.$supplyField;
        $join = [
            ['goods_scenery b','a.code = b.goods_code'],
            ['syy_goods_supply c','a.code = c.goods_code']
        ];
        $where = [
            "a.code"         => $goodsCode,
            "a.goods_type"  => 3,
            "a.is_del"       =>  ["<>","1"]  //未删除
        ];
        $data = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
        if(!$data){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }

        $data["recommend_account"] = json_decode($data["recommend_account"],true);
        $data["class_label"] = json_decode($data["class_label"],true);
        //图片处理
        $imgArray = json_decode($data["image"],true);
        $data["fileList"] = array();
        foreach ($imgArray as $k){
            $newArray = [
                "name"  => $k ,
                "url"  => config("img_url") . $k ,
                "status"  => "success" ,
            ];
            $data["fileList"][] = $newArray;
        }
        $data["state"] = '5';
        $data["tab"] = $this->getGoodsTab($goodsCode);
        $data["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $data);;
    }

    //显示商品头部 10
    public function showGoodsHead(){
        $data["sp_code"] = getSpCode(); //供应商编号
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            $data["goodsCode"] = "";
            $data["show_title"] = "";
            $data["check_type"] = "0";
            return array("code" => 200,"data" => $data);
        }
        $data = db('goods')->field("show_title,check_type")->where(array("code" => $goodsCode))->find();
        if(empty($data)){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }
        if(empty($data["show_title"])){
            $data["show_title"] = "";
        }
        $data["sp_code"] = getSpCode();
        $data["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $data);

    }


    //获取商品页面 辅
    private function getGoodsTab($goodsCode){
        $res = db('goods_create')->field("tab")->where(array("goods_code" => $goodsCode))->find();
        return $res["tab"];
    }

}