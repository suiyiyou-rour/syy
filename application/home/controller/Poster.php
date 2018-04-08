<?php
namespace app\home\controller;
use app\common\controller\HomeBase;
class Poster extends HomeBase
{
    /**
     * 获取海报列表
     */
    public function getPosterList(){
        // 参数设置
        $page = input('post.page');
        $type = input('post.type');
        $code = input('post.code');
        $name = input('post.name');
        $userType = getSpType();
        
        $where=array();
        // 条件重构
        $userType?'':$where['syy_goods.sp_code']=getSpCode();
        empty($type)?'':$where['syy_goods.goods_type']=$type;
        empty($code)?'':$where['syy_poster.goods_code']=$code;
        empty($name)?'':$where['syy_goods.show_title']=['like',"%{$name}%"];
        $page = empty($page)?0:($page-1)*15;
        // 查询
        $count = db('poster')
                ->join('syy_goods','syy_goods.code=syy_poster.goods_code')
                ->where($where)
                ->where('syy_poster.type','neq',3)
                ->count();
        
        $data = db('poster')
                ->field('syy_poster.*,syy_goods.show_title')
                ->join('syy_goods','syy_goods.code=syy_poster.goods_code')
                ->where($where)
                ->where('syy_poster.type','neq',3)
                ->limit($page.',15')
                ->select();
        //返回
        return \json(array('code' => 200 , 'data' => $data , 'count' => $count));
    }

    /**
     * 获取商品列表
     */
    public function getGoodList(){
        $userType = getSpType();
        
        $where = ['is_del'=> 0];
        $userType ? '' : $where['sp_code'] = getSpCode();
        // 超管全部获取  个人拿自己的
        if($userType){
            $notIn = db('poster')
                    ->field('goods_code')
                    ->select();
        }else{
            $notIn = db('poster')
                    ->field('syy_poster.goods_code')
                    ->join('syy_goods','syy_goods.code=syy_poster.goods_code')
                    ->where(['syy_poster.type'=> ['neq'=>3]])
                    ->select();
        }
        // $notIn = $userType ?db('poster')->field('goods_code')->select():db('poster')->field('syy_poster.goods_code')->join('syy_goods','syy_goods.code=syy_poster.goods_code')->where(['syy_poster.type'=> ['neq'=>3]])->select();
        $notInData = [];
        if(!empty($notIn)){
            foreach($notIn as $k => $v){
                $notInData[] = $v['goods_code'];
            }
        }
        $data = db('goods')->field('code,show_title')->where('check_type','in','2,3,5,6')->where('code','not in',$notInData)->where($where)->select();
        return \json(array('code' => 200 , 'data' => $data));
    }

    /**
     * 添加海报
     */
    public function addPoster(){
        $code = input('post.code');
        $imagePath = input('post.path');
        
        if(empty($code) || empty($imagePath)){
            return \json(array('code' => 404 , 'msg' => '参数缺失！'));
        }

        $data = db('poster')->where(array('goods_code' => $code))->select();
        // 商品编号存在 更新  不存在 插入
        if(empty($data)){
            $result = db('poster')->insert(array('goods_code' => $code ,'img_url' => $imagePath , 'type' => 1));
        }else{
            $result = db('poster')->where(array('goods_code' => $code))->update(array('goods_code' => $code ,'img_url' => $imagePath , 'type' => 1));
        }
        
        if(!$result){
            return \json(array('code' => 404 , 'msg' => '添加失败！'));
        }
        return \json(array('code' => 200 , 'msg' => '添加成功'));
    }

    /**
     * 海报 0 禁用， 1 启用 
     */
    public function cPoster(){
        $auth = getSpType();
        $posterId = input('post.posterId');
        $state = input('post.state');

        // 校验参数
        if(empty($posterId)){
            return \json(array('code' => 404 , 'msg' => '参数缺失！'));
        }
        // 启用 禁用判断
        if($state == 0){
            $type = 0;
            $str  = '禁用';
            if($auth){ // 超管禁用
                $type = 2;
            }
        }else{
            $type = 1;
            $str = '启用';
            if(!$auth){ // 非超管检验权限
                $pass = db('poster')
                        ->where(['id' => $posterId])
                        ->find();
                if($pass['type']==2){
                    return \json(array('code' => 404 , 'msg' => '该海报已被管理员禁用！请联系管理员'));
                }
            }
        }
        // 状态更新
        $result = db('poster')->where(['id' => $posterId])->update(['type' => $type]);
        // 结果返回
        if(!$result){
            return \json(array('code' => 404 , 'msg' => $str.'失败'));
        }
        return \json(array('code' => 200 , 'msg' => $str.'成功','data' => $type));
    }
    /**
     * 海报删除
     */
    public function dPoster(){
        $posterId = input('post.posterId');
        $result = db('poster')->where(['id' => $posterId])->update(['type' => 3]);

        if(!$result){
            return \json(array('code' => 404 , 'msg' => '删除失败'));
        }
        return \json(array('code' => 200 , 'msg' => '删除成功'));
    }

    /**
     * 海报照片异步上传
     */
    public function uploadPoter(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
                
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!$file){
            return \json(array('code'=>404,'msg' => '上传失败！'));
        }
        $info = $file->validate(array('size' => 5242880,'ext' => 'jpg,png,gif'))->move(ROOT_PATH . 'public' . DS .'image'.DS. 'poster');

        if(!$info){ 
            return \json(array('code'=>404,'msg' => $file->getError()));
        }
        return \json(array('code'=>200,'msg' => '上传成功' , 'data' => array('name' =>'poster'.DS.$info->getSaveName()) ));
    }

    /**
     * 
     */
}   