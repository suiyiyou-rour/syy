<?php
namespace app\home\logic;

use think\Request;

class AddTicket
{
    /**
     * 状态分发
     */
    public function dispatcher($state)
    {
        //需要商品code
        $goodsCode = input('post.goodsCode');
        if ($state != '0' && $state != '100' && $state != '101') {
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
                $output = $this->buyUsed();
                break;
            case '2':
                //产品特色添加
                $output = $this->rulesSet();
                break;
            case '11':
                //价格库存
                $output = $this->ratesInventory();
                break;
            case '100':
                //图片异步上传
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
        return json_encode($output);
    }

    //基本信息添加 0
    public function basicInfo()
    {
        $goodsCode = input('post.goodsCode');
        //数据验证
        $data = $this->basicInfoData();
        $validate = new \app\home\validate\Ticket();
        $result = $validate->scene('addBasicInfo')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }

        //主表添加数据
        $goodsData["sp_code"]       =   session("sp.code");     //供应商编号
        $goodsData["contact_code"]  =   $data["contact_code"]; //合同编码  （主）必须
        $goodsData["show_title"]    =   $data["show_title"];    //产品名称(外部标题) （主）必须
        //副表添加数据
        $ticketData["goods_class"]       =   $data["goods_class"];          //商品种类  （副）必须
        $ticketData["city"]              =   $data["city"];                  //城市      （副）必须
        $ticketData["place_name"]        =   $data["place_name"];           //景点名称   （副）必须
        $ticketData["ticket_type"]       =   $data["ticket_type"];          //门票票种  （副）必须
        $ticketData["include_cost"]      =   $data["include_cost"];         //费用包含 （副）必须
        $ticketData["include_cost_info"] =   $data["include_cost_info"];   //费用包含补充说明（副）
        $ticketData["not_include_info"]  =   $data["not_include_info"];    //费用不包含（副）
        $ticketData["safe_server"]       =   $data["safe_server"];          //保险服务 1 0 （副）必须
        $ticketData["safe_server_info"] =   $data["safe_server_info"];       //保险说明 （副）
        $ticketData["service_tel"]       =   $data["service_tel"];           //客服电话 （副）必须
        //补充表
//        $supplyData["image"]        =   $data["image"];     //图片（补）
        //图片数组处理
        if (empty($data["fileList"])) {
            return array("code" => 404, "msg" => "图片参数上传参数错误");
        }
        $fileList = objSetArray($data["fileList"]);
        if (empty($fileList[0]["name"])) {
            return array("code" => 404, "msg" => "图片参数上传参数错误-首图丢失");
        }
        $imageArray = array();
        foreach ($fileList as $k) {
            $imageArray[] = $k["name"];
        }
        $goodsData["head_img"] = $fileList[0]["name"];      //首图(主)
        $supplyData["image"] = json_encode($imageArray);    //图片数组（补充）

        //有商品号（更新）
        if ($goodsCode) {
            $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
            $groupRes = db('goods_ticket')->where(array("goods_code" => $goodsCode))->update($ticketData);
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
        $goodsCode = createGoodsCode("t");                  //产品编号
        //主表添加数据
        $goodsData["code"]          =   $goodsCode;        //产品编号
        $goodsData["create_time"]   =   time();            //创建时间
        $goodsData["goods_type"]    =   2;                 //门票
        //副表
        $ticketData["goods_code"]   =   $goodsCode;        //产品编号
        //补充表
        $supplyData["goods_code"]   =   $goodsCode;         //产品编号

        $goodsRes = db('goods')->insert($goodsData);
        $groupRes = db('goods_ticket')->insert($ticketData);
        $supplyRes = db('goods_supply')->insert($supplyData);
        if ($goodsRes && $groupRes && $supplyRes) {
            db('goods_create')->insert(array('goods_code' => $goodsCode));  //插入页码表
            return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
        } else {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
    }

    //购买使用说明 1
    public function buyUsed()
    {
        $goodsCode = input('post.goodsCode');
        //数据验证
        $data = $this->buyUsedData();
        $validate = new \app\home\validate\Ticket();
        $result = $validate->scene('addBuyUsed')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        //主表添加数据
        $goodsData["advance_time"]      =   $data["advance_time"]; //提前预定时间   （主）必须
        //副表添加数据
        $ticketData["contact_need"]     =   $data["contact_need"];  //联系人信息      (副)必须
        $ticketData["player_info"]      =   $data["player_info"];   //游玩人限制信息  (副)必须
        $ticketData["min_buy_num"]      =   $data["min_buy_num"];   //最少购买人数   (副)必须
        $ticketData["max_buy_num"]      =   $data["max_buy_num"];   //最多购买人数   (副)必须
        $ticketData["mobile_limit"]     =   $data["mobile_limit"];  //手机号限制    (副)
        $ticketData["identity_limit"]   =   $data["identity_limit"];//身份证限制    (副)
        $ticketData["entrance_time"]    =   $data["entrance_time"]; //入园时间      (副)必须
        $ticketData["entrance_place"]   =   $data["entrance_place"];//入园地址     (副)必须

        $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
        $ticketRes = db('goods_ticket')->where(array("goods_code" => $goodsCode))->update($ticketData);
        if ($goodsRes === false) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        if ($ticketRes === false) {
            return array("code" => 403, "msg" => "保存错误，请稍后再试");
        }

        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));

    }

    //价格库存有效期 规则设置2
    public function rulesSet(){
        $priceType = input('post.price_type');
        if($priceType != "1" && $priceType != "2"){//1价格日历 2有效期
            return array("code" => 403, "msg" => "日期模式错误");
        }
        if($priceType == "2"){
            return $this->rulesSetIndate();
        }else{
            return $this->rulesSetCalendar();
        }


    }

    //有效期 tab2 设置 002
    private function rulesSetIndate(){
        $goodsCode = input('post.goodsCode');
        //数据验证
        $data = $this->rulesSetIndateData();
        $validate = new \app\home\validate\Ticket();
        $result = $validate->scene('AddRulesSetIndate')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        //主表添加数据
        $goodsData["online_type"]       =   $data["online_type"];   //上线类型    (主)
        $goodsData["offline_type"]      =   $data["offline_type"];  //上线类型    (主)
        $goodsData["on_time"]           =   $data["on_time"];       //上线时间     (主)
        $goodsData["off_time"]          =   $data["off_time"];       //下线时间     (主)
        $goodsData["price_type"]        =   $data["price_type"];    //价格类型  (主)必须
        $goodsData["stock_type"]        =   $data["stock_type"];    //库存模式  (主)必须
        //副表添加数据
        $ticketData["usable_date"]      =   $data["usable_date"];   //可用日期  (副)必须        --价格日历没有
        $ticketData["disabled_date"]    =   $data["disabled_date"];   //不可用日期  (副)        --价格日历没有
        $ticketData["refund"]           =   $data["refund"];        //退款设置   (副)必须
        $ticketData["refund_info"]      =   $data["refund_info"];   //退款设置   (副)
        //有效期表数据
        $indateData["begin_date"]       =   $data["begin_date"];    //有效期开始时间 (indate)必须 --有效期模式没有
        $indateData["end_date"]         =   $data["end_date"];      //有效期开始时间 (indate)必须 --有效期模式没有
        $indateData["stock_num"]        =   $data["stock_num"];     //库存 必须 （indate）有效期  （价-主）总库存
        $indateData["plat_price"]       =   $data["plat_price"];    //价格       （indate）必须
        $indateData["settle_price"]     =   $data["settle_price"];  //结算价格   （indate）必须
        $indateData["market_price"]     =   $data["market_price"];  //市场价格   （indate）必须

        $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
        $ticketRes = db('goods_ticket')->where(array("goods_code" => $goodsCode))->update($ticketData);

        $indateCheck = db('ticket_indate')->field("id")->where(array("goods_code" => $goodsCode))->find();
        if($indateCheck){
            $indateRes = db('ticket_indate')->where(array("goods_code" => $goodsCode))->update($indateData);
        }else{
            $indateData["goods_code"] = $goodsCode;
            $indateRes = db('ticket_indate')->insert($indateData);
        }

        if ($goodsRes === false) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        if ($ticketRes === false) {
            return array("code" => 403, "msg" => "保存错误，请稍后再试");
        }
        if ($indateRes === false) {
            return array("code" => 403, "msg" => "保存错误，请稍后再试");
        }
        $this->saveGoodsTypeYx($goodsCode);//更改商品状态
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //价格日历 tab2 设置 001
    private function rulesSetCalendar(){
        $goodsCode = input('post.goodsCode');
        //数据验证
        $data = $this->rulesSetCalendarData();
        $validate = new \app\home\validate\Ticket();
        $result = $validate->scene('AddRulesSetCalendar')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        //主表添加数据
        $goodsData["online_type"]       =   $data["online_type"];   //上线类型  (主)
        $goodsData["offline_type"]      =   $data["offline_type"];  //上线类型  (主)
        $goodsData["on_time"]           =   $data["on_time"];        //上线时间  (主)
        $goodsData["off_time"]          =   $data["on_time"];        //下线时间  (主)
        $goodsData["price_type"]        =   $data["price_type"];    //价格类型  (主)必须
        $goodsData["stock_type"]        =   $data["stock_type"];    //库存模式  (主)必须
        $goodsData["stock_num"]         =   $data["stock_num"];     //库存(主)  必须
        //副表添加数据
        $ticketData["refund"]               =   $data["refund"];          //退款设置 (副)必须
        $ticketData["refund_info"]          =   $data["refund_info"];    //退款设置 (副)
        $ticketData["effective_days"]      =   $data["effective_days"]; //有效天数 (副)     --有效期模式没有

        $goodsRes = db('goods')->where(array("code" => $goodsCode))->update($goodsData);
        $ticketRes = db('goods_ticket')->where(array("goods_code" => $goodsCode))->update($ticketData);
        if ($goodsRes === false) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        if ($ticketRes === false) {
            return array("code" => 403, "msg" => "保存错误，请稍后再试");
        }
        //价格日历
        $calendarData["date"]                =   $data["date"];
        $calendarData["stock_num"]          =   $data["stock_num_day"]; //日库存    （calendar）必须
        $calendarData["plat_price"]         =   $data["plat_price"];    //价格       （calendar）必须
        $calendarData["settle_price"]       =   $data["settle_price"];  //结算价格   （calendar）必须
        $calendarData["market_price"]       =   $data["market_price"];  //市场价格   （calendar）必须

        $calendaRes = $this->upDateCalendar($calendarData,$goodsCode);
        if($calendaRes !== true){
            return $calendaRes;
        }
        $this->saveGoodsType($goodsCode);//更改商品状态
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //价格日历上传  (辅助)
    private function upDateCalendar($priData,$goodsCode){
        $dateArray = $priData["date"];      //日期数组
        if(empty($dateArray)){
            return array("code" => 403, "msg" => "日期不能为空");
        }
        $bol = true;
        $error = ""; //错误信息
        foreach ($dateArray as $k) {
            $data["date"]           =   strtotime($k);//时间戳;
            $data["stock_num"]      =   $priData["stock_num"]; //日库存
            $data["plat_price"]     =   $priData["plat_price"]; //价格
            $data["settle_price"]   =   $priData["settle_price"]; //结算价格
            $data["market_price"]   =   $priData["market_price"]; //市场价格
            $res = db('ticket_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->find();
            if ($res) {//修改状态
                $saveRes = db('ticket_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->update($data);
                if ($saveRes === false) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            } else {//添加状态
                $data["goods_code"] = $goodsCode;
                $AddRes = db('ticket_calendar')->insert($data);
                if ($AddRes === false) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            }
        }
        if (!$bol) {
            return array("code" => 403, "msg" => "保存日期错误，错误的日期为" . $error . "请再试一次或者联系管理员");
        }
        return true;
    }

    //价格库存 11
    public function ratesInventory()
    {
        return "ratesInventory";
    }

    //异步上传图片 100
    private function imageUpload()
    {
        $imgLimit = config("imageUpLimit");
        $file = request()->file('file');
        if (empty($file)) {
            return array("code" => 404, "msg" => "参数错误");
        }
        $info = $file->validate($imgLimit)->move(ROOT_PATH . 'public' . DS . 'image' . DS . 'ticket');
        if ($info) {
            return array("code" => 200, "data" => array("name" => 'ticket' . DS . $info->getSaveName()));
        } else {
            // 上传失败获取错误信息
            return array("code" => 403, "msg" => $file->getError());
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
        $gain = ['contact_code', 'goods_class', 'city', 'place_name', 'ticket_type', 'show_title', 'include_cost', 'include_cost_info', 'not_include_info', 'safe_server','safe_server_info', 'service_tel', 'fileList'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        if (empty($data["include_cost"])) {
            $data["include_cost"] = ""; //费用包含     （副）必须
        }
        if (empty($data["service_tel"])) {
            $data["service_tel"] = ""; //客服电话      （副）必须
        }
        if (empty($data["place_name"])) {
            $data["place_name"] = ""; //景点名称      （副）必须
        }
        if(empty($data["safe_server_info"])){
            $data["safe_server_info"] = "";
        }
        $data["include_cost"] = json_encode($data["include_cost"]);
        $data["service_tel"] = json_encode($data["service_tel"]);
        $data["place_name"] = json_encode($data["place_name"]);
        return $data;
    }

    //购买使用说明 1
    private function buyUsedData()
    {
        $gain = ['advance_time', 'contact_need', 'player_info', 'min_buy_num', 'max_buy_num', 'mobile_limit', 'identity_limit', 'entrance_time', 'entrance_place'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        if (empty($data["player_info"])) {
            $data["player_info"] = ""; //游玩人信息
        }
        if (empty($data["mobile_limit"])) {
            $data["mobile_limit"] = ""; //手机号限制
        }
        if (empty($data["identity_limit"])) {
            $data["identity_limit"] = ""; //身份证
        }
        if (empty($data["entrance_time"])) {
            $data["entrance_time"] = ""; //入园时间
        }
        if (empty($data["entrance_place"])) {
            $data["entrance_place"] = ""; //入园地址
        }
        $data["player_info"] = json_encode($data["player_info"]);
        $data["mobile_limit"] = json_encode($data["mobile_limit"]);
        $data["identity_limit"] = json_encode($data["identity_limit"]);
        $data["entrance_time"] = json_encode($data["entrance_time"]);
        $data["entrance_place"] = json_encode($data["entrance_place"]);
        return $data;
    }

    //价格日历 tab2 数据接收 001
    private function rulesSetCalendarData()
    {
        $gain = ['price_type','effective_days','stock_type','stock_num','stock_num_day','date','price','refund','refund_info','online_type','offline_type','on_time','off_time'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        if(empty($data["refund_info"])){
            $data["refund_info"] = "";
        }
        $data["refund_info"] = json_encode($data["refund_info"]);
        if($data["stock_type"] == 1 || $data["stock_type"] == 3){    //无限库存或者日库存模式
            $data["stock_num"] = 0;
        }
        if(empty($data["price"])){    //价格
            $data["plat_price"] = "";
            $data["settle_price"] = "";
            $data["market_price"] = "";
        }else{
            $data["plat_price"] = $data["price"]["plat_price"];
            $data["settle_price"] = $data["price"]["settle_price"];
            $data["market_price"] = $data["price"]["market_price"];
        }
        if(empty($data["date"])){
            $data["date"] = [];
        }
        return $data;
    }

    //有效期 tab2 数据接收 002
    private function rulesSetIndateData()
    {
        $gain = ['price_type', 'usable_date', 'begin_date','end_date','disabled_date','stock_type','stock_num', 'plat_price', 'settle_price','market_price','refund','refund_info','online_type','offline_type','on_time','off_time'];
        $data = Request::instance()->only($gain, 'post');//        $data = input('post.');
        //有效期时间段没接收 online_time
        if (empty($data["usable_date"])) {
            $data["usable_date"] = ""; //可以用日期
        }
        if (empty($data["disabled_date"])) {
            $data["disabled_date"] = ""; //不可用日期
        }
        if(empty($data["refund_info"])){
            $data["refund_info"] = "";
        }
//        if(empty($data["online_time"][0])){
//            $data["begin_date"] = "";
//        }else{
//            $data["begin_date"] = $data["online_time"][0];//有效期开始时间
//        }
//        if(empty($data["online_time"][1])){
//            $data["end_date"] = "";
//        }else{
//            $data["end_date"] = $data["online_time"][1];//有效期结束时间
//        }
        if($data["stock_type"] == 1){    //无限库存
            $data["stock_num"] = 0;
        }
        $data["usable_date"] = json_encode($data["usable_date"]);
        $data["disabled_date"] = json_encode($data["disabled_date"]);
        $data["refund_info"] = json_encode($data["refund_info"]);
        return $data;
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

    //后置方法 步骤操作结束后完成的事
    private function endOperation($goodsCode,$state){
        $this->lastEditTime($goodsCode);
        $tab = db('goods_create')->where(array("goods_code" => $goodsCode))->value('tab');
        if($tab !== null && $tab < 2){
            //更新tab
            if($state == 1){
                if ($tab < 1) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 1));
                };
            }else if($state == 2){
                if ($tab < 2) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 2));
                };
            }
        }
    }

    //更改商品保存状态(价格日历) 从已编辑到保存 0 - 1
    private function saveGoodsType($goodsCode){
        $where = [
            "code"        => $goodsCode,
            'is_del'      =>  ['<>',"1"]  //未删除
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if($res && $res["check_type"] == 0){
            $calendarType = db('ticket_calendar')->field("id")->where(array("goods_code" => $goodsCode))->find();
            if($calendarType){
                db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>1));
            }
        }
    }

    //更改商品保存状态（有效期）
    private function saveGoodsTypeYx($goodsCode){
        $where = [
            "code"        => $goodsCode,
            'is_del'      =>  ['<>',"1"]  //未删除
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if($res && $res["check_type"] == 0){
            $indateType = db('ticket_indate')->field("id")->where(array("goods_code" => $goodsCode))->find();
            if($indateType){
                db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>1));
            }
        }
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

}