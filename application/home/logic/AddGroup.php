<?php
namespace app\home\logic;

use think\Request;

class AddGroup
{
    /**
     * 状态分发
     */
    public function dispatcher($state)
    {
        //需要商品code
        $goodsCode = input('post.goodsCode');
        if ($state != '0' && $state != '11') {
            if (empty($goodsCode)) {
                return json_encode(array("code" => 412, "msg" => "添加商品，商品号不能为空"));
            }
            //是否有写入状态检测
            $res = $this->checkGoodsType($goodsCode);
            if ($res !== true) {
                return json_encode(array("code" => 405, "msg" => $res));
            }
        }
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
            case '100':
                //图片上传
                $output = $this->imageUpload();
                break;
            case '101':
                //图片删除
                $output = $this->imageDel();
                break;
            default:
                $output = array("code" => 404, "msg" => "参数错误");
        }
        $this->endOperation($goodsCode,$state);//后置方法
        return $output;
    }

    //基本信息添加 0
    public function basicInfo()
    {
        //数据验证
        $data = $this->basicInfoData();
        $validate = new \app\home\validate\Group();
        $result = $validate->scene('addBasicInfo')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());// 验证失败 输出错误信息
        }

        //主表添加数据
        $goodsData["contact_code"]      =   $data["contact_code"];  //合同编码     （主）必须
        $goodsData["inside_code"]       =   $data["inside_code"];   //内部编号     （主）
        $goodsData["inside_title"]      =   $data["inside_title"];  //内部显示标题 （主）必须
        $goodsData["subtitle"]          =   $data["subtitle"];      //商品副标题   （主）
        $goodsData["advance_time"]      =   $data["advance_time"]; //提前预定时间 （主）必须
        $goodsData["online_type"]       =   $data["online_type"];  //上线类型      (主)必须
        $goodsData["last_edit_time"]    =   time();                 //最后编辑时间
        if($goodsData["online_type"] == 1){ //审核通过上线
            $goodsData["offline_type"] = 3; //最后团期过期下线
        }else{
            $goodsData["offline_type"] = 2; //指定下架时间
            $goodsData["on_time"]        =   $data["on_time"];       //上线时间     （主）
            $goodsData["off_time"]       =   $data["off_time"];      //下线时间     （主）
        }
        $goodsData["rate"]              =   $data["rate"];          //产品费率      （主）必须
        //副表添加数据
        $groupData["service_type"]      =   $data["service_type"];   //服务保障   （副）
        $groupData["line_type"]         =   $data["line_type"];      //路线类型   （副）
        $groupData["play_type"]         =   $data["play_type"];      //游玩类型   （副）
        $groupData["begin_address"]     =   $data["begin_address"]; //出发地     （副）必须
        $groupData["end_address"]       =   $data["end_address"];   //目的地     （副）必须
        $groupData["main_place"]        =   $data["main_place"];    //主要景点   （副）必须
        $groupData["service_tel"]       =   $data["service_tel"];   //客服电话   （副）
        $groupData["refund_type"]       =   $data["refund_type"];   //退款类型   （副）必须
        $groupData["refund_info"]       =   $data["refund_info"];   //梯度详细退款（副）

        //有商品号（更新）
        $goodsCode = input('post.goodsCode');
        if ($goodsCode) {
            $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
            $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($groupData);
            if ($goodsRes === false) {
                return array("code" => 403, "msg" => "保存出错，请稍后再试");
            }
            if ($groupRes === false) {
                return array("code" => 403, "msg" => "保存错误，请稍后再试");
            }
            return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
        }

        //没有商品号（保存）
        $hash = input('post.hash');
        if (!checkFromHash($hash)) {
            return array("code" => 405, "msg" => "您表单提交速度过快，请3秒后重试。");
        }
        $goodsCode = createGoodsCode("g");  //产品编号
        //主表添加数据
        $goodsData["code"]              =   $goodsCode;                //产品编号
        $goodsData["sp_code"]           =   getSpCode();               //供应商编号
        $goodsData["create_time"]       =   time();                    //创建时间
        $goodsData["goods_type"]        =   1;                         //跟团游

        //副表添加数据
        $groupData["goods_code"]        =   $goodsCode;                 //产品编号
        //补充表
        $supplyData["goods_code"]       =   $goodsCode; //产品编号

        $goodsRes = db('goods')->insert($goodsData);
        $groupRes = db('goods_group')->insert($groupData);
        $supplyRes = db('goods_supply')->insert($supplyData);
        if ($goodsRes && $groupRes && $supplyRes) {
            db('goods_create')->insert(array('goods_code' => $goodsCode));  //插入页码表
            $this->delCreateRear();//删除多余的条数
            return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
        } else {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
    }

    //行程信息添加 1
    public function routeInfo()
    {
        $goodsCode = input('post.goodsCode');
        //数据验证
        $data      = $this->routeInfoData();
        $validate  = new \app\home\validate\Group();
        $result    = $validate->scene('addRouteInfo')->check($data);
        if (true !== $result) {
            // 验证失败 输出错误信息
            return array("code" => 405, "msg" => $validate->getError());
        }
        $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($data);
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));

    }

    //产品特色添加 2
    public function sellingPoint()
    {
        $goodsCode = input('post.goodsCode');

        $data = input('post.');
        //图片数组
        if (empty($data["fileList"]) || empty($data["feature_reasons"])) {
            return array("code" => 404, "msg" => "上传参数错误1");
        }
        $fileList = objSetArray($data["fileList"]);
        if (empty($fileList[0]["name"])) {
            return array("code" => 404, "msg" => "上传参数错误2");
        }
        $imageArray = array();
        foreach ($fileList as $k) {
            $imageArray[] = $k["name"];
        }

        $goodsData["head_img"]          =  $fileList[0]["name"];                  //首图
        $supplyData["image"]            =  json_encode($imageArray);               //图片数组
        $groupData["feature_reasons"]  =  json_encode($data["feature_reasons"]);//推荐理由

        $goodsRes  = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
        $supplyRes = db('goods_supply')->where(array("goods_code" => $goodsCode))->update($supplyData);
        $groupRes  = db('goods_group')->where(array("goods_code" => $goodsCode))->update($groupData);
        if ($goodsRes === false) {
            return array("code" => 403, "msg" => "首图保存出错，请稍后再试");
        }
        if ($supplyRes === false) {
            return array("code" => 403, "msg" => "图片保存错误，请稍后再试");
        }
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "推荐理由保存错误，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));


    }

    //自费项目添加 3
    public function chargedItem()
    {
        $goodsCode = input('post.goodsCode');

        $data = input('post.');
        if(empty($data["charged_item"])){
            return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
        }
        $groupData["charged_item"] = $data["charged_item"];
        if (empty($groupData["charged_item"])) {
            $groupData["charged_item"] = array();
        }

        $groupData["charged_item"] = json_encode($groupData["charged_item"]); //自费项目
        $res = db('goods_group')->where(array("goods_code" => $goodsCode))->update($groupData);
        if ($res === false) {
            return array("code" => 403, "msg" => "推荐理由保存错误，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //费用包含添加 4
    public function includeCost()
    {
        $goodsCode = input('post.goodsCode');

        //数据验证
        $data = $this->includeCostData();
//        $validate = new \app\home\validate\Group();
//        $result = $validate->scene('addIncludeCost')->check($data);
//        if(true !== $result){
//            // 验证失败 输出错误信息
//            return array("code" => 405,"msg" => $validate->getError());
//        }

        $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($data);
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //费用不包含添加 5
    public function notInCost()
    {
        $goodsCode = input('post.goodsCode');

        $post = input("post.");
        if (empty($post["cost_not_include"])) {
            return array("code" => 404, "msg" => "费用不包含不能为空");
        }
        $cost_not_include = json_encode($post["cost_not_include"]);

        $array = json_decode($cost_not_include, true);
        $data["single_supplement"] = $array["room"]["one"];     //单房差
        $data["cost_not_include"] = $cost_not_include;  //费用不包含
        $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($data);
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //特殊人群限制添加 6
    public function specialPeople()
    {
        $goodsCode = input('post.goodsCode');

        $postData = input("post.");
        $data["crowd_limit"] = json_encode($postData["crowd_limit"]);
        if (empty($data["crowd_limit"])) {
            return array("code" => 404, "msg" => "不能为空");
        }
        $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($data);
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));

    }

    //预定须知添加 7
    public function advanceKnow()
    {
        $goodsCode = input('post.goodsCode');

        $postData = input("post.");
        $data["book_notice"] = json_encode($postData["book_notice"]);
        if (empty($data["book_notice"])) {
            return array("code" => 404, "msg" => "不能为空");
        }
        $groupRes = db('goods_group')->where(array("goods_code" => $goodsCode))->update($data);
        if ($groupRes === false) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));

    }

    //价格库存添加 11
    public function ratesInventory()
    {
        $postData = input('post.');
        $goodsCode = $postData["goodsCode"];
        if(empty($goodsCode)){
            return array("code" => 412, "msg" => "商品号上传不能为空");
        }
        $priData = $postData["priData"];
        if (empty($priData["date"]) || empty($priData["price"])) {
            return array("code" => 404, "msg" => "上传参数不能为空");
        }
        $dateArray = $priData["date"];      //日期数组

        $price = $priData["price"];         //价格对象

        $bol = true;
        $error = ""; //错误信息
        $data = json_decode(json_encode($price), true);//对象转数组
        //todo 数据验证

        //todo 儿童和成人同价
        $groupRes = db('goods_group')->field("child_price_type")->where(array("goods_code" => $goodsCode))->find();
        if(empty($groupRes)){
            return array("code" => 403, "msg" => "产品查询失败，请联系管理员");
        }
        if($groupRes["child_price_type"] == 0){//无儿童价格
            $data["child_is_opne"] = 0;
            $data["market_child_price"] = 0;
            $data["plat_child_price"] = 0;
            $data["settle_child_price"] = 0;
        }else if($groupRes["child_price_type"] == 1){//与成人同价
            $data["child_is_opne"] = 1;
            $data["market_child_price"] = $data["market_price"];
            $data["plat_child_price"] = $data["plat_price"];
            $data["settle_child_price"] = $data["settle_price"];
        }


        foreach ($dateArray as $k) {
            $data["date"] = strtotime($k);//时间戳;
            $res = db('group_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->find();
            if ($res) {//修改状态
                $saveRes = db('group_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->update($data);
                if ($saveRes === false) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            } else {//添加状态
                $data["goods_code"] = $goodsCode;
                $AddRes = db('group_calendar')->insert($data);
                if ($AddRes === false) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            }
        }
        if (!$bol) {
            return array("code" => 403, "msg" => "保存日期错误，错误的日期为" . $error . "请再试一次或者联系管理员");
        }
        $this->saveGoodsType($goodsCode);//更改商品上线状态
        return array("code" => 200, "msg" => "保存成功");

    }

    //异步上传图片 100
    private function imageUpload()
    {
//        return array("code" => 404,"msg" => "上传大小错误");
        //todo 商品号
        $goodsCode = input('post.goodsCode');
//        return array("code" => 404,"msg" => $goodsCode);
        $imgLimit = config("imageUpLimit");
        $file = request()->file('file');
        if (empty($file)) {
            return array("code" => 404, "msg" => "参数错误");
        }
        $info = $file->validate($imgLimit)->move(ROOT_PATH . 'public' . DS . 'image' . DS . 'group');
        if ($info) {
            return array("code" => 200, "data" => array("name" => 'group' . DS . $info->getSaveName(), "goodsCode" => $goodsCode));
        } else {
            // 上传失败获取错误信息
            return array("code" => 404, "msg" => $file->getError());
        }
    }

    //图片删除 101
    private function imageDel()
    {
        $name = input("post.name");
        $goodsCode = input("post.goodsCode");
        return array("code" => 200, "data" => $name);
    }


    //基本信息数据接收 0
    private function basicInfoData()
    {
        $gain = ['contact_code', 'inside_code', 'inside_title', 'subtitle', 'service_type', 'line_type', 'play_type', 'begin_address', 'end_address', 'main_place', 'advance_time', 'online_type', 'on_time', 'off_time', 'service_tel', 'refund_type', 'refund_info', 'rate'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
//        $data = testGroupPage0();//测试参数
        if (empty($data["service_type"])) {
            $data["service_type"] = ""; //服务保障      （副）
        }
        if (empty($data["service_type"])) {
            $data["main_place"] = ""; //主要景点      （副）必须
        }
        if (empty($data["service_type"])) {
            $data["service_tel"] = ""; //客服电话      （副）
        }
        if (empty($data["service_type"])) {
            $data["refund_info"] = ""; //梯度详细退款      （副）
        }
        $data["service_type"] = json_encode($data["service_type"]); //服务保障      （副）
        $data["main_place"] = json_encode($data["main_place"]); //主要景点     （副）必须
        $data["service_tel"] = json_encode($data["service_tel"]); //客服电话     （副）
        $data["refund_info"] = json_encode($data["refund_info"]);//梯度详细退款     （副）
        return $data;
    }

    //行程信息数据接收 1
    private function routeInfoData()
    {
        $gain = ['play_day', 'go_trans', 'back_trans', 'go_trans_cost', 'back_trans_cost', 'gather_place', 'route_info'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');+
        if (empty($data["gather_place"])) {
            $data["gather_place"] = "";     //集合地点
        }
        if (empty($data["route_info"])) {
            $data["route_info"] = "";   //行程详细
        }
        $data["gather_place"] = json_encode($data["gather_place"]); //集合地点
        $data["route_info"] = json_encode($data["route_info"]); //行程详细
        return $data;
    }

    //产品特色数据接收 2
    private function sellingPointData()
    {
        $gain = ['fileList', 'feature_reasons'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');+
        if (empty($data["feature_reasons"])) {
            $data["feature_reasons"] = "";      //推荐理由
        }
        $data["feature_reasons"] = json_encode($data["feature_reasons"]); //推荐理由


        return $data;
    }

    //费用包含数据接收
    private function includeCostData()
    {
        $gain = ['main_place', 'little_traffic', 'stay', 'food_server', 'tick_server', 'guide_server', 'safe_server', 'child_price_type', 'child_price_info', 'child_price_supply', 'give_info'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        if (empty($data["main_place"])) {
            $data["main_place"] = ""; //门票
        }
        if (empty($data["child_price_info"])) {
            $data["child_price_info"] = ""; //儿童价说明
        }
        $data["main_place"] = json_encode($data["main_place"]); //门票
        $data["child_price_info"] = json_encode($data["child_price_info"]); //儿童价说明
        return $data;
    }

    //
    private function notInCostData()
    {
        $gain = ['vis_major', 'stay', 'food_server', 'tick_server', 'guide_server', 'safe_server', 'child_price_type', 'child_price_info', 'child_price_supply', 'give_info'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');

    }



    //商品修改状态检测
    private function checkGoodsType($goodsCode)
    {
        $where = array(
            "code" => $goodsCode,
            'is_del' => ['<>', "1"]  //未删除
        );
        $res = db('goods')->field("check_type")->where($where)->find();
        if (!$res) {
            return "没有商品或者商品被删除";
        }
        if ($res["check_type"] !== 0 && $res["check_type"] !== 1) {
            return "商品不在编辑状态";
        }
        return true;
    }

    //外部显示标题拼接 主
    private function getShowTitle($goodsCode){
        $allField = "a.code,b.main_place,b.service_type,b.play_day";
        $alias = array("syy_goods" => "a","syy_goods_group" => "b");
        $join = [['syy_goods_group','a.code = b.goods_code']];
        $where = [
            "a.code"        => $goodsCode,
            'a.is_del'      =>  ['<>',"1"]  //未删除
        ];
        $res = db('goods')->alias($alias)->join($join)->field($allField)->where($where)->find();
        if($res){
            $mainPlace = $this->getMainPlaceName($res["main_place"]);
            $serviceType = $this->getServiceTypeName($res["service_type"]);
            $playDay = "";
            if($res["play_day"]){
                $playDay = $res["play_day"]."日";
            }
            $data["show_title"] = $mainPlace.$serviceType.$playDay."跟团游";
            $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($data);
            if($goodsRes === false){
                echo json_encode(array("code" => 403, "msg" => "外部标题保存失败，请联系管理员"));
                die;
            }
        }
    }

    //获取主要景点拼接
    private function getMainPlaceName($mainPlace){
        $i = 0;
        $str = "";
        $mpArray = json_decode($mainPlace,true);
        foreach ($mpArray as $k){
            if(++$i > 3) {
                break;
            }
            $str .= $k["place"]."、";
        }
        return $str;
    }

    //获取服务类型拼接
    private function getServiceTypeName($serviceType){
        $str = "";
        $serviceNameData = array(
            "checked1" => "100%出团",
            "checked2" => "无购物",
            "checked3" => "无自费",
        );
        $stArray = json_decode($serviceType,true);
        foreach ($stArray as $k => $v){
            if($v == 1){
                $str .= $serviceNameData[$k];
            }
        }
        return $str;
    }

    //后置方法 步骤操作结束后完成的事
    private function endOperation($goodsCode,$state){
        //更新外部标题
        if($state == 0 || $state == 1){
            $this->getShowTitle($goodsCode);
        }
        $this->lastEditTime($goodsCode);
        $tab = db('goods_create')->where(array("goods_code" => $goodsCode))->value('tab');
        /**
         * state    0  1  2  3  4  5  6  7
         * 必须     1  1  1  0  1  0  0  1
         * tab      0  1  3     7
         */
        if($tab !== null && $tab < 7){
            //更新tab
            if($state == 1){
                if ($tab < 1) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 1));
                };
            }else if($state == 2){
                if ($tab < 3) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 3));
                };
            }else if($state == 4){
                if ($tab < 7) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 7));
                };
            }
        }
    }

    //更改商品保存状态 从已编辑到保存 0 - 1
    private function saveGoodsType($goodsCode){
        $where = [
            "code"        => $goodsCode,
            'is_del'      =>  ['<>',"1"]  //未删除
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(!$res)return;
        $calendarRes = db('group_calendar')->field("id")->where(array("goods_code" => $goodsCode))->find();
        if(!$calendarRes)return;
        if($res["check_type"] == 0){
            db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>1));
        }
        $this->saveGoodsUpdate($goodsCode); //保存时需要更新的字段
    }

    //更新最后一次编辑时间
    private function lastEditTime($goodsCode){
        $where = [
            "code"              => $goodsCode,
            "is_del"            =>  ['<>',"1"],  //未删除
        ];
        $res = db('goods')->field("id")->where($where)->find();
        if($res){
            $data["last_edit_time"] = time();
            db('goods')->where(array("code" => $goodsCode))->update($data);
        }
    }

    //删除多余的未保存商品
    private function delCreateRear(){
        $where = [
            "check_type"  =>  "0",        //制作中
            "goods_type"  =>  "1",        //跟团游
            "sp_code"     =>  getSpCode(), //供应商code
            "is_del"      =>  ["<>","1"],  //未删除
        ];
        $count = db('goods')->where($where)->count("id");
        if($count > 5){
            $code = db('goods')->where($where)->order("last_edit_time asc")->value('code');
            if($code){
                db('goods')->where(array("code"=>$code))->delete();
                db('goods_group')->where(array("goods_code"=>$code))->delete();
                db('goods_supply')->where(array("goods_code"=>$code))->delete();
                db('goods_create')->where(array("goods_code"=>$code))->delete();
            }
        }
    }

    //商品保存主要需要更新字段 下线时间 展示平台价格 展示结算价格
    private function saveGoodsUpdate($goodsCode){
        $calendarField = "MIN(plat_price) as plat_price ,MAX(date) as deadline_date,MIN(settle_price) as settle_price";
        $data = db('group_calendar')->field($calendarField)->where(array("goods_code"=>$goodsCode))->find();
        $res = db('goods')->field('offline_type')->where(array("code" => $goodsCode))->find();
        if($res["offline_type"] == 3){
            $data["off_time"] = $data["deadline_date"];
        }
        db('goods')->where(array("code" => $goodsCode))->update($data);
    }


}