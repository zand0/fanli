<?php
namespace app\seller\controller;

class Login extends Base
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function login(){
        $post = I('post.');
        
        if(!empty($post)){
            $seller = M('store')->where(['seller_name'=>$post['username'],'password'=>md5(md5($post['password']))])->find();
            if(!empty($seller)){
                //$this->redirect('/seller/index/index',[]);
                session('user_id',$seller['user_id']);
                session('seller_id',$seller['id']);
                $this->success('登陆成功','/Seller/index');
            }else{
                $this->error('登陆失败');
            } 
        }else{
            $this->error('请求为空','/seller/login');
        }
        
    }
    
    public function logout(){
        session(null);
        $this->success('登出成功','/seller/login/index');
    }
}
