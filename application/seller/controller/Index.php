<?php
namespace app\seller\controller;

class Index extends Base
{
    public function index()
    {
        
        
        return $this->fetch();
    }
    
    public function get_category(){
        
        $post = I('get.');
        
        $list = M('goods_category')->where(['parent_id'=>$post['parent_id']])->select();
        $str = '';
        foreach ($list as $v){
            $str .= "<option value='{$v['id']}' rel='0'>{$v['name']}</option>";
        }
        echo $str;
    } 
}
