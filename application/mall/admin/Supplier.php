<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Supplier extends Admin
{
	public function index($status='0'){  
        $list_tab = [
            '0' => ['title' => '已通过', 'url' => url('index', ['status' => '0'])],
            '1' => ['title' => '待审核', 'url' => url('index', ['status' => '1'])],
            '2' => ['title' => '驳回', 'url' => url('index', ['status' => '2'])],
        ];
        switch ($status) {
            case '0':
                $order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                //省市联动条件处理
		        if(isset($map['province_id']) && $map['province_id']>0){
		        	if(isset($map['city_id']) && $map['city_id']>0){
		        		$parent_id=db('regions')->where('id',$map['city_id'])->value('parent_id');
		        		if($parent_id!=$map['province_id']){
		        			unset($map['city_id']);
		        			if(isset($map['area_id'])){
				        		unset($map['area_id']);
				        	}
		        		}else{
		        			if(isset($map['area_id']) && $map['area_id']>0){
				        		$parent_id=db('regions')->where('id',$map['area_id'])->value('parent_id');
				        		if($parent_id!=$map['city_id']){
				        			unset($map['area_id']);
				        		}
				        	}
		        		}
		        	}else{
		                if(isset($map['area_id'])){
		                    unset($map['area_id']);
		                }
		            }
		        }else{
		        	if(isset($map['city_id'])){
		        		unset($map['city_id']);
		        	}
		        	if(isset($map['area_id'])){
		        		unset($map['area_id']);
		        	}
		        }

				//省份
				$province_selects=[];
				$provinces=db('regions')->where('level',1)->select();
				foreach ($provinces as $key => $value) {
					$province_selects[$value['id']]=$value['name'];
				}
				//地市
				$city_selects=[];
				if(isset($map['province_id']) && $map['province_id']>0){
					$citys=db('regions')->where('level',2)->where('parent_id',$map['province_id'])->select();
					
					foreach ($citys as $key => $value) {
						$city_selects[$value['id']]=$value['name'];
					}
				}
				//区县
				$area_selects=[];
				if(isset($map['city_id']) && $map['city_id']>0){
					$areas=db('regions')->where('level',3)->where('parent_id',$map['city_id'])->select();
					foreach ($areas as $key => $value) {
						$area_selects[$value['id']]=$value['name'];
					}
				}
                $data_list = Db::name('suppliers')->where('audit_state','1')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('suppliers') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
		        	->addTopSelect('province_id', '选择省份', $province_selects) //添加顶部下拉筛选
		        	->addTopSelect('city_id', '选择地市', $city_selects) //添加顶部下拉筛选
		        	->addTopSelect('area_id', '选择区县', $area_selects) //添加顶部下拉筛选
                    ->addTopSelect('state', '全部状态', ['1'=>'正常','0'=>'禁用']) //添加顶部下拉筛选
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','serial_num'=>'编号', 'name' => '公司名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'], 
                            ['serial_num', '编号'],
                            ['name', '名称'],
                            ['region', '区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'],
                            ['linkman', '联系人'],
                            ['mobile', '联系电话'], 
                            ['type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['rebate', '返点比例','callback',function($value){
                            	return $value.'%';
                            }],
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                            ['state','状态','switch'],
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('custom',['title'=>'查看详情','href'=>url('look',['id'=>'__ID__'])],true) 
                    ->addRightButton('edit',['title'=>'修改'])
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
                $data_list = Db::name('suppliers')->where('audit_state','0')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('suppliers') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','name' => '名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'], 
                            ['name', '名称'],
                            ['region', '区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'],
                            ['linkman', '联系人'],
                            ['mobile', '联系电话'], 
                            ['type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                            ['right_button', '操作', 'btn'],
                        ]) //添加多列数据
                    ->addRightButton('custom',['title'=>'审核','href'=>url('audit',['id'=>'__ID__'])]) 
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            case '2':
            	$order = $this->getOrder();
                if($order===''){
                    $order='id desc';
                }
                $map = $this->getMap();
                $data_list = Db::name('suppliers')->where('audit_state','2')->where($map)->order($order)->paginate();
                $page = $data_list->render();
                return ZBuilder::make('table')
                    ->setPageTitle('') // 设置页面标题
                    ->setPageTips('') // 设置页面提示信息
                    ->setTabNav($list_tab,  $status)//分组
                    ->hideCheckbox() //隐藏第一列多选框
                    ->setTableName('suppliers') // 指定数据表名
                    ->addOrder('id,created_at') // 添加排序
                    ->addTimeFilter('created_at') // 添加时间段筛选
                    ->setSearch(['id' => 'ID','name' => '名称'], '', '', '搜索') // 设置搜索参数
                    ->addColumns([
                            ['id', 'ID'], 
                            ['name', '名称'],
                            ['region', '区域','callback',function($value,$data){
                                $province=db('regions')->where('id',$data['province_id'])->value('name');
                                $city=db('regions')->where('id',$data['city_id'])->value('name');
                                $area=db('regions')->where('id',$data['area_id'])->value('name');
                                return $province.' '.$city.' '.$area;
                            }, '__data__'],
                            ['linkman', '联系人'],
                            ['mobile', '联系电话'], 
                            ['type_id', '分类','callback',function($value){
                                return db('product_types')->where('id',$value)->value('name');
                            }], 
                            ['auditmsg','上次驳回原因','callback',function($value,$data){
                                return db('supplier_audits')->where('supplier_id',$data['id'])->order('audit_at desc')->value('auditmsg');
                            },'__data__'],
                            ['created_at', '创建时间','datetime', '未知','Y-m-d H:i'], 
                        ]) //添加多列数据
                    ->setRowList($data_list) // 设置表格数据
                    ->setPages($page) // 设置分页数据
                    ->fetch();
                break;
            default:
                break;
        }
    		
	}
    public function audit($id=''){
        //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            $supplier=Db::name("suppliers")->where('audit_state','0')->where('id',$data['id'])->find();
            if(!$supplier){
                return $this->error('请求错误');
            }
            //数据输入验证
            $validate = new Validate([
                'audit_state|审核'  => 'require|in:1,2',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            $update=array();
            if($data['audit_state']=='1'){
                $validate = new Validate([
                    'rebate|返点比例'  => [
                    	'require',
                    	'egt'=>'0',
                    	'elt'=>'100',
                    	'regex'=>'/^(((\d|[1-9]\d)(\.\d{1,2})?)|100|100.0|100.00)$/',
                    ],
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                $update['rebate']=$data['rebate'];
                $update['serial_num']=get_supplier_serial_num();
            }
            if($data['audit_state']=='2'){
                $validate = new Validate([
                    'auditmsg|驳回理由'  => 'require|length:1,20',
                ]);
                if (!$validate->check($data)) {
                    return $this->error($validate->getError());
                }
                Db::name('supplier_audits')->insertGetId(['supplier_id'=>$data['id'],'auditmsg'=>$data['auditmsg'],'audit_at'=>$now]);
            }
            //数据处理
            $update['id']=$data['id'];
            $update['audit_state']=$data['audit_state'];
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("suppliers")->update($update);
            //跳转
            if($rt!==false){
                //正式发短信
                if($supplier['mobile']!=''){
                    import('aliyun_sms.SmsDemo');
                    if($data['audit_state']=='1'){
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125017389", // 短信模板编号
                                $supplier['mobile'], // 短信接收者
                                Array(
                                    "serial_num"=>$update['serial_num'],
                                ),
                                "001"   // 流水号,选填
                        );
                    }else{
                        $response = \SmsDemo::sendSms(
                                "五金商城", // 短信签名
                                "SMS_125027329", // 短信模板编号
                                $supplier['mobile'], // 短信接收者
                                Array(
                                    "auditmsg"=>trim(trim($data['auditmsg'],'。'),'.'),
                                ),
                                "001"   // 流水号,选填
                        );
                    }    
                }
                return $this->success('审核成功',url('index',['status'=>$data['audit_state']=='2'?'2':'0']));
            } else {
                return $this->error('审核失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $supplier=Db::name("suppliers")->where('audit_state','0')->where('id',$id)->find();
            if(!$supplier){
                return $this->error('请求错误');
            }
            $province=db('regions')->where('id',$supplier['province_id'])->value('name');
            $city=db('regions')->where('id',$supplier['city_id'])->value('name');
            $area=db('regions')->where('id',$supplier['area_id'])->value('name');
            $region=$province.' '.$city.' '.$area;
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('供应商审核') // 设置页面标题
                ->setPageTips('请认真审核相关信息') // 设置页面提示信息
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                //->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addStatic('name', '公司名称','',$supplier['name'])
                ->addStatic('linkman', '联系人','',$supplier['linkman'])
                ->addStatic('mobile', '联系电话','',$supplier['mobile'])
                ->addStatic('region', '区域','',$region)
                ->addStatic('street', '公司地址','',$supplier['street'])
                ->addStatic('type_id', '主营品类','',db('product_types')->where('id',$supplier['type_id'])->value('name'))
                ->addStatic('idcard_pic', '身份证','',staticText($supplier['idcard_pic'],'img'))
                ->addStatic('license', '营业执照','',staticText($supplier['license'],'img'))
                ->addRadio('audit_state', '审核', '必选', ['1' => '通过', '2' => '驳回'])
                ->addText('rebate', '返点比例','必填,百分比，规则范围：0.00~~100.00','',['','%'])
                ->addText('auditmsg', '驳回理由','必填，限制在20字以内')
                ->addHidden('id',$supplier['id'])
                ->setTrigger('audit_state', '1', 'rebate',false)
                ->setTrigger('audit_state', '2', 'auditmsg',false)
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
	public function edit($id=''){
         //判断是否为post请求
        if (Request::instance()->isPost()) {
            $now=time();
            //获取请求的post数据
            $data=input('post.');
            // 查处数据
            $supplier=Db::name("suppliers")->where('audit_state','1')->where('id',$data['id'])->find();
            if(!$supplier){
                return $this->error('请求错误');
            }
            //数据输入验证
            $validate = new Validate([
            	'name|公司名称'  => ['require'],
            	'linkman|联系人'  => ['require'],
            	'mobile|联系电话'  => ['require','unique'=>'suppliers'],
            	'region|区域'  => ['require','number'],
            	'street|公司地址'  => ['require'],
            	'type_id|主营品类'  => ['require','number'],
                'rebate|返点比例'  => [
                	'require',
                	'egt'=>'0',
                	'elt'=>'100',
                	'regex'=>'/^(((\d|[1-9]\d)(\.\d{1,2})?)|100|100.0|100.00)$/',
                ],
                'state|状态'  => 'require|in:0,1',
            ]);
            if (!$validate->check($data)) {
                return $this->error($validate->getError());
            }
            //数据处理
            $update=array();
            $update['id']=$data['id'];
            $update['name']=$data['name'];
            $update['linkman']=$data['linkman'];
            $update['mobile']=$data['mobile'];
            $update['area_id']=$data['region'];
            $update['city_id']=db('regions')->where('id',$data['region'])->value('parent_id');
            $update['province_id']=db('regions')->where('id',$update['city_id'])->value('parent_id');
            $update['street']=$data['street'];
            $update['type_id']=$data['type_id'];
            $update['rebate']=$data['rebate'];
            $update['state']=$data['state'];
            $update['updated_at']=$now;
            //数据更新
            $rt=Db::name("suppliers")->update($update);
            //跳转
            if($rt!==false){
                return $this->success('修改成功',url('index'));
            } else {
                return $this->error('修改失败');
            }
        }
        // 接收id
        if ($id>0) {
            // 查处数据
            $supplier=Db::name("suppliers")->where('audit_state','1')->where('id',$id)->find();
            if(!$supplier){
                return $this->error('请求错误');
            }
            $cates=Db::name('product_types')->field('id,parent_id,level,name')->where('level',config('mall.product_class_level'))->order('sort asc')->select();
            $cates=get_tree_ids($cates,'0',1);
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('修改供应商资料') // 设置页面标题
                ->setPageTips('请认真编辑相关信息') // 设置页面提示信息
                ->setBtnTitle('submit', '确定') //修改默认按钮标题
                //->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
                ->addText('name', '公司名称','必填',$supplier['name'])
                ->addText('linkman', '联系人','必填',$supplier['linkman'])
                ->addText('mobile', '联系电话','必填',$supplier['mobile'])
                ->addLinkages('region', '区域', '必选', 'regions', 4, $supplier['area_id'], 'id,name,parent_id')
                ->addText('street', '公司地址','必填',$supplier['street'])
                ->addSelect('type_id', '主营品类','必选',$cates,$supplier['type_id'])
                ->addText('rebate', '返点比例','必填,百分比，规则范围：0.00~~100.00',$supplier['rebate'],['','%'])
                ->addRadio('state', '状态', '必选', ['1' => '正常','0' => '禁用'],$supplier['state'])
                ->addHidden('id',$supplier['id'])
                ->setTrigger('audit_state', '2', 'auditmsg',false)
                //->isAjax(false) //默认为ajax的post提交
                ->fetch();
        }
    }
    public function look($id=''){
		$supplier=Db::name("suppliers")->where('audit_state','1')->where('id',$id)->find();
		if(!$supplier){
			return $this->error('请求错误');
		}
		$province=db('regions')->where('id',$supplier['province_id'])->value('name');
        $city=db('regions')->where('id',$supplier['city_id'])->value('name');
        $area=db('regions')->where('id',$supplier['area_id'])->value('name');
        $region=$province.' '.$city.' '.$area;
        $balance=db('supplier_capitals')->where('supplier_id',$id)->value('money_available');
        $order_num=db('orders')->where('supplier_id',$id)->where('state','in','1,3,4,5,8')->where('refund_state','in','-1,-2,-3,0,1,2,3,6,9,10,11')->count('id');
        $refund_order_num=db('orders')->where('supplier_id',$id)->where('state','in','1,3,4,5,9')->where('refund_state','in','4,5,7,8')->count('id');
		// 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('供应商详情') // 设置页面标题
            //->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
            ->hideBtn(['submit', 'back'])//隐藏按钮
            ->addStatic('serial_num', '供应商编号','',$supplier['serial_num'])
            ->addStatic('name', '公司名称','',$supplier['name'])
            ->addStatic('linkman', '联系人','',$supplier['linkman'])
            ->addStatic('mobile', '联系电话','',$supplier['mobile'])
            ->addStatic('region', '区域','',$region)
            ->addStatic('street', '公司地址','',$supplier['street'])
            ->addStatic('type_id', '主营品类','',db('product_types')->where('id',$supplier['type_id'])->value('name'))
            ->addStatic('street', '商品数','统计的是审核通过的商品数',db('products')->where('audit_state','1')->where('state','in','1,2')->count('id'))
            ->addStatic('street', '订单数','不包含成功退款退货和退款退货审核通过的订单',$order_num)
            ->addStatic('street', '退款订单数','只统计成功退款或退款审核通过的订单（包含退货订单）',$refund_order_num)
            ->addStatic('street', '账户余额','',number_format($balance/100,2,'.','').'元')
            //->addStatic('idcard_pic', '身份证','',staticText($supplier['idcard_pic'],'img'))
            ->addStatic('license', '营业执照','',staticText($supplier['license'],'img'))
            ->addStatic('created_at', '创建时间','',staticText($supplier['created_at'],'time'))
            //->isAjax(false) //默认为ajax的post提交
            ->fetch();
	}
}