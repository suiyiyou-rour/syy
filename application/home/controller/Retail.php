<?php
namespace app\home\controller;
use  app\common\controller\HomeBase;

/**
 * 经销商管理
 */
class Retail extends HomeBase
{
    public function __construct(){
        parent::__construct();
        if(!getSpType()){
            echo json_encode(array("code" => 404, "msg" => "只有超级管理才有权限"));
            die;
        }
    }

    /**
     *  获取分销商列表 
     */
    public function getRetailList(){
        $page = input("post.page");                   //页码
        if(empty($page)) $page = 1;

        $join = [['retail_money b','a.code = b.retail_code','LEFT']];
        // 查找申请的和通过的
        $count = db('retail')->alias("a")->join($join)->where('a.type','in','1,3')->count();
        if(!$count){
            return \json(array("code" => 200,"data" => array(),"count" => 0 ));
        }

        $retailData = db('retail')->alias("a")->join($join)->where('a.type','in','1,3')->page($page,10)->order('a.reg_time','desc')->select();
        return \json(array('code' => 200 ,'data' => $retailData ,'count' => $count));
    }

    /**
     * 分销通过 不通过
     */
    public function retailPass(){
        $code = input('post.code');
        $type = input('post.state');

        $check = $type == 0?2:1;

        if(empty($code)){
            return \json(array('code' => 404 ,'msg' => '出错啦！请重新刷新'));
        }

        $res = db('retail')->where(['code' => $code])->update(['check' =>  $check ]);
        if(!$res){
            return \json(array('code' => 404 ,'msg' => '操作失败！'));
        }

        try{
            db('retail_money')->insert(['retail_code' => $code]);
        } catch (\Exception $e) {
            return json(array("code" => 403, "msg" => "金额表写入失败，请联系管理员"));
        }

        return \json(array('code' => 200 ,'msg' => '操作成功！'));
    }

    /**
     * 分销禁用 启用
     */
    public function retailOpen(){
        $code = input('post.code');
        $type = input('post.state');

        $check = $type == 0?0:1;

        if(empty($code)){
            return \json(array('code' => 404 ,'msg' => '出错啦！请重新刷新'));
        }

        $res = db('retail')->where(['code' => $code])->update(['open' => $check ]);
        if(!$res){
            return \json(array('code' => 404 ,'msg' => '操作失败！'));
        }
        return \json(array('code' => 200 ,'msg' => '操作成功！'));
    }

    /**
     * 查看详情
     */
    public function detail(){
        $code = input('post.code');
        if(empty($code)){
            return \json(array('code' => 404 ,'msg' => '出错啦！请重新刷新'));
        }

        $res = db('retail')->field('file')->where(['code' => $code])->find();
        return \json(array('code' => 200 ,'data' => $res));
    }

    /**
     *  分销商类型
     */
    public function typeChange(){
        $code = input('post.code');
        $type = input('post.type');
        if(empty($code)){
            return \json(array('code' => 404 ,'msg' => '出错啦！请重新刷新'));
        }

        $res = db('retail')->where(['code' => $code])->update(['type' => $type]);
        if(!$res){
            return \json(array('code' => 404 ,'data' => '修改失败！'));
        }
        return \json(array('code' => 200 ,'data' => '修改成功！'));
    } 
}