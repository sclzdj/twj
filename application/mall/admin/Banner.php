<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Banner extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id desc';
        }
        $map = $this->getMap();
		$data_list = Db::name('banners')->where($map)->order($order)->paginate();
		$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->setTableName('banners') // 指定数据表名
        	->addOrder('id,sort') // 添加排序
        	->addColumns([
        			['id', 'ID'], 
        			['admin_attachment_id', '图片','picture','暂无图片'],
        			['product_link', '商品链接','callback',function($value){
        				return '<a target="_bank" href="'.$value.'">'.$value.'</a>';
        			}],
        			['sort', '排序'], 
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit','delete']) 
        	->addTopButtons(['add','delete']) 
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	public function add(){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
			    'admin_attachment_id|图片'  => 'require',
			    'product_link|商品链接'  => 'require',
			    'sort|排序' => 'require|regex:^[1-9]\d{0,9}$',
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$insert=array();
			$insert['admin_attachment_id']=$data['admin_attachment_id'];
			$insert['product_link']=$data['product_link'];
			$insert['sort']=$data['sort'];
			//数据更新
			$insert_id=Db::name("banners")->insertGetId($insert);
			//跳转
			if($insert_id>0){
				return $this->success('新增成功',url('index'));
	        } else {
	            return $this->error('新增失败');
	        }
		}
		
		// 使用ZBuilder快速创建表单
		return ZBuilder::make('form')
			->setPageTitle('新增轮播图') // 设置页面标题
			->setPageTips('请认真编辑相关信息') // 设置页面提示信息
			->setBtnTitle('submit', '确定') //修改默认按钮标题
			->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
			->addImage('admin_attachment_id', '图片','必传，推荐尺寸为1920*400')
			->addText('product_link', '商品链接','必填，请以http://或https://开头')
			->addText('sort', '排序','必填，请输入一个大于0的整数，前台以此升序取出数据','100')
			//->isAjax(false) //默认为ajax的post提交
			->fetch();
	}
	public function edit($id=''){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			//数据输入验证
			$validate = new Validate([
			    'admin_attachment_id|图片'  => 'require',
			    'product_link|商品链接'  => 'require',
			    'sort|排序' => 'require|regex:^[1-9]\d{0,9}$',
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['admin_attachment_id']=$data['admin_attachment_id'];
			$update['product_link']=$data['product_link'];
			$update['sort']=$data['sort'];
			//数据更新
			$rt=Db::name("banners")->update($update);
			//跳转
			if($rt!==false){
				return $this->success('编辑成功',url('index'));
	        } else {
	            return $this->error('编辑失败');
	        }
		}
		// 接收id
		if ($id>0) {
			// 查处数据
			$banner=Db::name("banners")->where('id',$id)->find();
			if(!$banner){
				return $this->error('请求错误');
			}
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('编辑轮播图') // 设置页面标题
				->setPageTips('请认真编辑相关信息') // 设置页面提示信息
				->setBtnTitle('submit', '确定') //修改默认按钮标题
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addImage('admin_attachment_id', '图片','必传，推荐尺寸为1920*400',$banner['admin_attachment_id'])
				->addText('product_link', '商品链接','必填，请以http://或https://开头',$banner['product_link'])
				->addText('sort', '排序','必填，请输入一个大于0的整数，前台以此升序取出数据',$banner['sort'])
				->addHidden('id',$banner['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
}