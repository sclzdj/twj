<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Hotrecommend extends Admin
{
	public function index(){  
		$order = $this->getOrder();
        if($order===''){
            $order='id asc';
        }
        $map = $this->getMap();
		$data_list = Db::name('hotrecommends')->where($map)->order($order)->select();
		//$page = $data_list->render();
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('hotrecommends') // 指定数据表名
        	->addOrder('id') // 添加排序
        	->addColumns([
        			['id', '位置','callback',function($value){
        				return '位置'.$value;
        			}], 
        			['admin_attachment_id', '图片','picture','暂无图片'],
        			['product_link', '商品链接','callback',function($value){
        				return '<a target="_bank" href="'.$value.'">'.$value.'</a>';
        			}],
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	->addRightButtons(['edit']) 
        	->setRowList($data_list) // 设置表格数据
        	//->setPages($page) // 设置分页数据
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
			]);
			if (!$validate->check($data)) {
			    return $this->error($validate->getError());
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['admin_attachment_id']=$data['admin_attachment_id'];
			$update['product_link']=$data['product_link'];
			//数据更新
			$rt=Db::name("hotrecommends")->update($update);
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
			$hotrecommend=Db::name("hotrecommends")->where('id',$id)->find();
			if(!$hotrecommend){
				return $this->error('请求错误');
			}
			// 使用ZBuilder快速创建表单
			return ZBuilder::make('form')
				->setPageTitle('编辑热门推荐') // 设置页面标题
				->setPageTips('请认真编辑相关信息') // 设置页面提示信息
				//->setUrl('edit') // 设置表单提交地址
				//->hideBtn(['back']) //隐藏默认按钮
				->setBtnTitle('submit', '确定') //修改默认按钮标题
				->addBtn('<button type="reset" class="btn btn-default">重置</button>') //添加额外按钮
				->addStatic('weizhi', '位置', '', '位置'.$hotrecommend['id'])
				->addImage('admin_attachment_id', '图片','必传，推荐尺寸为'.$hotrecommend['size'],$hotrecommend['admin_attachment_id'])
				->addText('product_link', '商品链接','必填，请以http://或https://开头',$hotrecommend['product_link'])
				->addHidden('id',$hotrecommend['id'])
				//->isAjax(false) //默认为ajax的post提交
				->fetch();
		}
	}
}