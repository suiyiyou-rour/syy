<?php
namespace app\home\controller;
use app\common\controller\HomeBase;
use think\console\Input;

class Scenery extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    //商品添加
    public function add(){
        $this->dispatcher();
    }

    //商品添加显示
    public function show(){
        $this->dispatcher();
    }

    //商品页面选择显示
    public function option(){
        $this->dispatcher();
    }

    //列表显示
    public function goodsList(){
        $where["a.goods_type"] = "3";       //景酒

        $show_title = input("post.show_title");         //随意游产品名称
        if($show_title){
            $where["a.show_title"] = ['like',"%".$show_title."%"];
        }

        $code = input("post.code");         //产品编号
        if($code){
            $where["a.code"] = $code;
        }

        $check_type = input("post.check_type");        //审核状态
        if($check_type){    //0全查
            $where["a.check_type"] = $check_type;
        }else{
            $where["a.check_type"] = ['<>',0];
        }

        if(session("sp.type") != 1){                    //超级管理
            $where["a.sp_code"] = session("sp.code");   //供应商
        }

        $page = input("post.page");        //页码
        if(empty($page)){
            $page = 1;
        }

        $join = [['goods_scenery b','a.code = b.goods_code']];
        $goodsField = "a.code,a.show_title,a.on_time,a.off_time,a.check_type";
        $sceneryField = "b.hotel_code";
        $allField = $goodsField.','.$sceneryField;

        $count = db('goods')->alias("a")->where($where)->join($join)->count('a.id');
        if(!$count){
            echo json_encode(array("code" => 200,"data" => array("count" =>0)));
            return;
        }
//
        $res = db('goods')->alias("a")->field($allField)->where($where)->join($join)->order('a.id desc')->page($page,10)->select();
        foreach ($res as &$k){
            $end_date  = db('scenery_calendar')->where(array("goods_code" => $k["code"]))->max('date');
            if(empty($end_date)){
                $k["end_date"] = "";
            }else{
                $k["end_date"] = $end_date;
            }
            $k["hotel_code"] = json_decode($k["hotel_code"],true);
            $k["end_date"] = date("Y-m-d",$k["end_date"]);
            $k["on_time"] = date("Y-m-d",$k["on_time"]);
            $k["off_time"] = date("Y-m-d",$k["off_time"]);
        }

        $output["list"]  =  $res;
        $output["count"]  =  $count;
        echo json_encode(array("code" => 200,"data" => $output));
        return;

    }

    //商品删除
    public function del(){
        $goodsCode = input('post.goodsCode');
        if(empty($goodsCode)){
            return json(array("code" => 404,"msg" => "参数错误404"));
        }
        $where = [
            "code"        =>  $goodsCode,
            "sp_code"     =>  getSpCode(),         // 供应商code
            "is_del"      =>  ["<>","1"],          //未删除
            "goods_type" =>   '3'                  //景酒
        ];
        $res = db('goods')->field("check_type")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "商品号找不到或者这条商品不属于你"));
        }
        if($res["check_type"] == 0){
            db('goods')->where(array("code"=>$goodsCode))->delete();
            db('goods_scenery')->where(array("goods_code"=>$goodsCode))->delete();
            db('goods_supply')->where(array("goods_code"=>$goodsCode))->delete();
            db('goods_create')->where(array("goods_code"=>$goodsCode))->delete();
        }else{
            $output = db('goods')->where(array("code" => $goodsCode))->update(array("is_del"=>1));
            if($output === false){
                return json(array("code" => 403,"msg" => "删除失败，请稍后再试一次"));
            }
        }
        return json(array('code' => 200,'msg' => '删除成功'));
    }

    //分配
    private function dispatcher(){
        $request = request();
        $controller  = ucfirst($request->controller());
        $action  = ucfirst($request->action());
        $state = input('state');
        if($state == null || $state == ""){
            echo json_encode(array("code" => 404,"msg" => "参数错误404"));
            return;
        }
        $res = \think\Loader::model('Goods','logic')->dispatcher($controller,$action,$state);
        echo json_encode($res);
        return;
    }


    //添加酒店
    public function addHotel(){
        $data = input("post.");
        //图片数组
        if (empty($data["fileList"] || empty($data["begin_time"]) || empty($data["end_time"]))) {
            return array("code" => 404, "msg" => "上传参数错误");
        }

        //图片存入处理
        $data["image"] = $this->imageSetStr($data["fileList"]);         //图片数组
        $data["begin_time"]     = json_encode($data["begin_time"]);     //入住时间
        $data["end_time"]       = json_encode($data["end_time"]);        //退房时间
        unset($data["fileList"]);

        $data["code"]       =  mt_rand(100000,999999);//编号
        $data["sp_code"]    =  session("sp.code");  //供应商编号

        //数据验证
        $validate = new \app\home\validate\Hotel();
        $result = $validate->scene('add')->check($data);
        if (true !== $result) {
            return json_encode(array("code" => 405, "msg" => $validate->getError()));
        }

        //数据保存
        $res = db("hotel")->insert($data);
//        $hotel = model('Hotel');
//        $res = $hotel->allowField(true)->save();
        if(!$res){
            return json_encode(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json_encode(array("code" => 200,"data"=>array("code"=>$data["code"])));

    }

    //添加景点
    public function addView(){
        $data = input("post.");
        //图片数组处理
        if (empty($data["fileList"])) {
            return array("code" => 404, "msg" => "上传参数错误");
        }
        //图片存入处理
        $data["image"] = $this->imageSetStr($data["fileList"]);
        $data["ticket_type"]    = json_encode($data["ticket_type"]);     //票种类型
        unset($data["fileList"]);

        $data["code"]       =  mt_rand(100000,999999);//编号
        $data["sp_code"]    =  session("sp.code");  //供应商编号

        //数据验证
        $validate = new \app\home\validate\View();
        $result = $validate->scene('add')->check($data);
        if (true !== $result) {
            return json_encode(array("code" => 405, "msg" => $validate->getError()));
        }

        $view = model('View');
        $view->data($data);
        $res = $view->allowField(true)->save();// 过滤post数组中的非数据表字段数据
        if(!$res){
            return json_encode(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json_encode(array("code" => 200,"data"=>array("code"=>$data["code"])));
    }

    //添加餐饮
    public function addMeal(){
        $data = input("post.");
        //图片数组处理
        if (empty($data["fileList"])) {
            return array("code" => 404, "msg" => "上传参数错误");
        }
        $data["image"] = $this->imageSetStr($data["fileList"]); //图片存入处理
        $data["instruction"]    = json_encode($data["instruction"]);     //使用说明
        unset($data["fileList"]);

        $data["code"]       =  mt_rand(100000,999999);//编号
        $data["sp_code"]    =  session("sp.code");  //供应商编号

        //数据验证
        $validate = new \app\home\validate\Meal();
        $result = $validate->scene('add')->check($data);
        if (true !== $result) {
            return json_encode(array("code" => 405, "msg" => $validate->getError()));
        }

        $meal = model('meal');
        $meal->data($data);
        $res = $meal->allowField(true)->save();// 过滤post数组中的非数据表字段数据
        if(!$res){
            return json_encode(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json_encode(array("code" => 200,"data"=>array("code"=>$data["code"])));
    }

    //添加车队
    public function addVehicle(){
        $data = input("post.");
        //图片数组处理
        if (empty($data["fileList"])) {
            return array("code" => 404, "msg" => "上传参数错误");
        }
        $data["image"] = $this->imageSetStr($data["fileList"]); //图片存入处理
        $data["instruction"]    = json_encode($data["instruction"]);     //使用说明
        unset($data["fileList"]);

        $data["code"]       =  mt_rand(100000,999999);//编号
        $data["sp_code"]    =  session("sp.code");  //供应商编号

        //数据验证
        $validate = new \app\home\validate\Vehicle();
        $result = $validate->scene('add')->check($data);
        if (true !== $result) {
            return json_encode(array("code" => 405, "msg" => $validate->getError()));
        }

        $vehicle = model('vehicle');
        $vehicle->data($data);
        $res = $vehicle->allowField(true)->save();// 过滤post数组中的非数据表字段数据
        if(!$res){
            return json_encode(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json_encode(array("code" => 200,"data"=>array("code"=>$data["code"])));
    }


    //异步上传图片
    public function imageUpload()
    {
        $type = input("post.goodsType");
        if(empty($type)){
            return json_encode(array("code" => 404, "msg" => "参数错误"));
        }
        $checkArray = array('hotel','view','meal','vehicle');
        if(!in_array($type,$checkArray)) {
            return json_encode(array("code" => 403, "msg" => "参数错误"));
        }

        $imgLimit = config("imageUpLimit");
        $file = request()->file('file');
        if (empty($file)) {
            return json_encode(array("code" => 404, "msg" => "参数错误"));
        }
        $info = $file->validate($imgLimit)->move(ROOT_PATH . 'public' . DS . 'image' . DS . $type);
        if ($info) {
            return json_encode(array("code" => 200, "data" => array("name" => $type . DS . $info->getSaveName())));
        } else {
            return json_encode(array("code" => 403, "msg" => $file->getError()));
        }
    }

    //图片删除
    public function imageDel(){
        $type = input("post.goodsType");
        if(empty($type)){
            return json_encode(array("code" => 404, "msg" => "参数错误"));
        }
        $checkArray = array('hotel','view','meal','vehicle');
        if(!in_array($type,$checkArray)) {
            return json_encode(array("code" => 403, "msg" => "参数错误"));
        }

        $name = input("post.name");
        return json_encode(array("code" => 200, "data" => $name));
    }

    //处理图片数组(前端对象转字符串)
    private function imageSetStr($imageObj){
        $fileList = objSetArray($imageObj);
        $imageArray = array();
        foreach ($fileList as $k) {
            $imageArray[] = $k["name"];
        }
        return json_encode($imageArray);
    }

}