<?php
namespace app\home\controller;
//use app\common\controller\HomeBase;

/**
 * 返利设置
 */
class Rebate extends Admin
{
    //经销商列表
    public function retailList(){
        $res = db('retail')->field("code,com_name")->where(array("type"=>3))->select();
        if(empty($res)){
            return json(array("code"=>405,"msg"=>"没有返利型经销商"));
        }
        return json(array("code"=>200,"data"=>$res));
    }

    //返利列表
    public function rebateList(){
        $page = input("post.page");                    //页码
        if(empty($page)){
            $page = 1;
        }

        $where = array();
        $goods_code = input("post.goods_code");         //产品编号
        if($goods_code){
            $where["a.goods_code"] = $goods_code;
        }

        $retail_code = input("post.retail_code");       //经销商编号
        if($retail_code){
            $where["a.retail_code"] = $retail_code;
        }

        $open = input("post.open");                     //开关
        if($open){
            $where["a.open"] = $open;
        }

        $join           =  [
            ['goods b','a.goods_code = b.code'],
            ['retail c','a.retail_code = c.code'],
            ['sp d','a.sp_code = d.code']
        ];
        $field       =  "a.*,b.show_title,c.com_name as jxs_name,d.com_name";

        $count = db('goods_rebate')->alias("a")->join($join)->where($where)->count('a.id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count"=>0)));
        }

        $res = db('goods_rebate')->alias("a")->join($join)->field($field)->where($where)->order("a.id desc")->page($page,10)->select();
        foreach ($res as &$k){
            $k["update_time"] = date("Y-m-d H:i:s",$k["update_time"]);
        }
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data" => $output));
    }


    //添加
    public function add(){
        $data = input("post.");
        //todo 数据验证没写


        //产品验证上线验证
        $date = strtotime(date("Y-m-d", time()));
        $where = [
            "check_type"   =>  "5",             //上线
            "is_del"       =>  ["<>","1"],      //未删除
            "on_time"      =>  ["<=",$date],
            "off_time"     =>  [">=",$date]
        ];
        $res = db('goods')->field("sp_code")->where($where)->find();
        if(!$res){
            return json(array("code" => 403,"msg" => "要上线产品才能添加返佣"));
        }
        $data["sp_code"] = $res["sp_code"];

        //验证重复添加
        $checkWhere["goods_code"]    =   $data["goods_code"];
        $checkWhere["retail_code"]   =   $data["retail_code"];
        $check = db('goods_rebate')->where($checkWhere)->find();
        if($check){
            return json(array("code" => 403,"msg" => "这个商品已经添加过这个经销商了"));
        }

        $view = model('Rebate');
        $view->data($data);
        try{
            $view->allowField(true)->save();// 过滤post数组中的非数据表字段数据
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200));
    }

    //修改
    public function alter(){
        $id = input("post.id");
        if(empty($id)){
            return json(array("code" => 404,"msg" => "标识不能为空"));
        }
        $check = db('goods_rebate')->where(array("id"=>$id))->find();
        if(!$check){
            return json(array("code" => 403,"msg" => "没有这个这条id"));
        }
        $data["money"]          =  input("post.money");
        $data["child_money"]    =  input("post.child_money");
        $data["remark"]         =  input("post.remark");
        $data["open"]           =  input("post.open");
        //todo 验证

        $view = model('Rebate');
        try{
            $view->save($data,['id' => $id]);
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200));
    }
}