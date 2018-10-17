<?php
namespace app\mall\admin;

use app\admin\controller\Admin;
use think\Db;
use think\Request;
use think\Validate;
use app\common\builder\ZBuilder;


class Order extends Admin
{
	public function index(){  
		$search_order=input('order','a.created_at desc,a.id desc');
		$order=$search_order;
		//$search_is_purchase=input('is_purchase','-1');
        $search_state=input('state','-1');
        $search_refund_state=input('refund_state','-99');
        $search_created_start=trim(input('created_start',''));
        $search_created_end=trim(input('created_end',''));
        $search_keywords=trim(input('keywords',''));
        $search_user_id=input('user_id','0');
        $map = [];
        if($search_state>=0){
        	$map['a.state']=$search_state;
        }
        if($search_refund_state>-99){
        	$map['a.refund_state']=$search_refund_state;
        }
        $search_user_name='';
        if($search_user_id>0){
        	$map['a.user_id']=$search_user_id;
        	$search_user_name=db('users')->where('id',$search_user_id)->value('name');
        	if($search_user_name===null){
        		$search_user_name='<i style="color:#999;">无效用户</i>';
        	}
        	
        }
        /*if($search_is_purchase=='0' ||$search_is_purchase=='1'){
        	$map['a.is_purchase']=$search_is_purchase;
        }*/
        if($search_keywords!==''){
        	$where=" (a.order_sn like '%{$search_keywords}%' || c.serial_num like '%{$search_keywords}%' || e.mobile like '%{$search_keywords}%' || g.name like '%{$search_keywords}%') ";
        }else{
        	$where='';
        }
        if($search_created_start!=='' || $search_created_end!==''){
        	if($search_created_start!=='' && $search_created_end===''){
        		$map['a.created_at']=['>=',strtotime($search_created_start.' 00:00:00')];
        	}elseif($search_created_start==='' && $search_created_end!==''){
        		$map['a.created_at']=['<=',strtotime($search_created_end.' 23:59:59')];
        	}else{
        		$map['a.created_at']=['between',[strtotime($search_created_start.' 00:00:00'),strtotime($search_created_end.' 23:59:59')]];
        	}
        }
        $limit=Db::name('admin_config')->where('name','list_rows')->value('value');
		$data_list = Db::name('orders a')->field('a.*,b.name user_name,c.serial_num supplier_serial_num,c.name supplier_name,d.num product_num,d.unit product_unit,d.price_sale product_price,d.product_size_name,e.receiver,e.mobile receiver_mobile,e.province_id,e.city_id,e.area_id,e.street,f.name product_name,f.picture product_picture,f.price_supply product_price_supply,g.name express_name')->join('users b','a.user_id=b.id','left')->join('suppliers c','a.supplier_id=c.id','left')->join('order_products d','a.id=d.order_id','left')->join('order_addresses e','a.order_address_id=e.id','left')->join('products f','d.product_id=f.id','left')->join('expresses g','a.express_id=g.id','left')->where($map)->where($where)->order($order)->paginate($limit);
		// 获取分页显示
		$page = $data_list->render();

		$data_list = json_decode(json_encode($data_list),TRUE);
		$data_list=$data_list['data'];

		foreach ($data_list as $key => $value) {
			$data_list[$key]['receive_address']=db('regions')->where('id',$value['province_id'])->value('name').' '.db('regions')->where('id',$value['city_id'])->value('name').' '.db('regions')->where('id',$value['area_id'])->value('name');
			$data_list[$key]['created_at_str']=date('Y-m-d H:i',$value['created_at']);
			$data_list[$key]['paid_at_str']=date('Y-m-d H:i',$value['paid_at']);
			$data_list[$key]['refund_express_name']=db('expresses')->where('id',$value['refund_express_id'])->value('name');
			$data_list[$key]['barter_express_name']=db('expresses')->where('id',$value['barter_express_id'])->value('name');
			$data_list[$key]['product_price']=number_format($value['product_price']/100,2,'.','');
			$data_list[$key]['amount']=number_format($value['amount']/100,2,'.','');
			$data_list[$key]['amount_pay']=number_format($value['amount_pay']/100,2,'.','');
			$state_str='';
			$pix=-1;
			switch ($value['state']) {
				case '0':
					$state_str='待付款';$pix=0;
					break;
				case '1':
					if($value['refund_state']=='0' || $value['refund_state']=='-1'){
						if($value['is_purchase']==0 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待采购';$pix=1;
							}else{
								$state_str='已驳回退款申请&nbsp;待采购';$pix=2;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待供应商备货';$pix=3;
							}else{
								$state_str='已驳回退款申请&nbsp;待供应商备货';$pix=4;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==1){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商已备货';$pix=5;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商已备货';$pix=6;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==2){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商缺货';$pix=7;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商缺货';$pix=8;
							}
						}else{
							$state_str='错误状态';
						}
					}elseif($value['refund_state']=='1'){
						$state_str='申请退款';$pix=9;
					}elseif($value['refund_state']=='4'){
						$state_str='同意退款申请';$pix=10;
					}elseif($value['refund_state']=='7'){
						$state_str='已退款';$pix=11;
					}else{
						$state_str='错误状态';
					}
					break;
				/*case '2':
					$state_str='申请撤单';
					break;*/
				case '3':
					if($value['refund_state']=='0'){
						$state_str='已发货';$pix=12;
					}elseif($value['refund_state']=='-1'){
						$state_str='已发货&nbsp;已驳回退款申请';$pix=13;
					}elseif($value['refund_state']=='1'){
						$state_str='已发货&nbsp;申请退款';$pix=14;
					}elseif($value['refund_state']=='4'){
						$state_str='已发货&nbsp;同意退款申请';$pix=15;
					}elseif($value['refund_state']=='7'){
						$state_str='已发货&nbsp;已退款';$pix=16;
					}else{
						$state_str='错误状态';
					}
					break;
				case '4':
					if($value['refund_state']=='0'){
						$state_str='货已到店';$pix=17;
					}elseif($value['refund_state']=='-2'){
						$state_str='货已到店&nbsp;已驳回退货申请';$pix=18;
					}elseif($value['refund_state']=='2'){
						$state_str='货已到店&nbsp;申请退货';$pix=19;
					}elseif($value['refund_state']=='5'){
						$state_str='货已到店&nbsp;同意退货申请';$pix=20;
					}elseif($value['refund_state']=='8'){
						$state_str='货已到店&nbsp;已退货';$pix=21;
					}elseif($value['refund_state']=='-3'){
						$state_str='货已到店&nbsp;已驳回换货申请';$pix=22;
					}elseif($value['refund_state']=='3'){
						$state_str='货已到店&nbsp;申请换货';$pix=23;
					}elseif($value['refund_state']=='6'){
						$state_str='货已到店&nbsp;同意换货申请';$pix=24;
					}elseif($value['refund_state']=='9'){
						$state_str='货已到店&nbsp;已换货';$pix=25;
					}elseif($value['refund_state']=='10'){
						$state_str='货已到店&nbsp;已放弃退货';$pix=-1;
					}elseif($value['refund_state']=='11'){
						$state_str='货已到店&nbsp;已放弃换货';$pix=-2;
					}else{
						$state_str='错误状态';
					}
					break;
				case '5':
					if($value['refund_state']=='0'){
						$state_str='已收货';$pix=26;
					}elseif($value['refund_state']=='-2'){
						$state_str='已收货&nbsp;已驳回退货申请';$pix=27;
					}elseif($value['refund_state']=='2'){
						$state_str='已收货&nbsp;申请退货';$pix=28;
					}elseif($value['refund_state']=='5'){
						$state_str='已收货&nbsp;同意退货申请';$pix=29;
					}elseif($value['refund_state']=='8'){
						$state_str='已收货&nbsp;已退货';$pix=30;
					}elseif($value['refund_state']=='-3'){
						$state_str='已收货&nbsp;已驳回换货申请';$pix=31;
					}elseif($value['refund_state']=='3'){
						$state_str='已收货&nbsp;申请换货';$pix=32;
					}elseif($value['refund_state']=='6'){
						$state_str='已收货&nbsp;同意换货申请';$pix=33;
					}elseif($value['refund_state']=='9'){
						$state_str='已收货&nbsp;已换货';$pix=34;
					}elseif($value['refund_state']=='10'){
						$state_str='已收货&nbsp;已放弃退货';$pix=-3;
					}elseif($value['refund_state']=='11'){
						$state_str='已收货&nbsp;已放弃换货';$pix=-4;
					}else{
						$state_str='错误状态';
					}
					break;
				case '8':
					if($value['refund_state']=='0'){
						$state_str='交易成功';
					}elseif($value['refund_state']=='-1'){
						$state_str='交易成功&nbsp;已驳回退款申请';
					}elseif($value['refund_state']=='-2'){
						$state_str='交易成功&nbsp;已驳回退货申请';
					}elseif($value['refund_state']=='-3'){
						$state_str='交易成功&nbsp;已驳回换货申请';
					}elseif($value['refund_state']=='9'){
						$state_str='交易成功&nbsp;已换货';
					}elseif($value['refund_state']=='10'){
						$state_str='交易成功&nbsp;已放弃退货';
					}elseif($value['refund_state']=='11'){
						$state_str='交易成功&nbsp;已放弃换货';
					}else{
						$state_str='交易成功';
					}
					$pix=35;
					break;
				case '9':
					if($value['refund_state']=='0'){
						$state_str='订单关闭';
					}elseif($value['refund_state']=='7'){
						$state_str='订单关闭&nbsp;已退款';
					}elseif($value['refund_state']=='8'){
						$state_str='订单关闭&nbsp;已退货';
					}else{
						$state_str='订单关闭';
					}
					$pix=36;
					break;
				default:
					$state_str='错误状态';
					break;
			}
			$data_list[$key]['pix']=$pix;
			$data_list[$key]['state_str']=$state_str;
		}
		$expresses=db('expresses')->order('rank asc')->select();
		$suppliers=db('suppliers')->field('id,name')->where(['audit_state'=>'1','state'=>'1'])->order('created_at desc')->select();
		// 模板变量赋值
		$this->assign('expresses', $expresses);
		$this->assign('suppliers', $suppliers);
		$this->assign('data_list', $data_list);
		$this->assign('page', $page);
		$this->assign('search_order', $search_order);
		//$this->assign('search_is_purchase', $search_is_purchase);
		$this->assign('search_state', $search_state);
		$this->assign('search_refund_state', $search_refund_state);
		$this->assign('search_created_start', $search_created_start);
		$this->assign('search_created_end', $search_created_end);
		$this->assign('search_keywords', $search_keywords);
		$this->assign('search_user_id', $search_user_id);
		$this->assign('search_user_name', $search_user_name);
		$this->assign('empty','<div style="height:250px;line-height:250px;text-align:center;font-size:30px;color:#666;background:#fff;">！没有数据</div>');
		$this->assign('page_title', '全部订单');
		// 渲染模板输出
		return $this->fetch();
	}
	public function purchase($id='',$supplier_id=''){
		$now=time();
		$order=db('orders')->find($id);
		$supplier=db('suppliers')->find($supplier_id);
		if($order && $supplier && $order['state']=='1' && ($order['refund_state']=='0' || $order['refund_state']=='-1') && ($order['supplier_product_state']==0 || $order['supplier_product_state']==2)){
			$update=['id'=>$id,'supplier_id'=>$supplier_id,'is_purchase'=>'1','supplier_product_state'=>'0','updated_at'=>$now];
			$rt=db('orders')->update($update);
			if($rt!==false){
				db("letters")->insertGetId(['order_id'=>$id,'type'=>'1','supplier_id'=>$supplier_id,'title'=>'订单提示','content'=>'您有新的订单！','created_at'=>$now,'updated_at'=>$now]);
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>'采购成功']));
			}else{
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>'采购失败']));
			}		
		}else{
			exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
		}
	}
	public function norefund($id=''){
		$now=time();
		$order=db('orders')->find($id);
		$data=input('post.');
		$validate = new Validate([
		    'refund_msg|驳回理由'  => 'require|length:1,30',
		]);
		if (!$validate->check($data)) {
		    exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
		}
		if($order && ($order['refund_state']=='1' || $order['refund_state']=='2' || $order['refund_state']=='3')){
			switch ($order['refund_state']) {
				case '1':
					$refund_state='-1';
					$tips='退款';
					break;
				case '2':
					$refund_state='-2';
					$tips='退货';
					break;
				case '3':
					$refund_state='-3';
					$tips='换货';
					break;
				default:
					exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
					break;
			}
			$update=['id'=>$id,'refund_state'=>$refund_state,'audit_msg'=>$data['refund_msg'],'updated_at'=>$now];
			$rt=db('orders')->update($update);
			if($rt!==false){
				db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城已驳回'.$tips.'，原因：'.$data['refund_msg'],'created_at'=>$now,'updated_at'=>$now]);
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>'驳回成功']));
			}else{
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>'驳回失败']));
			}		
		}else{
			exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
		}
	}
	public function refund($id=''){
		$now=time();
		$order=db('orders')->find($id);
		if($order && ($order['refund_state']=='1' || $order['refund_state']=='2' || $order['refund_state']=='3')){
			switch ($order['refund_state']) {
				case '1':
					$refund_state='4';
					$tips='退款';
					break;
				case '2':
					$refund_state='5';
					$tips='退货';
					break;
				case '3':
					$refund_state='6';
					$tips='换货';
					break;
			}
			$update=['id'=>$id,'refund_state'=>$refund_state,'refund_agree_at'=>$now,'updated_at'=>$now];
			$rt=db('orders')->update($update);
			if($rt!==false){
				$log_ids=[];
				$log_ids[]=db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城同意'.$tips,'created_at'=>$now,'updated_at'=>$now]);
				$log_ids[]=db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城退款中','created_at'=>$now,'updated_at'=>$now]);
				switch ($order['refund_state']) {
					case '1':
						$letter_id=db("letters")->insertGetId(['order_id'=>$id,'type'=>'1','user_id'=>$order['user_id'],'title'=>'退款通知','content'=>'商城已经同意了您的退款，请注意查收！','created_at'=>$now,'updated_at'=>$now]);
						break;
					case '2':
						$letter_id=db("letters")->insertGetId(['order_id'=>$id,'type'=>'1','user_id'=>$order['user_id'],'title'=>'退货通知','content'=>'商城已经同意了您的退货要求，请您及时处理！','created_at'=>$now,'updated_at'=>$now]);
						break;
					case '3':
						break;
				}
				if($order['refund_state']=='1'){//线上退款
					$now_1=time();
					$trade=db('trades')->find($order['trade_id']);
					if($trade){
						switch ($trade['pay_type']) {
							case 'alipay':
								require_once("../extend/alipay_refund_fastpay_by_platform_nopwd/alipay.config.php");
								require_once("../extend/alipay_refund_fastpay_by_platform_nopwd/lib/alipay_submit.class.php");
								/**************************请求参数**************************/
						        //服务器异步通知页面路径
						        $notify_url = "http://adm.hdyywj.com/public/index.php/index/index/alipaypayreceive";
						        //需http://格式的完整路径，不允许加?id=123这类自定义参数
						        //退款批次号
						        $batch_no = date('YmdHis').mt_rand(1000,9999);
						        //必填，每进行一次即时到账批量退款，都需要提供一个批次号，必须保证唯一性
						        //退款请求时间
						        $refund_date = date('Y-m-d H:i:s');
						        //必填，格式为：yyyy-MM-dd hh:mm:ss
						        //退款总笔数
						        $batch_num = '1';
						        //必填，即参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的最大数量999个）
						        //单笔数据集
						        $detail_data = $trade['trade_sn_return'].'^'.number_format($trade['pay_amount']/100,2,'.','').'^正常退款';
						        //必填，格式详见“4.3 单笔数据集参数说明”
								/************************************************************/
								//构造要请求的参数数组，无需改动
								$parameter = array(
										"service" => "refund_fastpay_by_platform_nopwd",
										"partner" => trim($alipay_config['partner']),
										"notify_url"	=> $notify_url,
										"batch_no"	=> $batch_no,
										"refund_date"	=> $refund_date,
										"batch_num"	=> $batch_num,
										"detail_data"	=> $detail_data,
										"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
								);
								//建立请求
								$alipaySubmit = new \AlipaySubmit($alipay_config);
								$html_text = $alipaySubmit->buildRequestHttp($parameter);
								//解析XML
								//注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
								$doc = new \DOMDocument();
								$doc->loadXML($html_text);
								//请在这里加上商户的业务逻辑程序代码
								//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
								//获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
								//解析XML
								if( ! empty($doc->getElementsByTagName( "alipay" )->item(0)->nodeValue) ) {
									$alipay = $doc->getElementsByTagName( "alipay" )->item(0)->nodeValue;
									if(trim($alipay)=='T'){
										db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'退款完成（已退款￥'.number_format($trade['pay_amount']/100,2,'.','').'）','created_at'=>$now_1,'updated_at'=>$now_1]);
										db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'订单关闭','created_at'=>$now_1,'updated_at'=>$now_1]);
										$new_trade=[
											'trade_sn'=>$trade['trade_sn'],
											'trade_sn_return'=>$batch_no,
											'pay_type'=>$trade['pay_type'],
											'trade_type'=>'refund',
											'real_amount'=>-$trade['pay_amount'],
											'pay_amount'=>-$trade['pay_amount'],
											'created_at'=>$now_1,
											'updated_at'=>$now_1,
											'attach'=>$alipay,
											'state'=>1,
											'user_id'=>$trade['user_id'],
											'paid_at'=>$now_1,
										];
										$new_trade_id=db('trades')->insertGetId($new_trade);
										db('trade_logs')->insertGetId(['trade_id'=>$new_trade_id,'response'=>$html_text,'created_at'=>$now_1,'updated_at'=>$now_1]);
										db('orders')->update(['id'=>$id,'refund_amount'=>$trade['pay_amount'],'state'=>'9','refund_state'=>'7','refund_trade_id'=>$new_trade_id,'updated_at'=>$now_1]);
										//计入平台流水
					                    $insert_plat=[
					                        'sn'=>get_platform_water_sn(),
					                        'amount'=>-$trade['pay_amount'],
					                        'channel'=>'支付宝',
					                        'source'=>'用户商品退款',
					                        'relate_order'=>$trade['trade_sn'],
					                        'created_at'=>$now_1,
					                        'updated_at'=>$now_1
					                    ];
					                    db('platform_waters')->insertGetId($insert_plat);
									}else{
										db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
										db('order_logs')->where('id','in',$log_ids)->delete();
										db('letters')->where('id',$letter_id)->delete();
										exit(json_encode(['data'=>[],'code'=>205,'msg'=>'支付宝返回错误信息：'.$alipay]));
									}
								}else{
									db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
									exit(json_encode(['data'=>[],'code'=>205,'msg'=>'请求第三方支付宝未回应']));
								}
								break;
							case 'unionpay':
								import('unionpay.sdk.acp_service');
							    $config = new \SDKConfig();
							    $AcpService = new \AcpService();
							    $params = array(
							  
							      //以下信息非特殊情况不需要改动
							      'version' => $config->getSDKConfig()->version,      //版本号
							      'encoding' => 'utf-8',       //编码方式
							      'signMethod' => $config->getSDKConfig()->signMethod,       //签名方法
							      'txnType' => '04',         //交易类型
							      'txnSubType' => '00',       //交易子类
							      'bizType' => '000201',       //业务类型
							      'accessType' => '0',      //接入类型
							      'channelType' => '07',       //渠道类型
							      'backUrl' => $config->getSDKConfig()->backUrl, //后台通知地址
							  
							      //TODO 以下信息需要填写
							      'orderId' => $trade['trade_sn'],   //商户订单号，8-32位数字字母，不能含“-”或“_”，可以自行定制规则，重新产生-此处为在退款订单前拼接 T
							      'merId' => config('unionpay.merId'),     //商户代码，请改成自己的商户号
							      'origQryId' => $trade['trade_sn_return'], //原消费的queryId，可以从查询接口或者通知接口中获取
							      'txnTime' => date('YmdHis', $now_1),    //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费
							      'txnAmt' => $trade['pay_amount'],   //交易金额，退货总金额需要小于等于原消费
							    );
							  
							    $AcpService->sign ( $params ); // 签名
							    $url = $config->getSDKConfig()->backTransUrl;
							  
							    $result_arr = $AcpService->post ( $params, $url);
							    if(count($result_arr)<=0) { //没收到200应答的情况 
							    	db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
									db('order_logs')->where('id','in',$log_ids)->delete();
									db('letters')->where('id',$letter_id)->delete();
							        exit(json_encode(['data'=>[],'code'=>205,'msg'=>'银联第三方没收到应答，请重试']));
							    }
							  
							    if (!$AcpService->validate ($result_arr) ){
							    	db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
									db('order_logs')->where('id','in',$log_ids)->delete();
									db('letters')->where('id',$letter_id)->delete();
							        exit(json_encode(['data'=>[],'code'=>205,'msg'=>'银联第三方应答报文验签失败，请重试']));
							    }
							  
							    if ($result_arr["respCode"] == "00"){
							      //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
							      db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'退款完成（已退款￥'.number_format($result_arr['settleAmt']/100,2,'.','').'）','created_at'=>$now_1,'updated_at'=>$now_1]);
								  db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'订单关闭','created_at'=>$now_1,'updated_at'=>$now_1]);
									$new_trade=[
										'trade_sn'=>$trade['trade_sn'],
										'trade_sn_return'=>$result_arr['queryId'],
										'pay_type'=>$trade['pay_type'],
										'trade_type'=>$trade['trade_type'],
										'real_amount'=>-$trade['pay_amount'],
										'pay_amount'=>-$result_arr['settleAmt'],
										'created_at'=>$now_1,
										'updated_at'=>$now_1,
										'attach'=>'',
										'state'=>1,
										'user_id'=>$trade['user_id'],
										'paid_at'=>strtotime($result_arr['traceTime']),
									];
									$new_trade_id=db('trades')->insertGetId($new_trade);
									db('trade_logs')->insertGetId(['trade_id'=>$new_trade_id,'response'=>json_encode($result_arr),'created_at'=>$now_1,'updated_at'=>$now_1]);
									db('orders')->update(['id'=>$id,'refund_amount'=>$result_arr['settleAmt'],'state'=>'9','refund_state'=>'7','refund_trade_id'=>$new_trade_id,'updated_at'=>$now_1]);
							  		//计入平台流水
				                    $insert_plat=[
				                        'sn'=>get_platform_water_sn(),
				                        'amount'=>-$result_arr['settleAmt'],
				                        'channel'=>'银联',
				                        'source'=>'用户商品退款',
				                        'relate_order'=>$trade['trade_sn'],
				                        'created_at'=>$now_1,
				                        'updated_at'=>$now_1
				                    ];
				                    db('platform_waters')->insertGetId($insert_plat);
							    } else if ($result_arr["respCode"] == "03"
							      || $result_arr["respCode"] == "04"
							      || $result_arr["respCode"] == "05" ){
							      //后续需发起交易状态查询交易确定交易状态
							      db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
								  db('order_logs')->where('id','in',$log_ids)->delete();
								  db('letters')->where('id',$letter_id)->delete();
							      exit(json_encode(['data'=>[],'code'=>205,'msg'=>'银联第三方处理超时，请稍后再试']));
							    } else {
							      //其他应答码做以失败处理
							      db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
								  db('order_logs')->where('id','in',$log_ids)->delete();
								  db('letters')->where('id',$letter_id)->delete();
							  	  exit(json_encode(['data'=>[],'code'=>205,'msg'=>'银联第三方退款响应：'.$result_arr["respMsg"]]));
							    }
								break;
							case 'wechat':
								db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
								db('order_logs')->where('id','in',$log_ids)->delete();
								db('letters')->where('id',$letter_id)->delete();
								exit(json_encode(['data'=>[],'code'=>204,'msg'=>'暂未开通微信支付']));
								break;
							default:
								db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
								db('order_logs')->where('id','in',$log_ids)->delete();
								db('letters')->where('id',$letter_id)->delete();
								exit(json_encode(['data'=>[],'code'=>204,'msg'=>'交易记录的支付方式错误，无法完成退款']));
								break;
						}
					}else{
						db('orders')->update(['id'=>$id,'refund_state'=>'1','refund_agree_at'=>'0','updated_at'=>$now_1]);
						db('order_logs')->where('id','in',$log_ids)->delete();
						db('letters')->where('id',$letter_id)->delete();
						exit(json_encode(['data'=>[],'code'=>203,'msg'=>'与第三方交易记录丢失，无法完成退款']));
					}
				}
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>'同意'.$tips.'成功']));
			}else{
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>'同意'.$tips.'失败']));
			}		
		}else{
			exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
		}
	}
	public function refundsupplier($id=''){
		$now=time();
		$order=db('orders')->find($id);
		if($order && ($order['refund_state']=='5' || $order['refund_state']=='6') && $order['refund_product_state']=='1'){
			switch ($order['refund_state']) {
				case '5':
					$refund_state='5';
					$tips='退货';
					break;
				case '6':
					$refund_state='6';
					$tips='换货';
					break;
			}
			$update=['id'=>$id,'refund_product_state'=>'2','barter_refund_supplier_at'=>$now,'updated_at'=>$now];
			$rt=db('orders')->update($update);
			if($rt!==false){
				switch ($order['refund_state']) {
					case '5':
						db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城收到退货，退货退款进行中','created_at'=>$now,'updated_at'=>$now]);
						db("letters")->insertGetId(['order_id'=>$id,'type'=>'1','supplier_id'=>$order['supplier_id'],'title'=>'退货提示','content'=>'您的商品需要退货','created_at'=>$now,'updated_at'=>$now]);
						break;
					case '6':
						db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城收到原货，换货进行中','created_at'=>$now,'updated_at'=>$now]);
						break;
				}
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>'向供应商'.$tips.'成功']));
			}else{
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>'向供应商'.$tips.'失败']));
			}		
		}else{
			exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
		}
	}
	public function refundchange($id=''){
		$now=time();
		$order=db('orders')->find($id);
		$data=input('post.');
		$validate = new Validate([
		    'barter_express_id|物流公司'  => 'require|number',
		    'barter_express_sn|物流单号'  => 'require',
		]);
		if (!$validate->check($data)) {
		    exit(json_encode(['data'=>[],'code'=>202,'msg'=>$validate->getError()]));
		}
		if($order && $order['refund_state']=='6' && $order['refund_product_state']=='3'){
			$update=['id'=>$id,'refund_product_state'=>'4','barter_express_id'=>$data['barter_express_id'],'barter_express_sn'=>$data['barter_express_sn'],'barter_refund_user_at'=>$now,'updated_at'=>$now];
			$rt=db('orders')->update($update);
			if($rt!==false){
				db('order_logs')->insertGetId(['order_id'=>$id,'content'=>'商城已发出新货（'.db('expresses')->where('id',$data['barter_express_id'])->value('name').':'.$data['barter_express_sn'].'）','created_at'=>$now,'updated_at'=>$now]);
				exit(json_encode(['data'=>[],'code'=>200,'msg'=>'发送换货成功']));
			}else{
				exit(json_encode(['data'=>[],'code'=>202,'msg'=>'发送换货失败']));
			}		
		}else{
			exit(json_encode(['data'=>[],'code'=>201,'msg'=>'请求错误']));
		}
	}
	public function unionpayreceive(){//银联接受通知   暂时无用

	}
	public function nopurchase(){  
		$search_order=input('order','a.created_at desc,a.id desc');
		$order=$search_order;
		//$search_is_purchase=input('is_purchase','-1');
        $search_created_start=trim(input('created_start',''));
        $search_created_end=trim(input('created_end',''));
        $search_keywords=trim(input('keywords',''));
        $map = [];
        /*if($search_is_purchase=='0' ||$search_is_purchase=='1'){
        	$map['a.is_purchase']=$search_is_purchase;
        }*/
        if($search_keywords!==''){
        	$where=" (a.order_sn like '%{$search_keywords}%' || c.serial_num like '%{$search_keywords}%' || e.mobile like '%{$search_keywords}%' || g.name like '%{$search_keywords}%') ";
        }else{
        	$where='';
        }
        if($search_created_start!=='' || $search_created_end!==''){
        	if($search_created_start!=='' && $search_created_end===''){
        		$map['a.created_at']=['>=',strtotime($search_created_start.' 00:00:00')];
        	}elseif($search_created_start==='' && $search_created_end!==''){
        		$map['a.created_at']=['<=',strtotime($search_created_end.' 23:59:59')];
        	}else{
        		$map['a.created_at']=['between',[strtotime($search_created_start.' 00:00:00'),strtotime($search_created_end.' 23:59:59')]];
        	}
        }
        $limit=Db::name('admin_config')->where('name','list_rows')->value('value');
		$data_list = Db::name('orders a')->field('a.*,b.name user_name,c.serial_num supplier_serial_num,c.name supplier_name,d.num product_num,d.unit product_unit,d.price_sale product_price,d.product_size_name,e.receiver,e.mobile receiver_mobile,e.province_id,e.city_id,e.area_id,e.street,f.name product_name,f.picture product_picture,f.price_supply product_price_supply,g.name express_name')->join('users b','a.user_id=b.id','left')->join('suppliers c','a.supplier_id=c.id','left')->join('order_products d','a.id=d.order_id','left')->join('order_addresses e','a.order_address_id=e.id','left')->join('products f','d.product_id=f.id','left')->join('expresses g','a.express_id=g.id','left')->where('a.state','1')->where('a.refund_state','in','0,-1,-2,-3')->where($map)->where($where)->order($order)->paginate($limit);
		// 获取分页显示
		$page = $data_list->render();

		$data_list = json_decode(json_encode($data_list),TRUE);
		$data_list=$data_list['data'];

		foreach ($data_list as $key => $value) {
			$data_list[$key]['receive_address']=db('regions')->where('id',$value['province_id'])->value('name').' '.db('regions')->where('id',$value['city_id'])->value('name').' '.db('regions')->where('id',$value['area_id'])->value('name');
			$data_list[$key]['created_at_str']=date('Y-m-d H:i',$value['created_at']);
			$data_list[$key]['paid_at_str']=date('Y-m-d H:i',$value['paid_at']);
			$data_list[$key]['refund_express_name']=db('expresses')->where('id',$value['refund_express_id'])->value('name');
			$data_list[$key]['barter_express_name']=db('expresses')->where('id',$value['barter_express_id'])->value('name');
			$data_list[$key]['product_price']=number_format($value['product_price']/100,2,'.','');
			$data_list[$key]['amount']=number_format($value['amount']/100,2,'.','');
			$data_list[$key]['amount_pay']=number_format($value['amount_pay']/100,2,'.','');
			$state_str='';
			$pix=-1;
			switch ($value['state']) {
				case '0':
					$state_str='待付款';$pix=0;
					break;
				case '1':
					if($value['refund_state']=='0' || $value['refund_state']=='-1'){
						if($value['is_purchase']==0 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待采购';$pix=1;
							}else{
								$state_str='已驳回退款申请&nbsp;待采购';$pix=2;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待供应商备货';$pix=3;
							}else{
								$state_str='已驳回退款申请&nbsp;待供应商备货';$pix=4;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==1){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商已备货';$pix=5;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商已备货';$pix=6;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==2){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商缺货';$pix=7;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商缺货';$pix=8;
							}
						}else{
							$state_str='错误状态';
						}
					}elseif($value['refund_state']=='1'){
						$state_str='申请退款';$pix=9;
					}elseif($value['refund_state']=='4'){
						$state_str='同意退款申请';$pix=10;
					}elseif($value['refund_state']=='7'){
						$state_str='已退款';$pix=11;
					}else{
						$state_str='错误状态';
					}
					break;
				/*case '2':
					$state_str='申请撤单';
					break;*/
				case '3':
					if($value['refund_state']=='0'){
						$state_str='已发货';$pix=12;
					}elseif($value['refund_state']=='-1'){
						$state_str='已发货&nbsp;已驳回退款申请';$pix=13;
					}elseif($value['refund_state']=='1'){
						$state_str='已发货&nbsp;申请退款';$pix=14;
					}elseif($value['refund_state']=='4'){
						$state_str='已发货&nbsp;同意退款申请';$pix=15;
					}elseif($value['refund_state']=='7'){
						$state_str='已发货&nbsp;已退款';$pix=16;
					}else{
						$state_str='错误状态';
					}
					break;
				case '4':
					if($value['refund_state']=='0'){
						$state_str='货已到店';$pix=17;
					}elseif($value['refund_state']=='-2'){
						$state_str='货已到店&nbsp;已驳回退货申请';$pix=18;
					}elseif($value['refund_state']=='2'){
						$state_str='货已到店&nbsp;申请退货';$pix=19;
					}elseif($value['refund_state']=='5'){
						$state_str='货已到店&nbsp;同意退货申请';$pix=20;
					}elseif($value['refund_state']=='8'){
						$state_str='货已到店&nbsp;已退货';$pix=21;
					}elseif($value['refund_state']=='-3'){
						$state_str='货已到店&nbsp;已驳回换货申请';$pix=22;
					}elseif($value['refund_state']=='3'){
						$state_str='货已到店&nbsp;申请换货';$pix=23;
					}elseif($value['refund_state']=='6'){
						$state_str='货已到店&nbsp;同意换货申请';$pix=24;
					}elseif($value['refund_state']=='9'){
						$state_str='货已到店&nbsp;已换货';$pix=25;
					}elseif($value['refund_state']=='10'){
						$state_str='货已到店&nbsp;已放弃退货';$pix=-1;
					}elseif($value['refund_state']=='11'){
						$state_str='货已到店&nbsp;已放弃换货';$pix=-2;
					}else{
						$state_str='错误状态';
					}
					break;
				case '5':
					if($value['refund_state']=='0'){
						$state_str='已收货';$pix=26;
					}elseif($value['refund_state']=='-2'){
						$state_str='已收货&nbsp;已驳回退货申请';$pix=27;
					}elseif($value['refund_state']=='2'){
						$state_str='已收货&nbsp;申请退货';$pix=28;
					}elseif($value['refund_state']=='5'){
						$state_str='已收货&nbsp;同意退货申请';$pix=29;
					}elseif($value['refund_state']=='8'){
						$state_str='已收货&nbsp;已退货';$pix=30;
					}elseif($value['refund_state']=='-3'){
						$state_str='已收货&nbsp;已驳回换货申请';$pix=31;
					}elseif($value['refund_state']=='3'){
						$state_str='已收货&nbsp;申请换货';$pix=32;
					}elseif($value['refund_state']=='6'){
						$state_str='已收货&nbsp;同意换货申请';$pix=33;
					}elseif($value['refund_state']=='9'){
						$state_str='已收货&nbsp;已换货';$pix=34;
					}elseif($value['refund_state']=='10'){
						$state_str='已收货&nbsp;已放弃退货';$pix=-3;
					}elseif($value['refund_state']=='11'){
						$state_str='已收货&nbsp;已放弃换货';$pix=-4;
					}else{
						$state_str='错误状态';
					}
					break;
				case '8':
					if($value['refund_state']=='0'){
						$state_str='交易成功';
					}elseif($value['refund_state']=='-1'){
						$state_str='交易成功&nbsp;已驳回退款申请';
					}elseif($value['refund_state']=='-2'){
						$state_str='交易成功&nbsp;已驳回退货申请';
					}elseif($value['refund_state']=='-3'){
						$state_str='交易成功&nbsp;已驳回换货申请';
					}elseif($value['refund_state']=='9'){
						$state_str='交易成功&nbsp;已换货';
					}elseif($value['refund_state']=='10'){
						$state_str='交易成功&nbsp;已放弃退货';
					}elseif($value['refund_state']=='11'){
						$state_str='交易成功&nbsp;已放弃换货';
					}else{
						$state_str='交易成功';
					}
					$pix=35;
					break;
				case '9':
					if($value['refund_state']=='0'){
						$state_str='订单关闭';
					}elseif($value['refund_state']=='7'){
						$state_str='订单关闭&nbsp;已退款';
					}elseif($value['refund_state']=='8'){
						$state_str='订单关闭&nbsp;已退货';
					}else{
						$state_str='订单关闭';
					}
					$pix=36;
					break;
				default:
					$state_str='错误状态';
					break;
			}
			$data_list[$key]['pix']=$pix;
			$data_list[$key]['state_str']=$state_str;
		}
		$expresses=db('expresses')->order('rank asc')->select();
		$suppliers=db('suppliers')->field('id,name')->where(['audit_state'=>'1','state'=>'1'])->order('created_at desc')->select();
		// 模板变量赋值
		$this->assign('expresses', $expresses);
		$this->assign('suppliers', $suppliers);
		$this->assign('data_list', $data_list);
		$this->assign('page', $page);
		$this->assign('search_order', $search_order);
		//$this->assign('search_is_purchase', $search_is_purchase);
		$this->assign('search_created_start', $search_created_start);
		$this->assign('search_created_end', $search_created_end);
		$this->assign('search_keywords', $search_keywords);
		$this->assign('empty','<div style="height:250px;line-height:250px;text-align:center;font-size:30px;color:#666;background:#fff;">！没有数据</div>');
		$this->assign('page_title', '待采购订单');
		// 渲染模板输出
		return $this->fetch();
	}
	public function yesrefund(){
		$search_order=input('order','a.created_at desc,a.id desc');
		$order=$search_order;
		//$search_is_purchase=input('is_purchase','-1');
        $search_refund_state=input('refund_state','-99');
        $search_created_start=trim(input('created_start',''));
        $search_created_end=trim(input('created_end',''));
        $search_keywords=trim(input('keywords',''));
        $map = [];
        if($search_refund_state>-99){
        	$map['a.refund_state']=$search_refund_state;
        }
        /*if($search_is_purchase=='0' ||$search_is_purchase=='1'){
        	$map['a.is_purchase']=$search_is_purchase;
        }*/
        if($search_keywords!==''){
        	$where=" (a.order_sn like '%{$search_keywords}%' || c.serial_num like '%{$search_keywords}%' || e.mobile like '%{$search_keywords}%' || g.name like '%{$search_keywords}%') ";
        }else{
        	$where='';
        }
        if($search_created_start!=='' || $search_created_end!==''){
        	if($search_created_start!=='' && $search_created_end===''){
        		$map['a.created_at']=['>=',strtotime($search_created_start.' 00:00:00')];
        	}elseif($search_created_start==='' && $search_created_end!==''){
        		$map['a.created_at']=['<=',strtotime($search_created_end.' 23:59:59')];
        	}else{
        		$map['a.created_at']=['between',[strtotime($search_created_start.' 00:00:00'),strtotime($search_created_end.' 23:59:59')]];
        	}
        }
        $limit=Db::name('admin_config')->where('name','list_rows')->value('value');
		$data_list = Db::name('orders a')->field('a.*,b.name user_name,c.serial_num supplier_serial_num,c.name supplier_name,d.num product_num,d.unit product_unit,d.price_sale product_price,d.product_size_name,e.receiver,e.mobile receiver_mobile,e.province_id,e.city_id,e.area_id,e.street,f.name product_name,f.picture product_picture,f.price_supply product_price_supply,g.name express_name')->join('users b','a.user_id=b.id','left')->join('suppliers c','a.supplier_id=c.id','left')->join('order_products d','a.id=d.order_id','left')->join('order_addresses e','a.order_address_id=e.id','left')->join('products f','d.product_id=f.id','left')->join('expresses g','a.express_id=g.id','left')->where('a.refund_state','in','1,2,3,4,5,6,7,8,9,10,11')->where($map)->where($where)->order($order)->paginate($limit);
		// 获取分页显示
		$page = $data_list->render();

		$data_list = json_decode(json_encode($data_list),TRUE);
		$data_list=$data_list['data'];

		foreach ($data_list as $key => $value) {
			$data_list[$key]['receive_address']=db('regions')->where('id',$value['province_id'])->value('name').' '.db('regions')->where('id',$value['city_id'])->value('name').' '.db('regions')->where('id',$value['area_id'])->value('name');
			$data_list[$key]['created_at_str']=date('Y-m-d H:i',$value['created_at']);
			$data_list[$key]['paid_at_str']=date('Y-m-d H:i',$value['paid_at']);
			$data_list[$key]['refund_express_name']=db('expresses')->where('id',$value['refund_express_id'])->value('name');
			$data_list[$key]['barter_express_name']=db('expresses')->where('id',$value['barter_express_id'])->value('name');
			$data_list[$key]['product_price']=number_format($value['product_price']/100,2,'.','');
			$data_list[$key]['amount']=number_format($value['amount']/100,2,'.','');
			$data_list[$key]['amount_pay']=number_format($value['amount_pay']/100,2,'.','');
			$state_str='';
			$pix=-1;
			switch ($value['state']) {
				case '0':
					$state_str='待付款';$pix=0;
					break;
				case '1':
					if($value['refund_state']=='0' || $value['refund_state']=='-1'){
						if($value['is_purchase']==0 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待采购';$pix=1;
							}else{
								$state_str='已驳回退款申请&nbsp;待采购';$pix=2;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==0){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;待供应商备货';$pix=3;
							}else{
								$state_str='已驳回退款申请&nbsp;待供应商备货';$pix=4;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==1){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商已备货';$pix=5;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商已备货';$pix=6;
							}
						}elseif($value['is_purchase']==1 && $value['supplier_product_state']==2){
							if($value['refund_state']=='0'){
								$state_str='已付款&nbsp;供应商缺货';$pix=7;
							}else{
								$state_str='已驳回退款申请&nbsp;供应商缺货';$pix=8;
							}
						}else{
							$state_str='错误状态';
						}
					}elseif($value['refund_state']=='1'){
						$state_str='申请退款';$pix=9;
					}elseif($value['refund_state']=='4'){
						$state_str='同意退款申请';$pix=10;
					}elseif($value['refund_state']=='7'){
						$state_str='已退款';$pix=11;
					}else{
						$state_str='错误状态';
					}
					break;
				/*case '2':
					$state_str='申请撤单';
					break;*/
				case '3':
					if($value['refund_state']=='0'){
						$state_str='已发货';$pix=12;
					}elseif($value['refund_state']=='-1'){
						$state_str='已发货&nbsp;已驳回退款申请';$pix=13;
					}elseif($value['refund_state']=='1'){
						$state_str='已发货&nbsp;申请退款';$pix=14;
					}elseif($value['refund_state']=='4'){
						$state_str='已发货&nbsp;同意退款申请';$pix=15;
					}elseif($value['refund_state']=='7'){
						$state_str='已发货&nbsp;已退款';$pix=16;
					}else{
						$state_str='错误状态';
					}
					break;
				case '4':
					if($value['refund_state']=='0'){
						$state_str='货已到店';$pix=17;
					}elseif($value['refund_state']=='-2'){
						$state_str='货已到店&nbsp;已驳回退货申请';$pix=18;
					}elseif($value['refund_state']=='2'){
						$state_str='货已到店&nbsp;申请退货';$pix=19;
					}elseif($value['refund_state']=='5'){
						$state_str='货已到店&nbsp;同意退货申请';$pix=20;
					}elseif($value['refund_state']=='8'){
						$state_str='货已到店&nbsp;已退货';$pix=21;
					}elseif($value['refund_state']=='-3'){
						$state_str='货已到店&nbsp;已驳回换货申请';$pix=22;
					}elseif($value['refund_state']=='3'){
						$state_str='货已到店&nbsp;申请换货';$pix=23;
					}elseif($value['refund_state']=='6'){
						$state_str='货已到店&nbsp;同意换货申请';$pix=24;
					}elseif($value['refund_state']=='9'){
						$state_str='货已到店&nbsp;已换货';$pix=25;
					}elseif($value['refund_state']=='10'){
						$state_str='货已到店&nbsp;已放弃退货';$pix=-1;
					}elseif($value['refund_state']=='11'){
						$state_str='货已到店&nbsp;已放弃换货';$pix=-2;
					}else{
						$state_str='错误状态';
					}
					break;
				case '5':
					if($value['refund_state']=='0'){
						$state_str='已收货';$pix=26;
					}elseif($value['refund_state']=='-2'){
						$state_str='已收货&nbsp;已驳回退货申请';$pix=27;
					}elseif($value['refund_state']=='2'){
						$state_str='已收货&nbsp;申请退货';$pix=28;
					}elseif($value['refund_state']=='5'){
						$state_str='已收货&nbsp;同意退货申请';$pix=29;
					}elseif($value['refund_state']=='8'){
						$state_str='已收货&nbsp;已退货';$pix=30;
					}elseif($value['refund_state']=='-3'){
						$state_str='已收货&nbsp;已驳回换货申请';$pix=31;
					}elseif($value['refund_state']=='3'){
						$state_str='已收货&nbsp;申请换货';$pix=32;
					}elseif($value['refund_state']=='6'){
						$state_str='已收货&nbsp;同意换货申请';$pix=33;
					}elseif($value['refund_state']=='9'){
						$state_str='已收货&nbsp;已换货';$pix=34;
					}elseif($value['refund_state']=='10'){
						$state_str='已收货&nbsp;已放弃退货';$pix=-3;
					}elseif($value['refund_state']=='11'){
						$state_str='已收货&nbsp;已放弃换货';$pix=-4;
					}else{
						$state_str='错误状态';
					}
					break;
				case '8':
					if($value['refund_state']=='0'){
						$state_str='交易成功';
					}elseif($value['refund_state']=='-1'){
						$state_str='交易成功&nbsp;已驳回退款申请';
					}elseif($value['refund_state']=='-2'){
						$state_str='交易成功&nbsp;已驳回退货申请';
					}elseif($value['refund_state']=='-3'){
						$state_str='交易成功&nbsp;已驳回换货申请';
					}elseif($value['refund_state']=='9'){
						$state_str='交易成功&nbsp;已换货';
					}elseif($value['refund_state']=='10'){
						$state_str='交易成功&nbsp;已放弃退货';
					}elseif($value['refund_state']=='11'){
						$state_str='交易成功&nbsp;已放弃换货';
					}else{
						$state_str='交易成功';
					}
					$pix=35;
					break;
				case '9':
					if($value['refund_state']=='0'){
						$state_str='订单关闭';
					}elseif($value['refund_state']=='7'){
						$state_str='订单关闭&nbsp;已退款';
					}elseif($value['refund_state']=='8'){
						$state_str='订单关闭&nbsp;已退货';
					}else{
						$state_str='订单关闭';
					}
					$pix=36;
					break;
				default:
					$state_str='错误状态';
					break;
			}
			$data_list[$key]['pix']=$pix;
			$data_list[$key]['state_str']=$state_str;
		}
		$expresses=db('expresses')->order('rank asc')->select();
		$suppliers=db('suppliers')->field('id,name')->where(['audit_state'=>'1','state'=>'1'])->order('created_at desc')->select();
		// 模板变量赋值
		$this->assign('expresses', $expresses);
		$this->assign('suppliers', $suppliers);
		$this->assign('data_list', $data_list);
		$this->assign('page', $page);
		$this->assign('search_order', $search_order);
		//$this->assign('search_is_purchase', $search_is_purchase);
		$this->assign('search_refund_state', $search_refund_state);
		$this->assign('search_created_start', $search_created_start);
		$this->assign('search_created_end', $search_created_end);
		$this->assign('search_keywords', $search_keywords);
		$this->assign('empty','<div style="height:250px;line-height:250px;text-align:center;font-size:30px;color:#666;background:#fff;">！没有数据</div>');
		$this->assign('page_title', '售后订单');
		// 渲染模板输出
		return $this->fetch();
	}
}