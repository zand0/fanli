<?php
namespace app\seller\controller;

use think\Model;

class Finance extends Base
{
    public function index()
    {
       return $this->fetch(); 
    }
    
    public function add_edit_withdrawals(){
        $post = I('post.');
        $get = I('get.');
        $user_id = session('user_id');
        $sid = session('seller_id');
        $id = $get['id'];
        if(!empty($post)){
            $store_name = M('store')->where(['id'=>$sid])->getField('store_name');
            $finance = model('finance');
            unset($post['id']);
            $finance->data($post);
            $finance->user_id = $user_id;
            $finance->status = 0;
            $finance->type = 0;
            $finance->add_time = time();
            $finance->store_id = $sid;
            $finance->store_name = $store_name;
            if($finance->save()){
                $this->success('添加成功','/seller/finance/withdrawals');
            }
        }
        $ws = M('finance')->where(['id'=>$id])->find();
        $this->assign('ws',$ws);
        return $this->fetch();
    }
    
    public function withdrawals(){
        $user_id = session('user_id');
        $list = M("finance")->where(['user_id'=>$user_id])->select();
        
        $this->assign('list',$list);
        
        return $this->fetch();
    }
    
}
