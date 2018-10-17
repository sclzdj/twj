<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Product extends Admin
{
	public function index($status='0'){  
        $list_tab = [
            '0' => ['title' => '正常', 'url' => url('index', ['status' => '0'])],
            '1' => ['title' => '首页推荐', 'url' => url('index', ['status' => '1'])],
            '2' => ['title' => '待审核', 'url' => url('index', ['status' => '2'])],
            '3' => ['title' => '驳回', 'url' => url('index', ['status' => '3'])],
        ];
        switch ($status) {
            case '0':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('products')->where('audit_state','1')->where('is_recommend','0')->where('state','in','1,2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                $suppliers=db('suppliers')->field('id,name')->select();
                $select_suppliers=[];
                foreach ($suppliers as $key => $value) {
                    $select_suppliers[$value['id']]=$value['name'];
                }
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('products') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTopSelect('supplier_id', '全部供应商', $select_suppliers) //添加顶部下拉筛选
                    ->addTopSelect('is_grounding', '上下架', ['0'=>'下架','1'=>'上架']) //添加顶部下拉筛选
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','sn' => '编号','name' => '名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'], 
                            ['sn', '商品编号'], 
                            ['name', '商品名称'],
                            ['supplier_id', '供应商','callback',function($value){
                                return db('suppliers')->where('id',$value)->value('name');
                            }],
                            ['price_supply', '供应价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_supply']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['price_sale', '销售价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_sale']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['product_type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                            ['is_grounding','上架','switch'],
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('edit',['title'=>'修改'])
                    ->addRightButton('custom',['title'=>'设为首页推荐','href'=>url('recommend',['ids'=>'__ID__','is_recommend'=>'1']),'class'=>'btn btn-xs btn-default ajax-get']) 
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '1':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('products')->where('audit_state','1')->where('is_recommend','1')->where('state','in','1,2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('products') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTopSelect('is_grounding', '上下架', ['0'=>'下架','1'=>'上架']) //添加顶部下拉筛选
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','sn' => '编号','name' => '名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'], 
                            ['sn', '商品编号'], 
                            ['name', '商品名称'],
                            ['supplier_id', '供应商','callback',function($value){
                                return db('suppliers')->where('id',$value)->value('name');
                            }],
                            ['price_supply', '供应价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_supply']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['price_sale', '销售价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_sale']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['product_type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                            ['is_grounding','上架','switch'],
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('custom',['title'=>'取消首页推荐','href'=>url('recommend',['ids'=>'__ID__','is_recommend'=>'0']),'class'=>'btn btn-xs btn-default ajax-get']) 
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '2':
                $search_order=input('order','created_at desc,id desc');
                $order=$search_order;
                $search_created_start=trim(input('created_start',''));
                $search_created_end=trim(input('created_end',''));
                $search_sn=trim(input('sn',''));
                $search_keywords=trim(input('keywords',''));
                $search_supplier_id=input('supplier_id','0');
                $map = [];
                $search_supplier_name='';
                if($search_supplier_id>0){
                    $map['supplier_id']=$search_supplier_id;
                    $search_supplier_name=db('suppliers')->where('id',$search_supplier_id)->value('name');
                    if($search_supplier_name===null){
                        $search_supplier_name='<i style="color:#999;">无效产品</i>';
                    }
                }
                if($search_keywords!==''){
                    $map['name']=['like','%'.$search_keywords.'%'];
                }
                if($search_created_start!=='' || $search_created_end!==''){
                    if($search_created_start!=='' && $search_created_end===''){
                        $map['created_at']=['>=',strtotime($search_created_start.' 00:00:00')];
                    }elseif($search_created_start==='' && $search_created_end!==''){
                        $map['created_at']=['<=',strtotime($search_created_end.' 23:59:59')];
                    }else{
                        $map['created_at']=['between',[strtotime($search_created_start.' 00:00:00'),strtotime($search_created_end.' 23:59:59')]];
                    }
                }
                if($search_sn!==''){
                    $map['sn']=['like','%'.$search_sn.'%'];
                }
                $limit=Db::name('admin_config')->where('name','list_rows')->value('value');
                $data_list = Db::name('products')->where('audit_state','0')->where('state','in','1,2')->where($map)->order($order)->paginate($limit);
                // 获取分页显示
                $page = $data_list->render();

                $data_list = json_decode(json_encode($data_list),TRUE);
                $data_list=$data_list['data'];
                foreach ($data_list as $key => $value) {
                    $data_list[$key]['product_type_name']=db('product_types')->where('id',$value['product_type_id'])->value('name');
                    $data_list[$key]['supplier_name']=db('suppliers')->where('id',$value['supplier_id'])->value('name');
                    $data_list[$key]['created_at_str']=date('Y-m-d H:i',$value['created_at']);
                    $sizes=db('product_sizes')->where('product_id',$value['id'])->select();
                    if($sizes){
                        $ht=[];
                        foreach ($sizes as $k => $v) {
                            $ht[]=$v['name'].'：￥'.number_format($v['price_supply']/100,2,'.','');
                        }
                        $str=implode('<br>', $ht);
                    }else{
                        $str='￥'.number_format($value['price_supply']/100,2,'.','');
                    }
                    $data_list[$key]['product_size_str']=$str;
                }
                $this->assign('data_list', $data_list);
                $this->assign('page', $page);
                $this->assign('search_order', $search_order);
                $this->assign('search_created_start', $search_created_start);
                $this->assign('search_created_end', $search_created_end);
                $this->assign('search_keywords', $search_keywords);
                $this->assign('search_sn', $search_sn);
                $this->assign('search_supplier_id', $search_supplier_id);
                $this->assign('search_supplier_name', $search_supplier_name);
                $this->assign('tab_nav', ['tab_list' => $list_tab, 'curr_tab' => '2']);
                $this->assign('empty','<div style="height:250px;line-height:250px;text-align:center;font-size:30px;color:#666;background:#fff;">！没有数据</div>');
                $this->assign('page_title', '供应商商品审核');
                // 渲染模板输出
                return $this->fetch('check');
                break;
            case '3':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('products')->where('audit_state','2')->where('state','in','1,2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('products') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','sn' => '编号','name' => '名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'],
                            ['sn', '商品编号'],  
                            ['name', '商品名称'],
                            ['supplier_id', '供应商','callback',function($value){
                                return db('suppliers')->where('id',$value)->value('name');
                            }],
                            ['price_supply', '供应价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_supply']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['price_sale', '销售价格','callback',function($value,$data){
                                $sizes=db('product_sizes')->where('product_id',$data['id'])->select();
                                if($sizes){
                                    $ht=[];
                                    foreach ($sizes as $k => $v) {
                                        $ht[]=$v['name'].'：￥'.number_format($v['price_sale']/100,2,'.','');
                                    }
                                    return implode('<br>', $ht);
                                }else{
                                    return '￥'.number_format($value/100,2,'.','');
                                }
                            },'__data__'], 
                            ['product_type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['auditmsg','上次驳回原因','callback',function($value,$data){
                                return db('product_audits')->where('product_id',$data['id'])->order('audit_at desc')->value('auditmsg');
                            },'__data__'],
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                            //['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            default:
                break;
        }
    		
	}
    public function recommend($is_recommend){
        if(!in_array($is_recommend, ['0','1'])){
            return $this->error('请求错误');
        }
        $ids = (Request::instance()->isGet()) ? input('ids') : input('post.ids/a');
        $ids=(array)$ids;
        foreach ($ids as $key => $value) {
            if(Db::name('products')->where('id',$value)->value('is_recommend')==$is_recommend){
                unset($ids[$key]);
            }
        }
        $rt=db('products')->where('id','in',$ids)->where('audit_state','1')->where('state','in','1,2')->update(['is_recommend'=>$is_recommend,'updated_at'=>time()]);
        $tips=$is_recommend?'设为':'取消';
        if($rt!==false){
            return $this->success($tips.'首页推荐成功',url('index',['status'=>$is_recommend]));
        }else{
            return $this->error($tips.'首页推荐失败');
        }
    }
    public function audit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $product=Db::name('products')->where('audit_state','0')->where('state','in','1,2')->where('id',$data['id'])->find();
            if(!$product){
                exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
            }
            $now=time();
            //数据输入验证
            $validate = new Validate([
                'audit_state|审核' => 'require|in:1,2',
            ]);
            if (!$validate->check($data)) {
                exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
            }
            if($data['audit_state']=='1'){
                $validate = new Validate([
                    'product_type_id|产品分类'  => 'require|number',
                    'is_sizes|是否多规格' => 'require|in:0,1',
                ]);
                if (!$validate->check($data)) {
                    exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
                }
                if($data['is_sizes']){
                    $updateAll=[];
                    $updateIdAll=[];
                    $pix=true;
                    foreach ($data as $key => $value) {
                        $arr=explode('_', $key);
                        if(isset($arr[0]) && isset($arr[1]) && isset($arr[2]) && $arr[0]=='price' && $arr[1]=='size' && $arr[2]>0){
                            if($value===''){
                                exit(json_encode(['data'=>[],'code'=>202,'msg'=>'每一项公开价格都必须填写']));
                            }
                            if($value<=0){
                                exit(json_encode(['data'=>[],'code'=>202,'msg'=>'公开价格都必须为大于0的数，可精确到分']));
                            }
                            if(!preg_match("/^\d{1,10}(\.\d{1,2})?$/",$value)){
                                exit(json_encode(['data'=>[],'code'=>202,'msg'=>'公开价格都必须为大于0的数，可精确到分']));
                            }
                            if($pix){
                                $price_sale=$value*100;
                                $pix=false;
                            }
                            $updateAll[]=['price_sale'=>$value*100,'updated_at'=>$now];
                            $updateIdAll[]=$arr[2];
                        }
                    }
                    foreach ($updateAll as $key => $value) {
                        Db::name('product_sizes')->where('product_id',$data['id'])->where('id',$updateIdAll[$key])->update($value);
                    }
                    $update=[];
                    $update['id']=$data['id'];
                    $update['audit_state']=$data['audit_state'];
                    $update['product_type_id']=$data['product_type_id'];
                    $update['price_sale']=$price_sale;
                    $update['updated_at']=$now;
                    $rt=Db::name('products')->update($update);
                    if($rt!==false){
                        exit(json_encode(['data'=>[],'code'=>200,'msg'=>'商品审核通过','status'=>'0']));
                    }else{
                        exit(json_encode(['data'=>[],'code'=>203,'msg'=>'数据库写入错误']));
                    }
                }else{
                    $validate = new Validate([
                        'price_sale|公开价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
                    ]);
                    if (!$validate->check($data)) {
                        exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
                    }
                    $update=[];
                    $update['id']=$data['id'];
                    $update['audit_state']=$data['audit_state'];
                    $update['product_type_id']=$data['product_type_id'];
                    $update['price_sale']=$data['price_sale']*100;
                    $update['updated_at']=$now;
                    $rt=Db::name('products')->update($update);
                    if($rt!==false){
                        exit(json_encode(['data'=>[],'code'=>200,'msg'=>'商品审核通过','status'=>'0']));
                    }else{
                        exit(json_encode(['data'=>[],'code'=>203,'msg'=>'数据库写入错误']));
                    }
                }
            }elseif($data['audit_state']=='2'){
                $validate = new Validate([
                    'auditmsg|驳回原因'  => 'require|length:1,20',
                ]);
                if (!$validate->check($data)) {
                    exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
                }
                Db::name('product_audits')->insertGetId(['product_id'=>$data['id'],'auditmsg'=>$data['auditmsg'],'audit_at'=>$now]);
                $update=[];
                $update['id']=$data['id'];
                $update['audit_state']=$data['audit_state'];
                $update['updated_at']=$now;
                $rt=Db::name('products')->update($update);
                if($rt!==false){
                    exit(json_encode(['data'=>[],'code'=>200,'msg'=>'商品已驳回','status'=>'3']));
                }else{
                    exit(json_encode(['data'=>[],'code'=>203,'msg'=>'数据库写入错误']));
                }
            }else{
                exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
            }
        }
        if($id>0){
            $product=Db::name('products')->where('audit_state','0')->where('state','in','1,2')->find($id);
            if(!$product){
                $this->error('请求错误');
            }
            $product['created_at_str']=date('Y-m-d H:i',$product['created_at']);
            $product['product_type_name']=db('product_types')->where('id',$product['product_type_id'])->value('name');
            $product['supplier_name']=db('suppliers')->where('id',$product['supplier_id'])->value('name');
            $product['price_supply']=number_format($product['price_supply']/100,2,'.','');
            $product['price_sale']=number_format($product['price_sale']/100,2,'.','');

            $cates=Db::name('product_types')->field('id,parent_id,level,name')->where('level',config('mall.product_class_level'))->order('sort asc')->select();
            $cates=get_tree_ids($cates,'0',1);
            $sizes=db('product_sizes')->where('product_id',$id)->select();
            if($sizes){
                $is_sizes='1';
                foreach ($sizes as $key => $value) {
                    $sizes[$key]['price_supply']=number_format($value['price_supply']/100,2,'.','');
                    $sizes[$key]['price_sale']=number_format($value['price_sale']/100,2,'.','');
                }
            }else{
                $is_sizes='0';
            }
            $this->assign('is_sizes', $is_sizes);
            $this->assign('sizes', $sizes);
            $this->assign('cates', $cates);
            $this->assign('product', $product);
            $this->assign('page_title', db('admin_menu')->where('id','250')->value('title'));
            // 渲染模板输出
            return $this->fetch();
        }
    }
	public function edit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $product=Db::name('products')->where('audit_state','1')->where('state','in','1,2')->where('id',$data['id'])->find();
            if(!$product){
                exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
            }
            $now=time();
            //数据输入验证
            $validate = new Validate([
                'product_type_id|产品分类'  => 'require|number',
                'is_sizes|是否多规格' => 'require|in:0,1',
            ]);
            if (!$validate->check($data)) {
                exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
            }
            if($data['is_sizes']){
                $updateAll=[];
                $updateIdAll=[];
                $pix=true;
                foreach ($data as $key => $value) {
                    $arr=explode('_', $key);
                    if(isset($arr[0]) && isset($arr[1]) && isset($arr[2]) && $arr[0]=='price' && $arr[1]=='size' && $arr[2]>0){
                        if($value===''){
                            exit(json_encode(['data'=>[],'code'=>202,'msg'=>'每一项公开价格都必须填写']));
                        }
                        if($value<=0){
                            exit(json_encode(['data'=>[],'code'=>202,'msg'=>'公开价格都必须为大于0的数，可精确到分']));
                        }
                        if(!preg_match("/^\d{1,10}(\.\d{1,2})?$/",$value)){
                            exit(json_encode(['data'=>[],'code'=>202,'msg'=>'公开价格都必须为大于0的数，可精确到分']));
                        }
                        if($pix){
                            $price_sale=$value*100;
                            $pix=false;
                        }
                        $updateAll[]=['price_sale'=>$value*100,'updated_at'=>$now];
                        $updateIdAll[]=$arr[2];
                    }
                }
                foreach ($updateAll as $key => $value) {
                    Db::name('product_sizes')->where('product_id',$data['id'])->where('id',$updateIdAll[$key])->update($value);
                }
                $update=[];
                $update['id']=$data['id'];
                $update['product_type_id']=$data['product_type_id'];
                $update['price_sale']=$price_sale;
                $update['updated_at']=$now;
                $rt=Db::name('products')->update($update);
                if($rt!==false){
                    exit(json_encode(['data'=>[],'code'=>200,'msg'=>'商品修改成功','status'=>'0']));
                }else{
                    exit(json_encode(['data'=>[],'code'=>203,'msg'=>'数据库写入错误']));
                }
            }else{
                $validate = new Validate([
                    'price_sale|公开价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
                ]);
                if (!$validate->check($data)) {
                    exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
                }
                $update=[];
                $update['id']=$data['id'];
                $update['product_type_id']=$data['product_type_id'];
                $update['price_sale']=$data['price_sale']*100;
                $update['updated_at']=$now;
                $rt=Db::name('products')->update($update);
                if($rt!==false){
                    exit(json_encode(['data'=>[],'code'=>200,'msg'=>'商品修改成功','status'=>'0']));
                }else{
                    exit(json_encode(['data'=>[],'code'=>203,'msg'=>'数据库写入错误']));
                }
            }
           
        }
        if($id>0){
            $product=Db::name('products')->where('audit_state','1')->where('state','in','1,2')->find($id);
            if(!$product){
                $this->error('请求错误');
            }
            $product['created_at_str']=date('Y-m-d H:i',$product['created_at']);
            $product['product_type_name']=db('product_types')->where('id',$product['product_type_id'])->value('name');
            $product['supplier_name']=db('suppliers')->where('id',$product['supplier_id'])->value('name');
            $product['price_supply']=number_format($product['price_supply']/100,2,'.','');
            $product['price_sale']=number_format($product['price_sale']/100,2,'.','');

            $cates=Db::name('product_types')->field('id,parent_id,level,name')->where('level',config('mall.product_class_level'))->order('sort asc')->select();
            $cates=get_tree_ids($cates,'0',1);
            $sizes=db('product_sizes')->where('product_id',$id)->select();
            if($sizes){
                $is_sizes='1';
                foreach ($sizes as $key => $value) {
                    $sizes[$key]['price_supply']=number_format($value['price_supply']/100,2,'.','');
                    $sizes[$key]['price_sale']=number_format($value['price_sale']/100,2,'.','');
                }
            }else{
                $is_sizes='0';
            }
            $this->assign('is_sizes', $is_sizes);
            $this->assign('sizes', $sizes);
            $this->assign('cates', $cates);
            $this->assign('product', $product);
            $this->assign('page_title', db('admin_menu')->where('id','248')->value('title'));
            // 渲染模板输出
            return $this->fetch();
        }
    }
}