<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;

class Poster extends WeixinBase
{
    //显示海报列表
    public function showList(){
        $page           = input("post.page");              //页码
        if(empty($page)) $page = 1;

        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "a.type"         =>  "1",              //海报正常
            "b.check_type"  =>  "5",              //产品上线
            "b.is_del"       =>  ["<>","1"],      //未删除
            "b.on_time"      =>  ["<=",$date],
            "b.off_time"     =>  [">=",$date]
        ];

        $join  =  [['goods b','a.goods_code = b.code']];

        $count = db('poster')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $field = "a.goods_code,a.img_url";
        $res = db("poster")->alias("a")->join($join)->field($field)->where($where)->order("a.id desc")->page($page,6)->select();
        foreach ($res as &$k){
            $k["img_url"] = config("img_url").$k["img_url"];
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }

    //获取海报
    public function getImage(){
        $goodsCode = input("post.goods_code");
        if(!$goodsCode){
            return json(array("code" => 404,"msg" => "商品号不能为空"));
        }

        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date],
            "code"         =>   $goodsCode
        ];
        $goodsObj = db('goods')->field("goods_type")->where($where)->find();
        if(!$goodsObj){
            return json(array("code" => 405,"msg" => "商品找不到或者没有上线"));
        }

        $posterObj = db('poster')->field("img_url")->where(array("goods_code" => $goodsCode , "type" => 1))->find();
        if(!$posterObj){
            return json(array("code" => 405,"msg" => "没有这个产品的海报，或者被禁用了"));
        }

        $poster_url = "image"."/".$posterObj['img_url'];                           //原图路径
        $imgUrl = $this->getPic($poster_url,$goodsCode,$goodsObj["goods_type"]);   //获取生成后的海报

        return json(array("code" => 200,"data" => config("poster_url").$imgUrl));
    }

    //合成图片
    private function getPic($ImgPath='',$goodsCode='',$goods_type=''){
        //二维码生成
        $qCodePath = $this->qrcode($goodsCode,$goods_type);

        // 二维码合成
        $bigImagePath = './image/qrcode/back.jpg';
        qCode($bigImagePath,$qCodePath,$qCodePath,12,12);

        // 图片合成
        $savePath = "./image/qrcode/".$goodsCode.$goods_type.".jpg";
        lowerRight($ImgPath,$qCodePath,$savePath,20,20);
        return $savePath.'?t='.time();
    }


    //生成二维码
    private function qrcode($goodsCode='',$goods_type=''){
        $pid = getPid();//cookie

        switch ($goods_type) {
            case '1':
                $view = "route";
                break;
            case '2':
                $view = "ticket";
                break;
            case '3':
                $view = "hotel";
                break;
            default:
                $view = "route";
        }
        $save_path = './image/qrcode/';
        $web_path = './image/qrcode/';
        $qr_data = "http://wx.suiyiyou.net/#/$view/$goodsCode?pid=$pid";
        $qr_level = 'H';
        $qr_size = '4'; // 二维码图片大小
        $save_prefix = isset($_GET['save_prefix'])?$_GET['save_prefix']:'ZETA';
        $filename = createQRcode($save_path,$qr_data,$qr_level,$qr_size,$save_prefix);
        $pic = $web_path.$filename;

        // 缩略图
        thumb($pic,$pic,180,180);
        return $pic;
    }
}