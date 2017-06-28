<?php
namespace app\seller\controller;
use think\Page;
class Goods extends Base
{
    public function index()
    {
        
    }
    
    public function addEditGoods(){
        $post = I('post.');
        $get = I('get.');
        $store_id = session('seller_id');
        if(!empty($post)){
            if($get['is_ajax']==1){
                
                //goods
                $Goods = new \app\admin\model\Goods();
                $Goods->data($post, true); // 收集数据
                $Goods->on_time = time(); // 上架时间
                $Goods->store_id = $store_id;
                if(!empty($post['shipping_area_ids'])){
                    $Goods->shipping_area_ids = implode(',', $post['shipping_area_ids']);
                }
                I('cat_id2') && ($Goods->cat_id = I('cat_id2'));
                I('cat_id3') && ($Goods->cat_id = I('cat_id3'));
                
                I('extend_cat_id_2') && ($Goods->extend_cat_id = I('extend_cat_id_2'));
                I('extend_cat_id_3') && ($Goods->extend_cat_id = I('extend_cat_id_3'));
                
                //$Goods->shipping_area_ids = implode(',', I('shipping_area_ids/a', []));
                //$Goods->shipping_area_ids = $Goods->shipping_area_ids ? $Goods->shipping_area_ids : '';
                //$Goods->spec_type = $Goods->goods_type;
                
                $Goods->save(); // 写入数据到数据库
                $goods_id = $insert_id = $Goods->getLastInsID();
                //goods_img
                $images = $post['goods_images'];
                $m_gi = M('goods_images');
                for ($i=0;$i<6;$i++){
                    if(!empty($images[$i])){
                        $m_gi->data([
                            'goods_id'=>$goods_id,
                            'image_url'=>$images[$i],
                        ])->save();
                    }
                }
                //goods_attr
                $attrs = [];
                foreach ($post as $k=>$v){
                    if(strstr($k, 'attr_')){
                        $tmp = explode('_', $k);
                        $attrs[] = ['id'=>$tmp[1],'name'=>$v[0]];
                    }
                    
                }
                if(!empty($post['item'])){
                    foreach ($post['item'] as $k=>$i){
                        M('spec_goods_price')->data([
                            'goods_id'=>$goods_id,  // '商品id' ,
                            'key'=>$k,  // '规格键名' ,
                            'key_name'=>$i['key_name'],  // '规格键名中文' ,
                            'price'=>$i['price'],  // '价格' ,
                            'store_count'=>$i['store_count'],  // '库存数量' ,
                            'bar_code'=>'',  // '商品条形码' ,
                            'sku'=>$i['sku']  // 'SKU' ,
                        ])->save();
                    }
                    
                }
                
                //$m_ga = M('goods_attr');
                //$m_gattr = M('goods_attribute');
                foreach ($attrs as $v){
                    //$attr_name = M('goods_attribute')->where(['attr_id'=>$v['id']])->getField('attr_name');
                    M('goods_attr')->data([
                        'goods_id'=>$goods_id,
                        'attr_id'=>$v['id'],
                        'attr_value'=>$v['name'],
                        'attr_price'=>0
                    ])->save();
                }
                //
                
            }
            //$seller = M('store')->where(['account'=>$post['account'],'password'=>md5(md5($post['password']))])->find();
            if(!empty($goods_id)){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            } 
        }
        return $this->fetch();
    }
    
    public function ajaxGetAttrInput(){
        $goods_id = I('get.goods_id');
        $cat_id3 = I('get.cat_id3');
        //<tr class='attr_311'><td> 包装尺寸</td> <td><input type='text' size='40' value='' name='attr_311[]' /></td></tr><tr class='attr_312'><td> 毛重</td> <td><input type='text' size='40' value='' name='attr_312[]' /></td></tr><tr class='attr_313'><td> 品牌</td> <td><input type='text' size='40' value='' name='attr_313[]' /></td></tr><tr class='attr_314'><td> 制冷方式</td> <td><input type='text' size='40' value='' name='attr_314[]' /></td></tr><tr class='attr_315'><td> 能效等级</td> <td><select name='attr_315[]'><option value=''>不限</option><option value='一级二级三级四级五级'>一级二级三级四级五级</option></select></td></tr>
        $typename = M('goods_category')->where(['id'=>$cat_id3])->getField('name');
        if(strstr($typename, ',')){
            $typename = str_replace(',', '、', $typename);
        }elseif (strstr($typename, ' ')){
            $typename = str_replace(' ', '、', $typename);
        }
        $type_id = M('goods_type')->where(['name'=>$typename])->getField('id');
        $list = M('goods_attribute')->where(['type_id'=>$type_id])->select();
//         $list2 = [];
//         foreach ($list as $k=>$v){
//             if($v['attr_input_type']==1){
//                 $list2[] = $v;
//                 unset($list[$k]);
//             }
//         }
        $this->assign('list',$list);
        //$this->assign('list2',$list2);
        return $this->fetch();
    }
    
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id');
        $cat_id3 = I('get.cat_id3');
        $typename = M('goods_category')->where(['id'=>$cat_id3])->getField('name');
        if(strstr($typename, ',')){
            $typename = str_replace(',', '、', $typename);
        }elseif (strstr($typename, ' ')){
            $typename = str_replace(' ', '、', $typename);
        }
        $type_id = M('goods_type')->where(['name'=>$typename])->getField('id');
        $list = [];
        $specList = M('spec')->where(['type_id'=>$type_id])->select();
        foreach ($specList as &$v){
            $v['spec_item']=M('spec_item')->where(['spec_id'=>$v['id']])->select();
            
        }
        unset($v);
        if(empty($specList)){
           return ; 
        }
        $this->assign('list',$specList);
        return $this->fetch();
        
    }
    
    public function addSpecItem(){
        
    }
    
    public function ajaxGetSpecInput(){
        $post = I('post.');
        $spec_arr = $post['spec_arr'];
        $list = [];
        $spec_item = [];
        $item_ids = [];
        $spec_ids = [];
        $key_name = '';
        if(!empty($spec_arr)){
            foreach ($spec_arr as $k=>$sp){
                $spec_ids[] = $k;
                $item_ids[] = current($sp);
                //$item_ids = $sp;
                $list[] = ['id'=>$k,'name'=>M('spec')->where('id',$k)->getField('name'),'item'=>$spec_arr[$k]];
            }
            foreach ($list as $k=>$v){
                
                foreach ($v['item'] as $key=>$i){
                    //$item_ids[]=$v['item'][$key];
                    $name = M('spec_item')->where('id',$v['item'][$key])->getField('item');
                    $key_name .= $v['name'].':'.$name.' ';
                    $spec_item[] = ['id'=>$v['item'][$key],'name'=>$name];
                }
            }
        }
        //var_dump(implode('_', $item_ids));
        $this->assign('list',$list);
        $this->assign('key_name',rtrim($key_name));
        $this->assign('spec_item',$spec_item);
        $this->assign('key_id',!empty($item_ids)?implode('_', $item_ids):'');
        return $this->fetch();
    }
    
    public function goodsList(){
        $where=[];
        $where['is_on_sale']=1;
        $goods_count = M('goods')->where($where)->count();
        $Page = new Page($goods_count,C('PAGESIZE'));
        $list = M('goods')->where($where)->order('last_update desc')->limit($Page->firstRow,$Page->listRows)->select();
        $catlist = M('goods_category')->where('parent_id',0)->getField('id,name');
        $this->assign('list',$list);
        $this->assign('catlist',$catlist);
        $this->assign('page',$Page->show());
        return $this->fetch();
    }
}
