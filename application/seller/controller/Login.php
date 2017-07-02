<?php
namespace app\seller\controller;
use think\Verify;
class Login extends Base
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function login(){
        $post = I('post.');
        $this->verifyHandle('user_login');
        if(!empty($post)){
            $seller = M('store')->where(['seller_name'=>$post['username'],'password'=>md5(md5($post['password']))])->find();
            if(!empty($seller)){
                M('store')->where(['id'=>$seller['id']])->update(['last_login'=>time()]);
                //$this->redirect('/seller/index/index',[]);
                session('user_id',$seller['user_id']);
                session('seller_id',$seller['id']);
                if($post['remember']){
                    session('expire',3600*24*7);
                }else{
                    session('expire',1800);
                }
                $this->success('登陆成功','/Seller/index');
            }else{
                $this->success('登陆失败');
            } 
        }else{
            $this->error('请求为空','/seller/login');
        }
        
    }
    
    public function logout(){
        session(null);
        $this->success('登出成功','/seller/login/index');
    }
    
    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
        exit();
    }
    
    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        $result = $verify->check(I('post.vertify'), $id ? $id : 'user_login');
        if (!$result) {
            $this->error("图像验证码错误");
        }
    }
}
