<?php
namespace app\home\logic;
use think\Request;

class ShowGroup
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
                $output = $this->routeInfo();
                break;
            case '2':
                //产品特色
                $output = $this->sellingPoint();
                break;
            case '3':
                //自费项目
                $output = $this->chargedItem();
                break;
            case '4':
                //费用包含
                $output = $this->includeCost();
                break;
            case '5':
                //费用不包含
                $output = $this->notInCost();
                break;
            case '6':
                //特殊人群限制
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
            case '12':
                //价格库存列表
                $output = $this->priceList();
                break;
            case '20':
                //价格库存列表
                $output = $this->goodsHead();
                break;
            default:
                $output = array("code" => 404,"msg" => "参数错误");
        }
        return $output;
    }

    /**
     * todo
     * todo tab规则  0  1  2  3  4  5  6  7
     * todo 必须     1  1  1  0  1  0  0  1
     * todo 页码写入 0  1  3     6        7
     * todo 数据显示判断（页码） tab 1 2 4 7
     * todo 空值判断 3 5 6
     *
     */

    //基本信息 0
    public function basicInfo()
    {
        $goodsCode = input('post.goodsCode');
        if($goodsCode){
            //有商品code 查询
            $goodsField = "a.contact_code,a.inside_code,a.inside_title,a.subtitle,a.advance_time,a.online_type,a.on_time,a.off_time,a.rate";
            $groupField = "b.service_type,b.line_type,b.play_type,b.begin_address,b.end_address,b.main_place,b.service_tel,b.refund_type,b.refund_info";
            $allField = $goodsField.','.$groupField;
            $alias = array("syy_goods" => "a","syy_goods_group" => "b");
            $join = [['syy_goods_group','a.code = b.goods_code']];
            $where = [
                "a.code"        => $goodsCode,
                'a.is_del'      =>  ['<>',"1"]  //未删除
            ];
            $data = db('goods')->alias($alias)->join($join)->field($allField)->where($where)->find();
            if(!$data){
                return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
            }
            $data["service_type"]      =   json_decode($data["service_type"]); //服务保障      （副）
            $data["main_place"]        =   json_decode($data["main_place"]); //主要景点     （副）必须
            $data["service_tel"]       =   json_decode($data["service_tel"]); //客服电话     （副）
            $data["refund_info"]       =   json_decode($data["refund_info"]);//梯度详细退款     （副）

            $data["state"] = '0';
            $data["tab"] = $this->getGoodsTab($goodsCode);
            $data["goodsCode"] = $goodsCode;
            return array("code" => 200,"data" => $data);
        }
        //没有商品code
        $alias = array("syy_goods" => "a","syy_goods_create" => "b");
        $join = [['syy_goods_create','a.code = b.goods_code']];
        $where = [
            "a.check_type"  =>  "0",        //制作中
            "a.goods_type"  =>  "1",        //跟团游
            "a.sp_code"     =>  getSpCode(), //供应商code
            "a.is_del"      =>  ["<>","1"]  //未删除
        ];
        $goodsField = "a.code,a.inside_title";
        $createField = "b.tab";
        $allField = $goodsField.','.$createField;
        $res = db('goods')->alias($alias)->field($allField)->where($where)->join($join)->order("a.last_edit_time desc")->select();
        //有 未填完信息
        if($res){
            foreach ($res as &$k){
                $k["tab"] = $k["tab"] + 1;
            }
            return array("code" => 203,"data" => $res);
        }
        //没有 未填完信息
        return array("code" => 202);
    }

    //行程信息 1
    public function routeInfo()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 1){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $field = "play_day,go_trans,back_trans,go_trans_cost,back_trans_cost,gather_place,route_info";
        $where = [
            "goods_code"        => $goodsCode
        ];
        $output = db('goods_group')->field($field)->where($where)->find();
        if(!$output){
            return array("code" => 403,"msg" => "商品不存在或者商品被删除，请联系管理员");
        }
        $output["gather_place"]      =   json_decode($output["gather_place"]); //集合地点
        $output["route_info"]        =   json_decode($output["route_info"]); //行程详细
        $output["go_trans"]          =   (int)$output["go_trans"];
        $output["back_trans"]        =   (int)$output["back_trans"];
        $output["state"] = '1';
        $output["tab"] = $tab;
        $output["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $output);


    }

    //产品特色 2
    public function sellingPoint()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 2){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }

        $Field = "a.feature_reasons,b.image";
        $alias = array("syy_goods_group" => "a","syy_goods_supply" => "b");
        $join = [['syy_goods_supply','a.goods_code = b.goods_code']];
        $where = [
            "a.goods_code"        => $goodsCode
        ];
        $data = db('goods_group')->alias($alias)->join($join)->field($Field)->where($where)->find();

        if(!$data){
            return array("code" => 405,"msg" => "查询失败，请联系管理员");
        }

        $output["feature_reasons"] = json_decode($data["feature_reasons"]);
        $imgArray = json_decode($data["image"],true);
        $output["fileList"] = array();
        foreach ($imgArray as $k){
            $newArray = [
                "url"           =>  config("img_url") . $k ,
                "status"        =>  "success" ,
                "response"      =>  array("data" => array("name" => $k))
            ];
            $output["fileList"][] = $newArray;
        }
        $output["state"] = '2';
        $output["tab"] = $tab;
        $output["goodsCode"] = $goodsCode;
        return array("code" => 200,"data" => $output);

    }

    //自费项目 3
    public function chargedItem()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);

        $data = db('goods_group')->field("charged_item")->where(array("goods_code"=> $goodsCode))->find();


        if(empty($data["charged_item"])){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $array = json_decode($data["charged_item"],true);
        if(is_array($array)){
            foreach ($array as &$k){
                $k["place"] = (float)$k["place"];
            }
        }
        $output["charged_item"] = $array;
        $output["state"]         = '3';
        $output["tab"]            = $tab;
        $output["goodsCode"]    = $goodsCode;
        return array("code" => 200,"data" => $output);


    }

    //费用包含 4
    public function includeCost()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        if($tab < 4){
            $data = db('goods_group')->field("main_place")->where(array("goods_code"=> $goodsCode))->find();
            if(empty($data)){
                return array("code" => 404,"msg" => "查询错误");
            }
            $data["main_place"]             =   json_decode($data["main_place"]); //门票
            return array("code" => 201,"data" => array("tab" => $tab,"main_place"=>$data["main_place"]));
        }

        $field = 'main_place,little_traffic,stay,food_server,tick_server,guide_server,safe_server,child_price_type,child_price_info,child_price_supply,give_info';
        $data = db('goods_group')->field($field)->where(array("goods_code"=> $goodsCode))->find();
        if(empty($data)){
            return array("code" => 404,"msg" => "查询错误");
        }

        $data["main_place"]             =   json_decode($data["main_place"]); //门票
        $data["child_price_info"]        =   json_decode($data["child_price_info"]); //儿童价说明
        $data["tab"] = $tab;
        $data["state"] = '4';
        $data["goodsCode"]    = $goodsCode;
        return array("code" => 200,"data" => $data);


    }

    //费用不包含 5
    public function notInCost()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);

        $data = db('goods_group')->field("cost_not_include")->where(array("goods_code"=> $goodsCode))->find();

        if(empty($data["cost_not_include"])){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $output["state"]       = '5';
        $output["tab"]         = $tab;
        $output["goodsCode"]    = $goodsCode;
        $output["cost_not_include"] = json_decode($data["cost_not_include"]);

        return array("code" => 200,"data" => $output);
    }

    //特殊人群限制 6
    public function specialPeople()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        $data = db('goods_group')->field("crowd_limit")->where(array("goods_code"=> $goodsCode))->find();
        if(empty($data["crowd_limit"])){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $output["state"]       = '6';
        $output["tab"]         = $tab;
        $output["goodsCode"]    = $goodsCode;
        $output["crowd_limit"] = json_decode($data["crowd_limit"]);

        return array("code" => 200,"data" => $output);
    }

    //预定须知 7
    public function advanceKnow()
    {
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 404,"msg" => "商品号不能为空");
        }
        $tab = $this->getGoodsTab($goodsCode);
        $data = db('goods_group')->field("book_notice")->where(array("goods_code"=> $goodsCode))->find();
        if(empty($data["book_notice"])){
            return array("code" => 201,"data" => array("tab"=>$tab));
        }
        $output["state"]       = '7';
        $output["tab"]         = $tab;
        $output["goodsCode"]    = $goodsCode;
        $output["book_notice"] = json_decode($data["book_notice"]);

        return array("code" => 200,"data" => $output);
    }

    //价格日历显示 11
    public function ratesInventory(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return array("code" => 412,"msg" => "商品号不能为空");
        }
        $res = db('group_calendar')
            ->field(['id'],true)
//            ->field("FROM_UNIXTIME(date,'%Y-%c-%d') as date")
            ->where(array("goods_code" => $goodsCode))
            ->order("date asc")
            ->select();
        if($res){
            foreach ($res as &$k){
                $k["plat_price"] = (float)$k["plat_price"];
                $k["market_price"] = (float)$k["market_price"];
                $k["settle_price"] = (float)$k["settle_price"];
                $k["market_child_price"] = (float)$k["market_child_price"];
                $k["plat_child_price"] = (float)$k["plat_child_price"];
                $k["settle_child_price"] = (float)$k["settle_child_price"];
                $k["plat_house_price"] = (float)$k["plat_house_price"];
                $k["date"] = date("Y-m-d",$k["date"]);
            }
            return array("code" => 200,"data" => $res);
        }
        return "";


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
            $where["date"] = [[ '>=' , strtotime($dateTime[0])], [ '<=' , strtotime($dateTime[1])], "and"];
        }else{
            $where["date"] = [ '>=' , time()];
        }
        $where["goods_code"] = $goodsCode;

        $count = db('group_calendar')->field('id')->where($where)->count();
        if(!$count){
            return array("code" => 200,"data" => array("count" => 0));
        }
        $res = db('group_calendar')
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

    //测试页码 辅 没用？
    private function checkTab($goodsCode){
        $res = db('goods_create')->field("tab")->where(array("goods_code" => $goodsCode))->find();
        if($res === null){

        }
    }




}