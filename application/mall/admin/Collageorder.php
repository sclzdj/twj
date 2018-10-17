<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Collageorder extends Admin
{
	public function index(){  
		$search_order=input('order','created_at desc,id desc');
		$order=$search_order;
        $search_state=input('state','-1');
        $search_deliver_state=input('deliver_state','-1');
        $search_created_start=trim(input('created_start',''));
        $search_created_end=trim(input('created_end',''));
        $search_keywords=trim(input('keywords',''));
        $search_user_id=input('user_id','0');
        $search_collage_product_id=input('collage_product_id','0');
        $map = [];
        if($search_state>=0){
        	$map['state']=$search_state;
        }
        if($search_deliver_state>=0){
        	$map['deliver_state']=$search_deliver_state;
        }
        $search_user_name='';
        if($search_user_id>0){
        	$map['user_id']=$search_user_id;
        	$search_user_name=db('users')->where('id',$search_user_id)->value('name');
        	if($search_user_name===null){
        		$search_user_name='<i style="color:#999;">无效用户</i>';
        	}
        	
        }
        $search_collage_product_title='';
        if($search_collage_product_id>0){
        	$map['collage_product_id']=$search_collage_product_id;
        	$search_collage_product_title=db('collage_products')->where('id',$search_collage_product_id)->value('title');
        	if($search_collage_product_title===null){
        		$search_collage_product_title='<i style="color:#999;">无效产品</i>';
        	}
        }
        if($search_keywords!==''){
        	$map['sn']=['like','%'.$search_keywords.'%'];
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
        $limit=Db::name('admin_config')->where('name','list_rows')->value('value');
		$data_list = Db::name('collage_orders')->where($map)->order($order)->paginate($limit);
		// 获取分页显示
		$page = $data_list->render();

		$data_list = json_decode(json_encode($data_list),TRUE);
		$data_list=$data_list['data'];

		foreach ($data_list as $key => $value) {
			$collage_product=db('collage_products')->where('id',$value['collage_product_id'])->find();
			$admin_attachment_ids=explode(',',$collage_product['admin_attachment_ids']);
			$data_list[$key]['collage_product_pic']=get_file_path($admin_attachment_ids[0]);
			$data_list[$key]['collage_product_title']=$collage_product['title'];
			$data_list[$key]['collage_product_spec']=$collage_product['spec'];
			$data_list[$key]['collage_product_size']=$collage_product['size'];
			$data_list[$key]['collage_price']=number_format($collage_product['collage_price']/100,2,'.','');
			$data_list[$key]['price']=number_format($collage_product['price']/100,2,'.','');
			$data_list[$key]['amount']=number_format($value['amount']/100,2,'.','');
			$data_list[$key]['difference_price']=number_format($value['difference_price']/100,2,'.','');
			$order_address=db('order_addresses')->where('id',$value['order_address_id'])->find();
			$data_list[$key]['receiver']=$order_address['receiver'];
			$data_list[$key]['receive_mobile']=$order_address['mobile'];
			$data_list[$key]['receive_address']=db('regions')->where('id',$order_address['province_id'])->value('name').' '.db('regions')->where('id',$order_address['city_id'])->value('name').' '.db('regions')->where('id',$order_address['area_id'])->value('name');
			$data_list[$key]['street']=$order_address['street'];
			$data_list[$key]['username']=db('users')->where('id',$value['user_id'])->value('name');
			$data_list[$key]['express_company']=db('expresses')->where('id',$value['express_id'])->value('name');
			$data_list[$key]['created_at_str']=date('Y-m-d H:i',$value['created_at']);
			$data_list[$key]['paid_at_str']=date('Y-m-d H:i',$value['paid_at']);
			switch ($value['state']) {
				case '0':
					$data_list[$key]['state_str']='待付款';
					break;
				case '1':
					$data_list[$key]['state_str']='进行中';
					break;
				case '2':
					$data_list[$key]['state_str']='拼单成功';
					break;
				case '3':
					$data_list[$key]['state_str']='待处理';
					break;
				case '4':
					$data_list[$key]['state_str']='已退款';
					break;
				case '5':
					$data_list[$key]['state_str']='已购买';
					break;
				default:
					$data_list[$key]['state_str']='错误状态';
					break;
			}
			if(in_array($value['state'], array('2','5'))){
				$data_list[$key]['deliver_status']=$value['deliver_state'];
			}else{
				$data_list[$key]['deliver_status']='-1';
			}
			switch ($value['deliver_state']) {
				case '0':
					$data_list[$key]['deliver_str']='未发货';
					break;
				case '1':
					$data_list[$key]['deliver_str']='已发货';
					break;
				case '2':
					$data_list[$key]['deliver_str']='货已到店';
					break;
				case '3':
					$data_list[$key]['state_str']='已确认收货';
					break;
				default:
					$data_list[$key]['state_str']='错误状态';
					break;
			}
		}
		$expresses=db('expresses')->order('rank asc')->select();
		// 模板变量赋值
		$this->assign('expresses', $expresses);
		$this->assign('data_list', $data_list);
		$this->assign('page', $page);
		$this->assign('search_order', $search_order);
		$this->assign('search_state', $search_state);
		$this->assign('search_deliver_state', $search_deliver_state);
		$this->assign('search_created_start', $search_created_start);
		$this->assign('search_created_end', $search_created_end);
		$this->assign('search_keywords', $search_keywords);
		$this->assign('search_user_id', $search_user_id);
		$this->assign('search_collage_product_id', $search_collage_product_id);
		$this->assign('search_user_name', $search_user_name);
		$this->assign('search_collage_product_title', $search_collage_product_title);
		$this->assign('empty','<div style="height:250px;line-height:250px;text-align:center;font-size:30px;color:#666;background:#fff;">！没有数据</div>');
		$this->assign('page_title', db('admin_menu')->where('id','245')->value('title'));
		// 渲染模板输出
		return $this->fetch();
	}
	public function deliver($action=''){
		//判断是否为post请求
		if (Request::instance()->isPost()) {
			//获取请求的post数据
			$data=input('post.');
			// 查处数据
			$collage_order=Db::name("collage_orders")->where('state','in',['2','5'])->where('id',$data['id'])->find();
			if(!$collage_order){
				exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
			}
			$now=time();
			switch ($action) {
				case 'deliver':
					$msg='发货';
					break;
				case 'edit':
					$msg='修改';
					break;
				default:
					exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
					break;
			}
			//数据输入验证
			$validate = new Validate([
			    'express_id|物流公司'  => 'require',
			    'express_sn|物流单号'  => 'require',
			]);
			if (!$validate->check($data)) {
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
			}
			//数据处理
			$update=array();
			$update['id']=$data['id'];
			$update['express_id']=$data['express_id'];
			$update['express_sn']=$data['express_sn'];
			$update['deliver_state']='1';
			$update['updated_at']=$now;
			//数据更新
			$rt=Db::name("collage_orders")->update($update);
			//跳转
			if($rt!==false){
				if($action=='deliver'){
					$express_company=db('expresses')->where('id',$data['express_id'])->value('name');
					Db::name("collage_order_events")->insertGetId(['collage_order_id'=>$data['id'],'event'=>'商城已发货（'.$express_company.'：'.$data['express_sn'].'）','created_at'=>$now,'updated_at'=>$now]);
					db("letters")->insertGetId(['order_id'=>$data['id'],'type'=>'2','user_id'=>$collage_order['user_id'],'title'=>'订单发货','content'=>'您的拼单订单已经发货','created_at'=>$now,'updated_at'=>$now]);
				}else{
					Db::name("collage_order_events")->where(['collage_order_id'=>$data['id']])->where('event','like','%商城已发货%')->update(['event'=>'商城已发货（'.$data['express_id'].'：'.$data['express_sn'].'）','updated_at'=>$now]);
				}
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>$msg.'成功']));
	        } else {
	            exit(json_encode(['data'=>[],'code'=>203,'msg'=>$msg.'失败']));
	        }
		}
	}
}