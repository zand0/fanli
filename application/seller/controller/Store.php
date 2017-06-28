<?php
namespace app\seller\controller;

class Store extends Base
{
    public function index()
    {
        return '';
    }
    
    public function store_setting(){
        $sid = session('seller_id');
        if(empty($sid)){
            $this->error('sid is empty');
        }
        $store = M('store')->where('id',$sid)->find();
        $this->assign("store",$store);
        return $this->fetch();
    }
    
    public function setting_save(){
        $post = I('post.');
        $sid = session('seller_id');
        if(empty($sid)){
            $this->error('sid is empty');
        }
        if(empty($post)){
            $this->error('空请求');
        }
        
        $res = M('store')->where('id',$sid)->update($post);
        if($res){
            $this->success('修改成功！','/seller/store/store_setting');
        }else{
            $this->error("修改失败",'/seller/store/store_setting');
        }
        
    }
    
    public function change_password(){
        $post = I('post.');
        $sid = session('seller_id');
        if(empty($sid)){
            $this->error('sid is empty');
        }
        if(!empty($post['new_pwd']) && $post['new_pwd']!=$post['sec_pwd']){
            $this->error('密码不一致');
        }
        if(!empty($post)){
            if(M('store')->where('id',$sid)->getField('password')==md5(md5($post['old_pwd']))){
                if(M('store')->where('id',$sid)->update(['password'=>md5(md5($post['new_pwd']))])){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                $this->error('旧密码错误');
            }
        }
        return $this->fetch();
    }
}
