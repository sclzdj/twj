<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class User extends Admin
{
	public function index(){  
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

		$data_list = Db::name('users')->where($map)->order($order)->paginate();
		$page = $data_list->render();
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
        return ZBuilder::make('table')
        	->setPageTitle('') // 设置页面标题
        	->setPageTips('') // 设置页面提示信息
        	->hideCheckbox() //隐藏第一列多选框
        	->setTableName('users') // 指定数据表名
        	->addOrder('id') // 添加排序
        	->addTopSelect('province_id', '选择省份', $province_selects) //添加顶部下拉筛选
        	->addTopSelect('city_id', '选择地市', $city_selects) //添加顶部下拉筛选
        	->addTopSelect('area_id', '选择区县', $area_selects) //添加顶部下拉筛选
        	->setSearch(['id' => 'ID', 'name' => '名称','mobile'=>'手机'], '', '', '搜索') // 设置搜索参数
        	->addColumns([
        			['id', 'ID'], 
        			['name', '用户名称'],
        			['mobile', '手机号码'],
        			['region', '所属地区','callback',function($value,$data){
        				$province=db('regions')->where('id',$data['province_id'])->value('name');
        				$city=db('regions')->where('id',$data['city_id'])->value('name');
        				$area=db('regions')->where('id',$data['area_id'])->value('name');
        				return $province.' '.$city.' '.$area;
        			}, '__data__'],
        			['product_type_id', '主营分类','callback',function($value){
        				return db('product_types')->where('id',$value)->value('name');
        			}], 
        			['right_button', '操作', 'btn'],
        		]) //添加多列数据
        	//->addRightButton('custom',['title'=>'查看详情','href'=>url('look',['id'=>'__ID__'])],true)
            ->addRightButton('custom',['title'=>'查看订单','href'=>url('mall/order/index',['user_id'=>'__ID__'])])  //
            ->addRightButton('custom',['title'=>'查看拼单订单','href'=>url('mall/collageorder/index',['user_id'=>'__ID__'])]) 
        	->setRowList($data_list) // 设置表格数据
        	->setPages($page) // 设置分页数据
        	->fetch();
	}
	/*public function look($id=''){
		$user=Db::name("users")->where('id',$id)->find();
		if(!$user){
			return $this->error('请求错误');
		}
		
		return '';
	}*/
}