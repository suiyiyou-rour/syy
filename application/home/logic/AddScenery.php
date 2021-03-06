<?php
namespace app\home\logic;
use think\Request;

class AddScenery
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
                return array("code" => 412, "msg" => "添加商品，商品号不能为空");
            }
            //是否有写入状态检测
            $res = $this->checkGoodsType($goodsCode);
            if ($res !== true) {
                return array("code" => 405, "msg" => $res);
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
            case '12':
                //删除价格日历
                $output = $this->delcalendar();
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
        if(getSpType()) return array("code" => 403, "msg" => "超级管理员不能添加新产品以及不能编辑第一页");
        //数据验证
        $gain = ['contact_code', 'add_type', 'settlement_type','inside_code'];
        $data = Request::instance()->only($gain, 'post');
        $validate = new \app\home\validate\Scenery();
        $result = $validate->scene('addBasicInfo')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }

        //主表添加数据
        $goodsData["sp_code"]            =   getSpCode();               //供应商编号
        $goodsData["contact_code"]      =   $data["contact_code"];   //合同编码  （主）必须
        $goodsData["inside_code"]       =   $data["inside_code"];    //供应商内部编号  （主）必须
        $goodsData["last_edit_time"]    =   time();                   //最后编辑时间

        //副表添加数据
        $sceneryData["add_type"]            =   $data["add_type"];          //添加产品类型 0手动
        $sceneryData["settlement_type"]    =   $data["settlement_type"];   //结算模式 0底价模式

        //有商品号（更新）
        $goodsCode = input('post.goodsCode');
        if ($goodsCode) {
            //是否有写入状态检测
            $checkRes = $this->checkGoodsType($goodsCode);
            if ($checkRes !== true) {
                return array("code" => 405, "msg" => $checkRes);
            }
            try{
                db('goods')->where(array("code" => $goodsCode))->update($goodsData);
                db('goods_scenery')->where(array("goods_code" => $goodsCode))->update($sceneryData);
            } catch (\Exception $e) {
                return array("code" => 403, "msg" => "保存出错，请稍后再试");
            }
            return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
        }

        //没有商品号（保存）
        $hash = input('post.hash');
        if (!checkFromHash($hash)) {
            return array("code" => 405, "msg" => "您表单提交速度过快，请3秒后重试。");
        }
        $goodsCode = createGoodsCode("s");                  //产品编号
        //主表添加数据
        $goodsData["code"]          =   $goodsCode;        //产品编号
        $goodsData["create_time"]   =   time();            //创建时间
        $goodsData["goods_type"]    =   3;                 //酒景
        //副表
        $sceneryData["goods_code"]   =   $goodsCode;        //产品编号
        //补充表
        $supplyData["goods_code"]   =   $goodsCode;         //产品编号

        try{
            db('goods')->insert($goodsData);            //主表
            db('goods_scenery')->insert($sceneryData); //副表
            db('goods_supply')->insert($supplyData);   //补充表
            db('goods_create')->insert(array('goods_code' => $goodsCode));  //插入页码表
            $this->delCreateRear(); //删除多余的未保存商品
        } catch (\Exception $e) {
            return array("code" => 403, "msg" => "数据保存出错，请再试一次");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //打包内容 1
    public function packDetails(){
        $goodsCode = input('post.goodsCode');
        $data = $this->packDetailsData();       //数据接收
        $validate = new \app\home\validate\Scenery();
        $result = $validate->scene('addPackDetails')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        try{
            db('goods_scenery')->where(array("goods_code" => $goodsCode))->update($data);
        } catch (\Exception $e) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    // 套餐信息 2
    public function packageInfo(){
        $goodsCode = input('post.goodsCode');
        //数据接收 处理
        $gain = ['hotel_day', 'apply_man_num', 'apply_child_num', 'show_market_price','trip_info'];
        $data = Request::instance()->only($gain, 'post');
        if(empty($data["trip_info"])){
            $data["trip_info"] = "";
        }
        $data["trip_info"] = json_encode($data["trip_info"]);
        //数据验证
        $validate = new \app\home\validate\Scenery();
        $result = $validate->scene('addPackageInfo')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        try{
            db('goods_scenery')->where(array("goods_code" => $goodsCode))->update($data);
        } catch (\Exception $e) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //价格库存 3
    public function ratesInventory(){
        $goodsCode = input('post.goodsCode');
        $dateArray = input('post.date/a');  //日期数组
        $gain = [ 'plat_price', 'settle_price','stock_is_open', 'stock_num','reserve_is_open','reserve_time','reserve_num'];
        $data = Request::instance()->only($gain, 'post');
        //todo 数据没验证
        $bol = true;
        $error = ""; //错误信息
        foreach ($dateArray as $k) {
            $data["date"] = strtotime($k);//时间戳;
            $res = db('scenery_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->find();
            if ($res) {//修改状态
                try{
                    db('scenery_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->update($data);
                } catch (\Exception $e) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            } else {//添加状态
                try{
                    $data["goods_code"] = $goodsCode;
                    db('scenery_calendar')->insert($data);
                } catch (\Exception $e) {
                    $bol = false;
                    $error .= $data["date"] . ",";
                }
            }
        }
        if (!$bol) {
            return array("code" => 403, "msg" => "保存日期错误，错误的日期为" . $error . "请再试一次或者联系管理员");
        }
        $this->saveGoodsUpdate($goodsCode); //最低价格更新到主表
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //商品设置 4
    public function productSet(){
        $goodsCode = input('post.goodsCode');
        $data = $this->productSetData();
        $validate = new \app\home\validate\Scenery();//数据验证
        $result = $validate->scene('addProductSet')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }
        //主表添加数据
        $goodsData["advance_time"]      =   $data["advance_time"]; //提前预定时间   （主）必须
        //副表添加数据
        unset($data["advance_time"]);

        try{
            db('goods')->where(array("code" => $goodsCode))->update($goodsData);
            db('goods_scenery')->where(array("goods_code" => $goodsCode))->update($data);
        } catch (\Exception $e) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //商品信息 5
    public function productInfo(){
        $goodsCode = input('post.goodsCode');
        $data = $this->productInfoData();
        //图片数组单独处理
        if (empty($data["fileList"])) {
            return array("code" => 404, "msg" => "图片信息上传参数错误");
        }
        $fileList = objSetArray($data["fileList"]);
        if (empty($fileList[0]["response"]["data"]["name"])) {
            return array("code" => 404, "msg" => "图片信息上传参数错误:首图");
        }
        $imageArray = array();
        foreach ($fileList as $k) {
            $imageArray[] = $k["response"]["data"]["name"];
        }

        //普通数据验证
        $validate = new \app\home\validate\Scenery();
        $result = $validate->scene('addProductInfo')->check($data);
        if (true !== $result) {
            return array("code" => 405, "msg" => $validate->getError());
        }

        //主表
        $goodsData["head_img"]      =  $fileList[0]["response"]["data"]["name"];  //首图
        $goodsData["show_title"]    =  $data["show_title"];         //外部显示标题
        $goodsData["online_type"]   =  2;                            //默认指定日期上下线
        $goodsData["offline_type"]  =  2;                            //默认指定下架时间
        $goodsData["on_time"]       =  $data["on_time"];            //上线日期
        $goodsData["off_time"]      =  $data["off_time"];           //下线日期
        //副表  'recommend_account','class_label'
        $sceneryData["recommend_account"]    =  $data["recommend_account"];        //推荐理由
        $sceneryData["class_label"]           =  $data["class_label"];              //类目标签
        //补充表
        $supplyData["image"] = json_encode($imageArray);             //图片数组

        try{
            db('goods')->where(array("code" => $goodsCode))->update($goodsData);
            db('goods_supply')->where(array("goods_code" => $goodsCode))->update($supplyData);
            db('goods_scenery')->where(array("goods_code" => $goodsCode))->update($sceneryData);
            $this->saveGoodsUpdate($goodsCode);     //价格日历字段 更新主表
            db('goods')->where(array("code" => $goodsCode))->update(array("check_type"=>1));
        } catch (\Exception $e) {
            return array("code" => 403, "msg" => "保存出错，请稍后再试");
        }
        return array("code" => 200, "data" => array("goodsCode" => $goodsCode));
    }

    //价格日历删除 12
    public function delcalendar(){
        $postData = input('post.');
        $goodsCode = $postData["goodsCode"];
        if(empty($goodsCode)){
            return array("code" => 412, "msg" => "商品号不能为空");
        }

        $dateArray = $postData["date"];
        if (empty($dateArray)) {
            return array("code" => 404, "msg" => "上传参数不能为空");
        }

        foreach ($dateArray as $k) {
            $data["date"] = strtotime($k);//时间戳;
            db('scenery_calendar')->where(array("goods_code" => $goodsCode, "date" => $data["date"]))->delete();
        }
        return array("code" => 200);
    }

    //----  图片处理

    //异步上传图片 100
    private function imageUpload()
    {
        //todo 商品号
        $goodsCode = input('post.goodsCode');
//        return array("code" => 404,"msg" => $goodsCode);
        $imgLimit = config("imageUpLimit");
        $file = request()->file('file');
        if (empty($file)) {
            return array("code" => 404, "msg" => "参数错误");
        }
        $info = $file->validate($imgLimit)->move(ROOT_PATH . 'public' . DS . 'image' . DS . 'scenery');
        if ($info) {
            return array("code" => 200, "data" => array("name" => 'scenery' . DS . $info->getSaveName(), "goodsCode" => $goodsCode));
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

    //----  数据接收

    //打包内容数据 1
    private function packDetailsData(){
        $gain = ['hotel_code', 'view_code', 'meal_code','vehicle_code'];
        $data = Request::instance()->only($gain, 'post');
        if(empty($data['hotel_code'])){
            $data['hotel_code'] = "";
        }
        if(empty($data['view_code'])){
            $data['view_code'] = "";
        }
        if(empty($data['meal_code'])){
            $data['meal_code'] = "";
        }
        if(empty($data['vehicle_code'])){
            $data['vehicle_code'] = "";
        }
        $data["hotel_code"] = json_encode($data["hotel_code"]);
        $data["view_code"] = json_encode($data["view_code"]);
        $data["meal_code"] = json_encode($data["meal_code"]);
        $data["vehicle_code"] = json_encode($data["vehicle_code"]);
        return $data;

    }

    //商品设置数据 4
    private function productSetData(){
        $gain = ['advance_time','stock_confirm_time','min_buy_num','max_buy_is_open','max_buy_num', 'refund','refund_info','contact_info','play_people_info','friendship_hints','book_notice'];
        $data = Request::instance()->only($gain, 'post');
        if(empty($data['stock_confirm_time'])){
            $data['stock_confirm_time'] = "";
        }
        if(empty($data['contact_info'])){
            $data['contact_info'] = "";
        }
        if(empty($data['play_people_info'])){
            $data['play_people_info'] = "";
        }
        $data["stock_confirm_time"] = json_encode($data["stock_confirm_time"]);
        $data["contact_info"] = json_encode($data["contact_info"]);
        $data["play_people_info"] = json_encode($data["play_people_info"]);
        return $data;
    }

    //商品信息数据 5
    private function productInfoData(){
        $gain =  ['show_title', 'on_time', 'off_time','recommend_account','class_label','fileList'];
        $data = Request::instance()->only($gain, 'post');
        if(empty($data['recommend_account'])){
            $data['recommend_account'] = "";
        }
        if(empty($data['class_label'])){
            $data['class_label'] = "";
        }
        $data["recommend_account"] = $data["recommend_account"];
        $data["class_label"] = json_encode($data["class_label"]);
        return $data;

    }

    //----  辅助函数

    //处理图片数组(前端对象转字符串)
    private function imageSetStr($imageObj){
        $fileList = objSetArray($imageObj);
        $imageArray = array();
        foreach ($fileList as $k) {
            $imageArray[] = $k["name"];
        }
        return json_encode($imageArray);
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
        $array = array(0,1,4,6);
        if(!in_array($res["check_type"],$array)){
            return "商品不在编辑状态";
        }
        return true;
    }

    //后置方法 步骤操作结束后完成的事
    private function endOperation($goodsCode,$state){
        $this->lastEditTime($goodsCode);//更新时间
        $tab = db('goods_create')->where(array("goods_code" => $goodsCode))->value('tab');
        if($tab !== null && $tab < 5){
            //更新tab
            if($state == 1){
                if ($tab < 1) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 1));
                };
            }else if($state == 2){
                if ($tab < 2) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 2));
                };
            }else if($state == 3){
                if ($tab < 3) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 3));
                };
            }else if($state == 4){
                if ($tab < 4) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 4));
                };
            }else if($state == 5){
                if ($tab < 5) {
                    db('goods_create')->where(array("goods_code" => $goodsCode))->update(array("tab" => 5));
                };
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

    //删除多余的未保存商品
    private function delCreateRear(){
        $where = [
            "check_type"  =>  "0",        //制作中
            "goods_type"  =>  "3",        //景酒
            "sp_code"     =>  getSpCode(), //供应商code
            "is_del"      =>  ["<>","1"],  //未删除
        ];
        $count = db('goods')->where($where)->count("id");
        if($count > 5){
            $code = db('goods')->where($where)->order("last_edit_time asc")->value('code');
            if($code){
                db('goods')->where(array("code"=>$code))->delete();
                db('goods_scenery')->where(array("goods_code"=>$code))->delete();
                db('goods_supply')->where(array("goods_code"=>$code))->delete();
                db('goods_create')->where(array("goods_code"=>$code))->delete();
            }
        }
    }

    //商品保存主要需要更新字段 下线时间 展示平台价格 展示结算价格
    private function saveGoodsUpdate($goodsCode){
        $calendarField = "MIN(plat_price) as plat_price ,MAX(date) as deadline_date,MIN(settle_price) as settle_price";
        $data = db('scenery_calendar')->field($calendarField)->where(array("goods_code"=>$goodsCode))->find();
        db('goods')->where(array("code" => $goodsCode))->update($data);
    }

}