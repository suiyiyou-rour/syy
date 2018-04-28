<?php
namespace app\home\logic;
use think\Request;

class ShowTicket
{
    /**
     * 状态分发
     */
    public function dispatcher($state){
        switch ($state) {
            case '0':
                //基本信息
                $output = $this->basicInfo();
                break;
            case '1':
                //行程信息
                $output = $this->buyUsedData();
                break;
            case '2':
                //产品特色
                $output = $this->ratesInventory();
                break;
            case '12':
                //价格库存列表
                $output = $this->priceList();
                break;
            case '20':
                //价格库存列表
                $output = $this->goodsHead();
                break;
            default:
                $output = array("code" => 404, "msg" => "参数错误");
        }
        return $output;

    }


    //基本信息 0
    public function basicInfo()
    {
        $goodsCode = input('post.goodsCode');

        if($goodsCode){
            //有商品code 查询
            $goodsField = "a.contact_code,a.show_title";
            $ticketField = "b.goods_class,b.city,b.place_name,b.ticket_type,b.include_cost,b.include_cost_info,b.not_include_info,b.safe_server,b.safe_server_info,b.service_tel";
            $supplyField = "c.image";
            $allField = $goodsField.','.$ticketField.','.$supplyField;
            $join = [
                ['syy_goods_ticket b','a.code = b.goods_code'],
                ['syy_goods_supply c','a.code = c.goods_code']
            ];
            $where = [
                "a.code"         => $goodsCode,
                "a.goods_type"  => 2,
                "a.is_del"       =>  ["<>","1"]  //未删除
            ];
            $data = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
            if(!$data){
                return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
            }
            $data["include_cost"] = json_decode($data["include_cost"]);
            $data["service_tel"] = json_decode($data["service_tel"]);
            $data["place_name"] = json_decode($data["place_name"],true);

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
            unset($data["image"]);

            $data["state"] = '0';
            $data["tab"] = $this->getGoodsTab($goodsCode);
            $data["goodsCode"] = $goodsCode;
            return array("code" => 200,"data" => $data);
        }

        //没有商品code
        $join = [['syy_goods_create b','a.code = b.goods_code']];
        $where = [
            "a.check_type"  =>  "0",         //制作中
            "a.goods_type"  =>  "2",            //门票
            "a.sp_code"     =>  getSpCode(),  //供应商code
            "a.is_del"      =>  ["<>","1"]  //未删除
        ];
        $goodsField = "a.code,a.show_title";
        $createField = "b.tab";
        $allField = $goodsField.','.$createField;
        $res = db('goods')->alias("a")->field($allField)->where($where)->join($join)->order("a.last_edit_time desc")->select();
        //有 未填完信息
        if($res){
            foreach ($res as &$k){
                $k["tab"] = $k["tab"] + 1;
            }
            return array("code" => 201,"data" => $res);
        }
        //没有 未填完信息
        return array("code" => 202);
    }

    //购买使用说明 1
    public function buyUsedData()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 1){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $goodsField = "a.advance_time";
        $ticketField = "b.advance_time_type,b.contact_need,b.player_info,b.min_buy_num,b.max_buy_num,b.mobile_limit,b.identity_limit,b.entrance_time,b.entrance_place";
        $allField = $goodsField.','.$ticketField;
        $join = [['syy_goods_ticket b','a.code = b.goods_code']];
        $where = [
            "a.code"         => $goodsCode,
            "a.goods_type"  => 2,
            "a.is_del"       =>  ["<>","1"]  //未删除
        ];
        $output = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
        if(!$output){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }
        $output["player_info"] = json_decode($output["player_info"]);
        $output["mobile_limit"] = json_decode($output["mobile_limit"]);
        $output["identity_limit"] = json_decode($output["identity_limit"]);
        $output["entrance_time"] = json_decode($output["entrance_time"]);
        $output["entrance_place"] = json_decode($output["entrance_place"]);
        $output["state"] = '1';
        $output["tab"] = $tab;
        $output["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $output);

    }

    //价格库存有效期 2
    public function ratesInventory()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 2){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }

        $goodsField = "a.price_type,a.online_type,a.offline_type,a.on_time,a.off_time,stock_type,a.stock_type,a.stock_num";
        $ticketField = "b.effective_days,b.usable_date,b.disabled_date,b.refund,b.refund_info";
        $allField = $goodsField.','.$ticketField;
        $join = [['syy_goods_ticket b','a.code = b.goods_code']];
        $where = [
            "a.code"         => $goodsCode,
            "a.goods_type"  => 2,
            "a.is_del"       =>  ["<>","1"]  //未删除
        ];$output = db('goods')->alias("a")->join($join)->field($allField)->where($where)->find();
        if(!$output){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }

        if($output["price_type"] == 1){//1价格日历 2有效期
            $calendarRes = db('ticket_calendar')
                ->field("date,stock_num,plat_price,settle_price,market_price,sales_num")
                ->where(array("goods_code" => $goodsCode))
                ->order("date asc")
                ->select();
            if($calendarRes){
                foreach ($calendarRes as &$k){
                    $k["plat_price"] = (float)$k["plat_price"];
                    $k["settle_price"] = (float)$k["settle_price"];
                    $k["market_price"] = (float)$k["market_price"];
                    $k["date"] = date("Y-m-d",$k["date"]);
                }
            }
            unset($output["usable_date"]);
            unset($output["disabled_date"]);
            $output["dateList"] = $calendarRes;
            //前端要求
            $output["price"] = array("plat_price" => 0 , "settle_price" => 0 ,"market_price" => 0);
            $output["date"] = array();
            $output["stock_num_day"] = 1;
        }else{
            $indateRes = db('ticket_indate')
                ->field("begin_date,end_date,stock_num as stock_num_day,plat_price,settle_price,market_price")
                ->where(array("goods_code" => $goodsCode))
                ->find();
            if($indateRes){
                $indateRes["plat_price"] = (float)$indateRes["plat_price"];
                $indateRes["settle_price"] = (float)$indateRes["settle_price"];
                $indateRes["market_price"] = (float)$indateRes["market_price"];
            }
            $output = array_merge($output,$indateRes);//数组合并
//            $output["plat_price"] = $indateRes["plat_price"];
//            $output["settle_price"] = $indateRes["settle_price"];
//            $output["market_price"] = $indateRes["market_price"];
//            $output["stock_num_day"] = $indateRes["stock_num_day"];
//            $output["begin_date"] = $indateRes["begin_date"];
//            $output["end_date"] = $indateRes["end_date"];
            $output["usable_date"] = json_decode($output["usable_date"]);
            $output["disabled_date"] = json_decode($output["disabled_date"]);
            unset($output["stock_num"]);
            //前端要求
            if(empty($output["effective_days"])){
                $output["effective_days"] = 1;
            }
        }
        $output["refund_info"] = json_decode($output["refund_info"]);
        $output["state"] = '2';
        $output["tab"] = $tab;
        $output["goodsCode"] = $goodsCode;
        return array("code" => 200 ,"data"=>$output);
    }

    //价格日历详细 12
    public function priceList(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"data" => "商品号不能为空");
        }
        $dateTime = input('post.time/a');
        $page = input('post.page');

        if (empty($page)) {
            $page = 1;
        }

        if($dateTime && count($dateTime) >= 2){
            $where["date"] = [
                [ '>=' , strtotime($dateTime[0])],
                [ '<=' , strtotime($dateTime[1])],
                "and"
            ];
        }
        $where["goods_code"] = $goodsCode;

        $count = db('ticket_calendar')->field('id')->where($where)->count();
        if(!$count){
            return array("code" => 200,"data" => array("count" => 0));
        }
        $res = db('ticket_calendar')
            ->field(['id'],true)
            ->where($where)
            ->order("date asc")
            ->page($page,10)
            ->select();
        if($res) {
            foreach ($res as &$k) {
                $k["plat_price"] = (float)$k["plat_price"];
                $k["market_price"] = (float)$k["market_price"];
                $k["settle_price"] = (float)$k["settle_price"];
                $k["market_child_price"] = (float)$k["market_child_price"];
                $k["plat_child_price"] = (float)$k["plat_child_price"];
                $k["settle_child_price"] = (float)$k["settle_child_price"];
                $k["plat_house_price"] = (float)$k["plat_house_price"];
                $k["date"] = date("Y-m-d",$k["date"]);
            }
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return array("code" => 200,"data" => $output);
    }

    //商品头部信息显示 20
    public function goodsHead(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"data" => "商品号不能为空");
        }
        $allField = "code,inside_code,inside_title,show_title,check_type";
        $where = [
            "code"        => $goodsCode,
            'is_del'      =>  ['<>',"1"]  //未删除
        ];
        $res = db('goods')->field($allField)->where($where)->find();
        if(empty($res)){
            return array("code" => 403,"data" => "查询错误,商品号不对或者被删除");
        }
        return array("code" => 200,"data" => $res);
    }

    //获取商品页面 辅
    private function getGoodsTab($goodsCode){
        $res = db('goods_create')->field("tab")->where(array("goods_code" => $goodsCode))->find();
        return $res["tab"];
    }

    //数据库图片转显示格式
    public function showImage($str){
        $imgArray = json_decode($str,true);
        $array = array();
        foreach ($imgArray as $k){
            $newArray = [
                "name"  => $k ,
                "url"  => config("img_url") . $k ,
                "status"  => "success" ,
            ];
            $array[] = $newArray;
        }
        return $array;
    }

}