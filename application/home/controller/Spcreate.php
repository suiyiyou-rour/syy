<?php
namespace app\home\controller;
use app\common\controller\HomeBase;
class Spcreate extends HomeBase
{
    public function __construct(){
        parent::__construct();
        if(!getSpType()){
            echo json_encode(array("code" => 404, "msg" => "只有超级管理才有权限"));
            die;
        }
    }
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
        $info = $file->validate(array('size' => 5242880,'ext' => 'jpg,png,gif,jpeg'))->move(ROOT_PATH . 'public' . DS .'image'.DS. 'spImage');

        if(!$info){ 
            return \json(array('code'=>404,'msg' => $file->getError()));
         }
         return \json(array('code'=>200,'msg' => '上传成功' , 'data' => array('name' =>'spImage'.DS.$info->getSaveName()) ));
    }

    /**
     * 重名验证
     */
    public function reName(){
        $name = input('post.name');
        $data = db('sp')->where(array('account_num' => $name))->find();
        if(!empty($data)){
            return \json(array("code" => 404, "msg" => '该账号已存在！'));
        }
        return \json(array("code" => 200, "msg" => '此账号可以使用！'));
    }

    /**
     * 供应商注册
     */
    public function spCreate(){
        $data = input('post.');
        $validate = \validate('Spcreate');
        $res = $validate->check($data);
        if (true !== $res) {
            return \json(array("code" => 404, "msg" => $validate->getError()));
        }
        // 权限字段查看
        if(empty($data['auth'])){
            return \json(array("code" => 404, "msg" => '权限不能为空'));
        }
       
        $user_code = $this->getCode();
        
        $insertData=array(
            'code'          => $user_code,
            'type'          => 2,
            'account_num'   => $data['account'],
            'pwd'           => md5($data['password']),
            'com_name'      => $data['company'],
            'province'      => '福建',
            'city'          => '福州',
            'address'       => $data['address'],
            'name'          => $data['name'],
            'mobile'        => $data['mobile'],
            'qq'            => 'xxx',
            'email'         => $data['email_address'],
            'open'          => 1,
            'reg_time'      => time(),
            'file'          => json_encode(array($data['y_image_path'],$data['z_image_path'],$data['s_image_path']))
        );

        $res1 = db('sp')->insert($insertData);
        
        $authData = [];
        foreach($data['auth'] as $k => $v){
            $authData[]=['user_code'=> $user_code,'fid' => $v];
        }
        $res2 = db('auth')->insertAll($authData);
        db('sp_money')->insert(['sp_code' => $user_code]);
        // 插入返回
        if($res1 && $res2){
            return \json(array("code" => 200, "msg" => '添加成功'));
        }else{
            return \json(array("code" => 404, "msg" => '添加失败'));
        }
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
     /**
      * 供应商列表
      */
      public function getSpList(){
        $auth = input('id');
        if(empty($auth)){
            return \json(array('code' => 404 , 'msg' => '无权限访问'));
        }
        $page = input('post.page');
        if(empty($page)){
            $page = 1;
        }
        $page = ($page-1)*10;
        $count = db('sp') ->where(array('type' => 2))->count();
        $data = db('sp')->field(['pwd','reg_time','login_time'],true)->where(array('type' => 2))->limit($page.',10')->select();
        $dataArr = [];
        // 数据重构
        foreach($data as $k =>$v){
            $imgPath = json_decode($v['file']);
            $temp = array(
                'code'              =>   $v['code'],
                'account'           =>   $v['account_num'],   //账号  
                'password'          =>   '',   //密码   
                'name'              =>   $v['name'],//供应商名字   
                'mobile'            =>   $v['mobile'],//供应商联系方式     
                'company'           =>   $v['com_name'],//公司名      
                'address'           =>   $v['address'],//地址     
                'y_image_path'      =>   $imgPath[0],//营业执照     
                'z_image_path'      =>   $imgPath[1],//组织机构代码     
                's_image_path'      =>   $imgPath[2],//税务登记证     
                'email_address'     =>   $v['email'],//邮箱  
                'open'              =>   $v['open'] 
            );

            $authData = db('auth')->field('fid')->where(array('user_code' => $v['code']))->select(); 
            $auth = [];
            foreach($authData as $key =>$val){
                $auth[] = $val['fid'];
            }
            $temp['auth'] = $auth;
            $dataArr[] = $temp;
        }
      
        return \json(array('code' => 200 , 'data' => array('count'=> $count,'list' => $dataArr)));
      }


      /**
       * 供应商启用
       */
      public function spOpen(){
            $spCode = input('post.code');
            $type = input('post.type');
            // open 1 开启 0 关闭
            $updateArr = ['open' => 1];
            $str = '开启';
            // $type 0 禁用 1 开启
            if($type == 0){
                $updateArr['open'] = 0;
                $str = '禁用';
            }

            $res = db('sp')->where(['code' => $spCode])->update($updateArr);
            if($res){
                return \json(array('code' => 200 , 'data' => $str.'成功！'));
            }
            return \json(array('code' => 404 , 'data' => $str.'失败！'));
      }
      /**
       * 编辑修改
       */
      public function spChange(){

            $data = input('post.');
            $validate = \validate('Spcreate');
            $res = $validate->scene('change')->check($data);
            if (true !== $res) {
                return \json(array("code" => 404, "msg" => $validate->getError()));
            }
            // 权限字段查看
            if(empty($data['auth'])){
                return \json(array("code" => 404, "msg" => '权限不能为空'));
            }
            // Code查看
            if(empty($data['code'])){
                return \json(array("code" => 404, "msg" => '修改失败，参数code缺失！'));
            }
            
            $user_code = $data['code'];

            $insertData=array(
                'code'          => $user_code,
                'type'          => 2,
                'account_num'   => $data['account'],
                'com_name'      => $data['company'],
                'address'       => $data['address'],
                'name'          => $data['name'],
                'mobile'        => $data['mobile'],
                'qq'            => 'xxx',
                'email'         => $data['email_address'],
                'file'          => json_encode(array($data['y_image_path'],$data['z_image_path'],$data['s_image_path']))
            );
            // 密码检验
            if(!empty($data['password'])){
                $insertData['pwd']  = md5($data['password']);
            }

            // 数据更新
            $res1 = db('sp')->where(['code' => $user_code])->update($insertData);
            // 删除原权限
            db('auth')->where(['user_code' => $user_code])->delete();

            $authData = [];
            foreach($data['auth'] as $k => $v){
                $authData[]=['user_code'=> $user_code,'fid' => $v];
            }
            $res2 = db('auth')->insertAll($authData);
            // 插入返回
            if($res1 !==false && $res2 !==false){
                return \json(array("code" => 200, "msg" => '添加成功'));
            }else{
                return \json(array("code" => 404, "msg" => '添加失败'));
            }
      } 
}   