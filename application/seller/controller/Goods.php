<?php
namespace app\seller\controller;
use think\Page;
use app\admin\logic\GoodsLogic;
class Goods extends Base
{
    public function index()
    {
        
    }
    
    public function addEditGoods(){
        $post = I('post.');
        $get = I('get.');
        $store_id = session('seller_id');
        $goods_id = I('get.goods_id',0);
        $type = $goods_id > 0 ? 2 : 1;
        $GoodsLogic = new GoodsLogic();
        if(!empty($post)){
            if($get['is_ajax']==1){
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
                if ($type == 2) {
                    $Goods->isUpdate(true)->save(); // 写入数据到数据库
                    // 修改商品后购物车的商品价格也修改一下
                    M('cart')->where("goods_id = $goods_id and spec_key = ''")->save(array(
                        'market_price' => I('market_price'), //市场价
                        'goods_price' => I('shop_price'), // 本店价
                        'member_goods_price' => I('shop_price'), // 会员折扣价
                    ));
                    $msg = '修改成功';
                } else {
                    $Goods->save(); // 写入数据到数据库
                    $goods_id = $insert_id = $Goods->getLastInsID();
                    $msg = '添加成功';
                }
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
                        if(M('spec_goods_price')->where(['goods_id'=>$goods_id,'key'=>$k])->find()){
                            M('spec_goods_price')
                            ->where(['goods_id'=>$goods_id,'key'=>$k])
                            ->data([
                                'goods_id'=>$goods_id,  // '商品id' ,
                                'key'=>$k,  // '规格键名' ,
                                'key_name'=>$i['key_name'],  // '规格键名中文' ,
                                'price'=>$i['price'],  // '价格' ,
                                'store_count'=>$i['store_count'],  // '库存数量' ,
                                'bar_code'=>'',  // '商品条形码' ,
                                'sku'=>$i['sku']  // 'SKU' ,
                            ])->update();
                        }else{
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
                }
                foreach ($attrs as $v){
                    //$attr_name = M('goods_attribute')->where(['attr_id'=>$v['id']])->getField('attr_name');
                    if(M('goods_attr')->where(['goods_id'=>$goods_id,'attr_id'=>$v['id']])->find()){
                        M('goods_attr')->where(['goods_id'=>$goods_id,'attr_id'=>$v['id']])->data([
                            'goods_id'=>$goods_id,
                            'attr_id'=>$v['id'],
                            'attr_value'=>$v['name'],
                            'attr_price'=>0
                        ])->update();
                        
                    }else{
                        M('goods_attr')->data([
                            'goods_id'=>$goods_id,
                            'attr_id'=>$v['id'],
                            'attr_value'=>$v['name'],
                            'attr_price'=>0
                        ])->save();
                    } 
                } 
            }
            //$seller = M('store')->where(['account'=>$post['account'],'password'=>md5(md5($post['password']))])->find();
            if(!empty($goods_id)){
                return json(['status'=>1,'msg'=>$msg]);
            }else{
                return json(['status'=>0,'msg'=>$msg]);
            } 
        }
        $goodsInfo = M('Goods')->where('goods_id',$goods_id)->find();
        if ($goodsInfo['price_ladder']) {
            $goodsInfo['price_ladder'] = unserialize($goodsInfo['price_ladder']);
        }
        $level_cat = $GoodsLogic->find_parent_cat($goodsInfo['cat_id']); // 获取分类默认选中的下拉框
        $level_cat2 = $GoodsLogic->find_parent_cat($goodsInfo['extend_cat_id']); // 获取分类默认选中的下拉框
        $brandList = $GoodsLogic->getSortBrands();
        $cat_list = M('goods_category')->where("parent_id = 0")->select(); // 已经改成联动菜单
        $plugin_shipping = M('plugin')->where(array('type' => array('eq', 'shipping')))->select();//插件物流
        $shipping_area = D('Shipping_area')->getShippingArea();//配送区域
        $goods_shipping_area_ids = explode(',', $goodsInfo['shipping_area_ids']);
        $this->assign('goods_shipping_area_ids', $goods_shipping_area_ids);
        $this->assign('goods', $goodsInfo);  // 商品详情
        $this->assign('cat_list', $cat_list);
        $this->assign('level_cat', $level_cat);
        $this->assign('level_cat2', $level_cat2);
        $this->assign('brandList', $brandList);
        $this->assign('plugin_shipping', $plugin_shipping);
        $this->assign('shipping_area', $shipping_area);
        $goodsImages = M("GoodsImages")->where('goods_id',$goods_id)->select();
        $this->assign('goodsImages', $goodsImages);  // 商品相册
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
        /*$list = M('goods_attribute')->where(['type_id'=>$type_id])->select();
//         $list2 = [];
//         foreach ($list as $k=>$v){
//             if($v['attr_input_type']==1){
//                 $list2[] = $v;
//                 unset($list[$k]);
//             }
//         }
        if(!empty($goods_id)){
            foreach ($list as &$v){
                $v['item'] = M('goods_attr')->where(['goods_id'=>$goods_id,'attr_id'=>$v['attr_id']])->find();
            }
        }
        
        unset($v);
        $this->assign('list',$list);
        //$this->assign('list2',$list2);
        return $this->fetch();*/
        
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($goods_id,$type_id);
        exit($str);
    }
    
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id');
        $GoodsLogic = new GoodsLogic();
        $cat_id3 = I('get.cat_id3');
        $list2 = [];
        $typename = M('goods_category')->where(['id'=>$cat_id3])->getField('name');
        if(strstr($typename, ',')){
            $typename = str_replace(',', '、', $typename);
        }elseif (strstr($typename, ' ')){
            $typename = str_replace(' ', '、', $typename);
        }
        $type_id = M('goods_type')->where(['name'=>$typename])->getField('id');
        $list = [];
        $specList = M('spec')->where(['type_id'=>$type_id])->select();
//         foreach ($specList as &$v){
//             $v['spec_item']=M('spec_item')->where(['spec_id'=>$v['id']])->select();
//         }
        
//         unset($v);
//         if(empty($specList)){
//            return ; 
//         }
//         $this->assign('list',$specList);
//         //$this->assign('list2',$list2);
//         return $this->fetch();

        foreach($specList as $k => $v)
            $specList[$k]['spec_item'] = M('SpecItem')->where("spec_id = ".$v['id'])->order('id')->getField('id,item'); // 获取规格项
        
        
    
        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');
            
            $items_id = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
            $items_ids = explode('_', $items_id);
        }else{
            $items_ids=[];
        }
        $this->assign('specImageList',$specImageList);
    
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        return $this->fetch();
        
        
        
    }
    
    public function addSpecItem(){
        $spec_item = I("post.spec_item");
        $spec_id = I("post.spec_id");
        if(empty($spec_id) || empty($spec_item)){
            return json(['status'=>-2,'msg'=>'数据不完整']);
        }
        if(!M('spec_item')->where(['spec_id'=>$spec_id,'item'=>$spec_item])->find()){
            $res = M("spec_item")->data([
                'spec_id'=>$spec_id,
                'item'=>$spec_item
            ])->save();
        }else{
            return json(['status'=>-3,'msg'=>'此项已存在']);
        }
        if ($res){
            return json(['status'=>0,'msg'=>'ok']);
        }else{
            return json(['status'=>-1,'msg'=>'添加失败']);
        }
    }
    
    public function ajaxGetSpecInput(){
        /*$post = I('post.');
        $goods_id = I('get.goods_id');
        $spec_arr = $post['spec_arr'];
        $list = [];
        $list2 = [];
        $spec_item = [];
        $item_ids = [];
        $spec_ids = [];
        $key_name = '';
        if(!empty($spec_arr)){
            foreach ($spec_arr as $k=>$sp){
                $spec_ids[] = $k;
                $item_ids[] = current($sp);
                //$item_ids = $sp;
                $list[] = ['id'=>$k,'name'=>M('spec')->where('id',$k)->getField('name'),'item_name'=>$spec_arr[$k]];
                //$list2 = M('spec_goods_price')->where(['goods_id'=>$goods_id])->select();
                
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
        if(!empty($goods_id)){
            $list2 = M('spec_goods_price')->where(['goods_id'=>$goods_id])->select();
        }
        if(!empty($list2)){
            foreach ($list2 as &$v) {
                $v['key_name_arr'] = strstr($v['key_name'],' ')?explode(' ', $v['key_name']):[$v['key_name']];
            }
        }
        unset($v);
        //$item_name_list = M('')->where(['g'])->getField();
        //var_dump(implode('_', $item_ids));
        $this->assign('list',$list);
        $this->assign('list2',$list2);
        $this->assign('key_name',rtrim($key_name));
        $this->assign('spec_item',$spec_item);
        $this->assign('key_id',!empty($item_ids)?implode('_', $item_ids):'');*/
        
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('goods_id/d') ? I('goods_id/d') : 0;
        $str = $GoodsLogic->getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
        exit($str);
        return $this->fetch();  
    }
    
    public function goodsList(){
        $sid = session('seller_id');
        $where=[];
        $where['is_on_sale']=1;
        $where['store_id']=$sid;
        $goods_count = M('goods')->where($where)->count();
        $Page = new Page($goods_count,C('PAGESIZE'));
        $list = M('goods')->where($where)->order('last_update desc,on_time desc')->limit($Page->firstRow,$Page->listRows)->select();
        $catlist = M('goods_category')->where('parent_id',0)->getField('id,name');
        foreach($list as &$v){
            $v['cat_id1'] = M('goods_category')->where('id',M('goods_category')->where('id',$v['cat_id'])->getField('parent_id'))->getField('parent_id');
        }
        unset($v);
        //$catlist = M('goods_category')->where('parent_id',0)->getField('id,name');
        $this->assign('list',$list);
        $this->assign('catlist',$catlist);
        $this->assign('page',$Page->show());
        return $this->fetch();
    }
}
