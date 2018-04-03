<?php
namespace app\home\controller;
use app\common\controller\HomeBase;

class contract extends HomeBase
{
    public function __construct()
    {
        parent::__construct();
    }

    //供应商列表
    public function showSp(){
        if(!getSpType()){
            return json(array("code" => 404, "msg" => "只有超级管理才有权限"));
        }
        $list = db('sp')->field('code,com_name')->where(array('type' => '2'))->select();
        if(!$list){
            return json(array("code" => 405, "msg" => "没有任何供应商"));
        }
        return json(array("code" => 200, "data" => $list));
    }

    //添加
    public function add(){
        if(!getSpType()){
            return json(array("code" => 404, "msg" => "只有超级管理才有权限"));
        }
        $data = input("post.");
        $data["content"] = $_POST["content"];//富文本

//        //图片数组处理
//        if (empty($data["fileList"])) {
//            return array("code" => 404, "msg" => "上传参数错误");
//        }
//        //图片存入处理
//        $data["image"]      = $this->imageSetStr($data["fileList"]);
//        unset($data["fileList"]);
        if(empty($data["content"])){
            return json(array("code" => 404, "msg" => "合同内容不能为空"));
        }

        //数据验证
        $validate = new \app\home\validate\contract();
        $result = $validate->scene('add')->check($data);
        if (true !== $result) {
            return json(array("code" => 405, "msg" => $validate->getError()));
        }

        $data["code"]       =  $this->creatrCode();//编号
        $view = model('contract');
        $view->data($data);
        $res = $view->allowField(true)->save();// 过滤post数组中的非数据表字段数据
        if(!$res){
            return json_encode(array("code" => 403, "msg" => "保存出错，请再保存一次"));
        }
        return json(array("code" => 200,"data"=>array("code"=>$data["code"])));
    }

    //显示
    public function show(){
        $code = input("post.code");
        if(empty($code)){
            return json(array("code" => 404,"data"=>"合同编号不能为空"));
        }
        $where = [
            "code"      =>  $code, 
            'is_del'    =>  ['<>', "1"]  //未删除
        ];
        
        $res = db("contract")->field("code,sp_code,name,content,rate")->where($where)->find();
        return json(array("code" => 200,"data"=>$res));
    }

    //列表显示
    public function showList(){
        $where['is_del'] = array("<>", "1");  //未删除
        if(!getSpType()){                       //超级管理
            $where["sp_code"] = getSpCode();   //供应商
        }

        $page = input("post.page");            //页码
        if(empty($page)){
            $page = 1;
        }

        $count = db('contract')->where($where)->count('id');
        if(!$count){
            return json(array("code" => 200,"data" => array("count" =>0)));
        }
        $res = db("contract")->field("code,sp_code,name,create_time,rate")->where($where)->order('id desc')->page($page,10)->select();
        $output["list"]  =  $res;
        $output["count"]  =  $count;
        return json(array("code" => 200,"data"=>$output));

    }

    //删除
    public function del(){
        if(!getSpType()){
            return json(array("code" => 404, "msg" => "只有超级管理才有权限"));
        }
        $code = input("post.code");
        if(empty($code)){
            return json(array("code" => 404,"data"=>"合同编号不能为空"));
        }
        $where = [
            "code"        =>  $code,
            "is_del"      =>  ["<>","1"]          //未删除
        ];
        $res = db('contract')->field("id")->where($where)->find();
        if(empty($res)){
            return json(array("code" => 405,"msg" => "没有这条合同或者已经被删除"));
        }
        $output = db('contract')->where($where)->update(array("is_del"=>1));
        if(!$output){
            return json(array('code' => 403,'msg' => '删除失败，失败原因：可能已经被删除'));
        }
        return json(array('code' => 200,'msg' => '删除成功'));
    }

    //异步上传图片
    public function imageUpload()
    {


        $imgLimit = config("imageUpLimit");
        $file = request()->file('file');
        if (empty($file)) {
            return json_encode(array("code" => 404, "msg" => "参数错误"));
        }
        $info = $file->validate($imgLimit)->move(ROOT_PATH . 'public' . DS . 'image' . DS . '');
        if ($info) {
            return json_encode(array("code" => 200, "data" => array("name" => '' . DS . $info->getSaveName())));
        } else {
            return json_encode(array("code" => 403, "msg" => $file->getError()));
        }
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

    private function creatrCode(){
        $id = db("contract")->order("id desc")->value('id');
        $id += 100001;
        return $id;
    }

}
