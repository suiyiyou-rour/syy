<?php
namespace app\weixin\controller;
use app\common\controller\WeixinBase;
class Index extends WeixinBase
{
    //首页列表
    public function index()
    {
        $where = goodsOnlineWhere();       //上线条件
        $where["level"] = ["<>","4"];   //排除轮播
        $res = db('goods')
            ->field("code,show_title,head_img,goods_type")
            ->where($where)
            ->order("level desc,sales desc")
            ->limit(0,6)
            ->select();
        foreach ($res as &$k){
            $k["head_img"] = config("img_url").$k["head_img"];
        }
        return json(array("code"=>200,"list"=>$res));
    }

    //首页轮播图
    public function carousel(){
        $where = goodsOnlineWhere();      //上线条件
        $where["level"] = 4;             //等级最高轮播
        $res = db('goods')
            ->field("code,head_img,goods_type")
            ->where($where)
            ->order("level desc,sales desc")
            ->limit(0,3)
            ->select();
        foreach ($res as &$k){
            $k["head_img"] = config("img_url").$k["head_img"];
        }
        return json(array("code"=>200,"list"=>$res));
    }

    //跟团游 门票 景酒 列表
    public function goodsList(){
        $goods_type = input("post.goods_type");       //类型
        $show_title  = input("post.show_title");      //标题
        $page       = input("post.page");              //页码
        if(empty($goods_type)) $goods_type = 1;         //默认跟团
        if(empty($page)) $page = 1;

        $where                   =   goodsOnlineWhere();   //上线条件
        $where["goods_type"]    =   $goods_type;
        if($show_title) $where["show_title"] = ['like',"%".$show_title."%"];

        $count = db('goods')->where($where)->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" => 0,"list" => array())));
        }

        $res = db('goods')
            ->field("code,show_title,head_img,plat_price,settle_price,sales")
            ->where($where)
            ->order("level desc,sales desc")
            ->page($page,10)
            ->select();
        foreach ($res as &$k){
            $k["head_img"] = config("img_url").$k["head_img"];
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));


    }

}
