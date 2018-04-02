<?php
namespace app\home\controller;
use app\common\controller\HomeBase;
class Spcreate extends HomeBase
{
    /**
     *   供应商  凭证图片上传
     */
    public function spUpload(){
        
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        
        // 移动到框架应用根目录/public/uploads/ 目录下
        if(!$file){
            return \json(array('code'=>404,'msg' => '上传失败！'));
        }
        $info = $file->validate(array('size' => 5242880,'ext' => 'jpg,png,gif'))->move(ROOT_PATH . 'public' . DS .'image'.DS. 'spImage');

        if(!$info){ 
            return \json(array('code'=>404,'msg' => $file->getError()));
         }
         return \json(array('code'=>200,'msg' => '上传成功' , 'data' => array('name' =>'spImage'.DS.$info->getSaveName()) ));
    }

    /**
     * 供应商注册
     */
    public function spCreate(){
        




    }

    /**
     * 权限
     */
    public function getAuth(){
        $auth =  \think\Loader::model('Auth','logic');
        $res = $auth->spAuth();
        return \json(array('code' => 200 , 'data' => $res));
    }

    /**
     * 供应商 code 生成
     */
     private function getCode(){
        $code = cache('sp_code');
        $num = 1000000;
        if($code){
            $num = $code;
        }
        $num++;
        cache('sp_code',$num);
        return $num;
     }
}