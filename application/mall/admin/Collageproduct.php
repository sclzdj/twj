<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Collageproduct extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id desc';
        }
        $map = $this->getMap();
		$data_list = Db::name('collage_products')->where($map)->order($order)->paginate();
		$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('当前人数的统计不包括待付款、待处理和已退款三种情况') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('collage_products') // 指定数据表名
        	->addOrder('id,start_at,end_at') // 添加排序
        	->addTopSelect('status', '全部拼单状态', ['0'=>'进行中','1'=>'拼单成功','2'=>'拼单失败']) //添加顶部下拉筛选
        	->setSearch(['id' => 'ID', 'title' => '产品名称'], '', '', '搜索') // 设置搜索参数
        	->addColumns([
        			['id', 'ID'], 
        			['title', '产品名称'],
        			['collage_price', '拼单价格','callback',function($value){
                        return number_format($value/100,2,'.','').'元';
                    }],
        			['price', '购买价格','callback',function($value){
                        return number_format($value/100,2,'.','').'元';
                    }],
        			['require_orders', '拼单要求'], 
        			['collage_orders', '当前人数','callback',function($value,$data){
        				return db('collage_orders')->where(['collage_product_id'=>$data['id']])->where('state','in','1,2,5')->count('id');
        			},'__data__'], 
        			['start_at', '开始时间','datetime', '未知','Y-m-d H:i'], 
        			['end_at', '截止时间','datetime', '未知','Y-m-d H:i'], 
        			['status','拼单状态','status', '', ['进行中','拼单成功','拼单失败:danger']],
        			['audit_status', '判定', 'callback',function($value,$data,$menu_id){
        				if($data['status']==0){
        					if(!if_menu_auth($menu_id)) return '';
        					return "<a href='".url('audit',['ids'=>$data['id'],'status'=>'1'])."' class='btn btn-xs btn-default ajax-get confirm'>判定成功</a><a href='".url('audit',['ids'=>$data['id'],'status'=>'2',])."' class='btn btn-xs btn-default ajax-get confirm'>判定失败</a>";
        				}else{
        					return '已判定';
        				}
        			},'__data__','244'],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	//->addRightButton('custom',['title'=>'查看详情','href'=>url('look',['id'=>'__ID__'])],true)
        	->addRightButton('edit',['title'=>'修改产品']) 
        	->addRightButton('custom',['title'=>'查看订单','href'=>url('mall/collageorder/index',['collage_product_id'=>'__ID__'])])
        	->addTopButton('add',['title'=>'新建产品'])  
        	//->addTopButton('custom',['title'=>'判定成功','href'=>url('audit',['status'=>'1']),'icon'=>'fa fa-fw fa-calendar-check-o','class'=>'btn btn-primary ajax-post confirm'])
        	//->addTopButton('custom',['title'=>'判定失败','href'=>url('audit',['status'=>'2']),'icon'=>'fa fa-fw fa-calendar-times-o','class'=>'btn btn-primary ajax-post confirm'])
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	/*public function look($id=''){
		$collage_product=Db::name("collage_products")->where('id',$id)->find();
		if(!$collage_product){
			return $this->error('请求错误');
		}
		// 使用ZBuilder快速创建表单
		
	}*/
	public function audit(){
		$status=input('status','0');
		if($status!='1' && $status!='2'){
			return $this->error('请求错误');
		}
		$now=time();
		$ids = (Request::instance()->isGet()) ? input('ids') : input('post.ids/a');
		$ids=(array)$ids;
		foreach ($ids as $key => $value) {
			if(Db::name('collage_products')->where('id',$value)->value('status')!='0'){
				unset($ids[$key]);
			}
		}
		$rt=Db::name('collage_products')->where('id','in',$ids)->update(['status'=>$status,'run_at'=>$now,'updated_at'=>$now]);
		if($rt!==false){
			if($status=='1'){
				//判定订单状态
				Db::name('collage_orders')->where('collage_product_id','in',$ids)->where('state','1')->update(['state'=>'2','run_at'=>$now,'updated_at'=>$now]);
				//记录订单事件
				$collage_order_ids=Db::name('collage_orders')->field('id')->where('collage_product_id','in',$ids)->select();
				foreach ($collage_order_ids as $key => $value) {
					Db::name('collage_order_events')->insertGetId(['collage_order_id'=>$value['id'],'event'=>'拼单成功','created_at'=>$now,'updated_at'=>$now]);
				}
				$tips='成功';
			}else{
				//判定订单状态
				Db::name('collage_orders')->where('collage_product_id','in',$ids)->where('state','1')->update(['state'=>'3','run_at'=>$now,'updated_at'=>$now]);
				//记录订单事件
				$collage_order_ids=Db::name('collage_orders')->field('id')->where('collage_product_id','in',$ids)->select();
				foreach ($collage_order_ids as $key => $value) {
					Db::name('collage_order_events')->insertGetId(['collage_order_id'=>$value['id'],'event'=>'拼单失败','created_at'=>$now,'updated_at'=>$now]);
				}
				$tips='失败';
			}
			return $this->success('已判定为拼单'.$tips);
        } else {
            return $this->error('数据库写入错误');
        }
	}
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			$now=time();
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'title|标题'=> 'require|length:1,20|unique:collage_products',
			    'weight|重量'  => 'require|gt:0|regex:^\d{1,5}(\.\d{1,3})?$',
			    'bland|品牌'  => 'require',
			    'require_orders|拼单要求'  => 'require|gt:0|regex:^\d{1,10}$',
			    'collage_price|拼单价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
			    'price|购买价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
			    'end_at|截止时间'  => 'require',
			    'admin_attachment_ids|图片'  => 'require',
			    'detail|产品详情' => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			if ($data['collage_price']>=$data['price']) {
			    return $this->error('拼单价格必须小于购买价格');
			}
			if($data['start_at']==''){
				$data['start_at']=$now;
			}else{
				$data['start_at']=strtotime($data['start_at']);
				if ($data['start_at']<$now) {
				    return $this->error('开始时间必须在发布时间之后');
				}
			}
			$data['end_at']=strtotime($data['end_at']);
			if ($data['end_at']<=$data['start_at']) {
			    return $this->error('截止时间必须在开始时间之后');
			}
			if (count(explode(',',$data['admin_attachment_ids']))>5) {
			    return $this->error('图片最多只能上传五张');
			}
			//数据处理
			$insert=array();
			$insert['title']=$data['title'];
			$insert['spec']=$data['spec'];
			$insert['size']=$data['size'];
			$insert['weight']=$data['weight'];
			$insert['bland']=$data['bland'];
			$insert['require_orders']=$data['require_orders'];
			$insert['collage_price']=$data['collage_price']*100;
			$insert['price']=$data['price']*100;
			$insert['start_at']=$data['start_at'];
			$insert['end_at']=$data['end_at'];
			$insert['admin_attachment_ids']=$data['admin_attachment_ids'];
			$insert['detail']=$_POST['detail'];
			$insert['created_at']=$now;
			$insert['updated_at']=$now;
			//数据更新
			$insert_id=Db::name("collage_products")->insertGetId($insert);
			//跳转
			if($insert_id>0){
				return $this->success('发布拼单产品成功',url('index'));
	        } else {
	            return $this->error('发布拼单产品失败');
	        }
		}
		
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('新建拼单产品') // 设置页面标题
			->setPageTips('请认真编辑相关信息') // 设置页面提示信息
			->setBtnTitle('submit', '发布') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addText('title', '标题','必填，限制在20个字以内')
			->addText('spec', '规格描述','')
			->addText('size', '尺寸','')
			->addText('weight', '重量','必填，规则范围0.001~99999.999', '', ['', 'KG'])
			->addText('bland', '品牌','必填')
			->addText('require_orders', '拼单要求','必填，即最低订单笔数，规则范围1~9999999999')
			->addText('collage_price', '拼单价格','必填，需小于购买价格，规则范围0.01~9999999999.99，<b>有人下单之后不能修改</b>', '', ['<i class="fa fa-fw fa-yen"></i>', ''])
			->addText('price', '购买价格','必填，规则范围0.00~9999999999.99，<b>有人下单之后不能修改</b>', '', ['<i class="fa fa-fw fa-yen"></i>', ''])
			->addDatetime('start_at', '开始时间', '需为确定发布之后的时间，不选则为发布时间')
			->addDatetime('end_at', '截止时间', '必选，需为开始时间之后的时间')
			->addImages('admin_attachment_ids', '图片','必传，最多上传五张图片，第一张为主图')
			->addUeditor('detail', '产品详情','')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	public function edit($id=''){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			$now=time();
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
				'title|标题'=> 'require|length:1,20|unique:collage_products',
			    'weight|重量'  => 'require|gt:0|regex:^\d{1,5}(\.\d{1,3})?$',
			    'bland|品牌'  => 'require',
			    'require_orders|拼单要求'  => 'require|gt:0|regex:^\d{1,10}$',
			    'collage_price|拼单价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
			    'price|购买价格'  => 'require|gt:0|regex:^\d{1,10}(\.\d{1,2})?$',
			    'end_at|截止时间'  => 'require',
			    'admin_attachment_ids|图片'  => 'require',
			    'detail|产品详情' => 'require'
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			if ($data['collage_price']>=$data['price']) {
			    return $this->error('拼单价格必须小于购买价格');
			}
			if($data['start_at']==''){
				$data['start_at']=$now;
			}else{
				$data['start_at']=strtotime($data['start_at']);
				if ($data['start_at']<db('collage_products')->where('id',$data['id'])->value('start_at')) {
				    return $this->error('开始时间不能小于原来的开始时间');
				}
			}
			$data['end_at']=strtotime($data['end_at']);
			if ($data['end_at']<$data['start_at']) {
			    return $this->error('截止时间不能小于开始时间');
			}
			if (count(explode(',',$data['admin_attachment_ids']))>5) {
			    return $this->error('图片最多只能上传五张');
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['title']=$data['title'];
			$update['spec']=$data['spec'];
			$update['size']=$data['size'];
			$update['weight']=$data['weight'];
			$update['bland']=$data['bland'];
			$update['require_orders']=$data['require_orders'];
			$update['collage_price']=$data['collage_price']*100;
			$update['price']=$data['price']*100;
			$update['start_at']=$data['start_at'];
			$update['end_at']=$data['end_at'];
			$update['admin_attachment_ids']=$data['admin_attachment_ids'];
			$update['detail']=$_POST['detail'];
			$update['updated_at']=$now;
			//数据更新
			$rt=Db::name("collage_products")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('修改拼单产品成功',url('index'));
	        } else {
	            return $this->error('修改拼单产品失败');
	        }
		}
		
		// 接收id
		if ($id>0) {
			// 查处数据
			$collage_product=Db::name("collage_products")->where('id',$id)->find();
			if(!$collage_product){
				return $this->error('请求错误');
			}
			$order=Db::name("collage_orders")->where('collage_product_id',$id)->where('state','in','0,1,2,3,4,5')->find();
			if($order){
				$attr='readonly';
				$tips1='<b>已有人下单不能修改</b>';
				$tips2='<b>已有人下单不能修改</b>';
			}else{
				$attr='';
				$tips1='必填，需小于购买价格，规则范围0.01~9999999999.99，<b>有人下单之后不能修改</b>';
				$tips2='必填，规则范围0.00~9999999999.99，<b>有人下单之后不能修改</b>';
			}
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('修改拼单产品') // 设置页面标题
				->setPageTips('请认真修改相关信息') // 设置页面提示信息
				->setBtnTitle('submit', '修改') //修改默认按钮标题
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addText('title', '标题','必填，限制在20个字以内',$collage_product['title'])
				->addText('spec', '规格描述','',$collage_product['spec'])
				->addText('size', '尺寸','',$collage_product['size'])
				->addText('weight', '重量','必填，规则范围0.001~99999.999',$collage_product['weight'], ['', 'KG'])
				->addText('bland', '品牌','必填',$collage_product['bland'])
				->addText('require_orders', '拼单要求','必填，即最低订单笔数，规则范围1~9999999999',$collage_product['require_orders'])
				->addText('collage_price', '拼单价格',$tips1, number_format($collage_product['collage_price']/100,2,'.',''), ['<i class="fa fa-fw fa-yen"></i>', ''],$attr)
				->addText('price', '购买价格',$tips2, number_format($collage_product['price']/100,2,'.',''), ['<i class="fa fa-fw fa-yen"></i>', ''],$attr)
				->addDatetime('start_at', '开始时间', '需为原开始时间之后的时间，不选则为确定修改时间',date('Y-m-d H:i',$collage_product['start_at']))
				->addDatetime('end_at', '截止时间', '必选，需为开始时间之后的时间',date('Y-m-d H:i',$collage_product['end_at']))
				->addImages('admin_attachment_ids', '图片','必传，最多上传五张图片，第一张为主图',$collage_product['admin_attachment_ids'])
				->addUeditor('detail', '产品详情','',$collage_product['detail'])
				->addHidden('id',$collage_product['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
}