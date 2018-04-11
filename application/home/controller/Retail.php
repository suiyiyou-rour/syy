<?php
// 分销商管理
namespace app\home\controller;
use  app\common\controller\HomeBase;
class Retail extends HomeBase
{
    public function __construct(){
        parent::__construct();
        if(!getSpType()){
            echo json(array("code" => 404, "msg" => "只有超级管理才有权限"));
            die;
        }
    }

    /**
     *  获取分销商列表 
     */
    public function getRetailList(){
        $page = input('post.page')?1:input('post.page');
        $page = ($page-1)*10;
        // 查找申请的和通过的
        $count = db('retail')->where('type','in','0,1')->count();
        $retailData = db('retail')->where('type','in','0,1')->limit($page.',10')->order('reg_time','desc')->select();
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
        db('retail_money')->insert(['retail_code' => $code]);
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
}