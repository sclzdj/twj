<?php
namespace app\command\home;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\common\controller\Common;
use think\Db;
class Refund extends Command
{
    protected function configure()
    {
        //设置参数
        //$this->addArgument('timed', Argument::REQUIRED);//可选参数
        $this->setName('refund')->setDescription('用户端到期未发货放弃退货、换货，供应商端到期确认收到退货，用户端到期确认退货');
    }
    protected function execute(Input $input, Output $output)
    {
        //放执行代码
        //$args = $input->getArguments();
        //print_r($args);
        //$timed=(int)$args['timed'];
       
        $now=time();
        //放弃退货，换货
        $orders=db('orders')->where('state','in','4,5')->where('refund_state','in','5,6')->where('refund_product_state','0')->where('refund_agree_at','<',$now-config('crontab.refund_agree_limit')*24*60*60)->select();
        foreach ($orders as $key => $value) {
            if($value['refund_state']=='4'){
                $rows=db('orders')->where('state','in','4,5')->where('refund_state','5')->where('refund_product_state','0')->where('id',$value['id'])->update(['refund_state'=>'10','updated_at'=>$now]);
                if($rows>0){
                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'因超时未发送退货，系统已视为自动放弃','created_at'=>$now,'updated_at'=>$now]);
                    if($value['state']=='4'){
                        $rows=db('orders')->where('state','4')->where('id',$value['id'])->update(['state'=>'5','received_at'=>$value['arrived_at'],'updated_at'=>$now]);
                        if($rows>0){
                            db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'已确认收货（系统自动）','created_at'=>$value['arrived_at'],'updated_at'=>$value['arrived_at']]);
                        }
                    }
                    $rows=db('orders')->where('id',$value['id'])->update(['state'=>'8','updated_at'=>$now]);
                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'交易成功，订单完成','created_at'=>$now,'updated_at'=>$now]);
                }
            }
            if($value['refund_state']=='5'){
                $rows=db('orders')->where('state','in','4,5')->where('refund_state','4')->where('refund_product_state','0')->where('id',$value['id'])->update(['refund_state'=>'11','updated_at'=>$now]);
                if($rows>0){
                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'因超时未发换原货，系统已视为自动放弃','created_at'=>$now,'updated_at'=>$now]);
                    if($value['state']=='4'){
                        $rows=db('orders')->where('state','4')->where('id',$value['id'])->update(['state'=>'5','received_at'=>$now,'updated_at'=>$now]);
                        if($rows>0){
                            db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'已确认收货（系统自动）','created_at'=>$now,'updated_at'=>$now]);
                        }
                    }
                    $rows=db('orders')->where('id',$value['id'])->update(['state'=>'8','updated_at'=>$now]);
                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'交易成功，订单完成','created_at'=>$now,'updated_at'=>$now]);
                }
            }
        }
        //供应商规定时间内未确认收到退货则系统自动确认
        $orders=db('orders')->where('state','in','4,5')->where('refund_state','5')->where('refund_product_state','2')->where('barter_refund_supplier_at','<',$now-config('crontab.barter_refund_supplier_limit')*24*60*60)->select();
        foreach ($orders as $key => $value) {
            $rows=db('orders')->where('state','in','4,5')->where('refund_state','5')->where('refund_product_state','2')->where('id',$value['id'])->update(['refund_product_state'=>'3','refund_state'=>'8','updated_at'=>$now]);
            if($rows>0){
                $trade=db('trades')->find($value['trade_id']);
                if($trade){
                    switch ($trade['pay_type']) {
                        case 'alipay':
                            require_once("./extend/alipay_refund_fastpay_by_platform_nopwd/alipay.config.php");
                            require_once("./extend/alipay_refund_fastpay_by_platform_nopwd/lib/alipay_submit.class.php");
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
                                    "notify_url"    => $notify_url,
                                    "batch_no"  => $batch_no,
                                    "refund_date"   => $refund_date,
                                    "batch_num" => $batch_num,
                                    "detail_data"   => $detail_data,
                                    "_input_charset"    => trim(strtolower($alipay_config['input_charset']))
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
                                    //插入订单事件记录
                                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'退货完成（已退款￥'.number_format($trade['pay_amount']/100,2,'.','').'）','created_at'=>$now,'updated_at'=>$now]);
                                    db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'订单关闭','created_at'=>$now,'updated_at'=>$now]);
                                    //插入退款交易记录
                                    $new_trade=[
                                        'trade_sn'=>$trade['trade_sn'],
                                        'trade_sn_return'=>$batch_no,
                                        'pay_type'=>$trade['pay_type'],
                                        'trade_type'=>'refund',
                                        'real_amount'=>-$trade['pay_amount'],
                                        'pay_amount'=>-$trade['pay_amount'],
                                        'created_at'=>$now,
                                        'updated_at'=>$now,
                                        'attach'=>$alipay,
                                        'state'=>1,
                                        'user_id'=>$trade['user_id'],
                                        'paid_at'=>$now,
                                    ];
                                    $new_trade_id=db('trades')->insertGetId($new_trade);
                                    //插入退款交易记录日志
                                    db('trade_logs')->insertGetId(['trade_id'=>$new_trade_id,'response'=>$html_text,'created_at'=>$now,'updated_at'=>$now]);
                                    //修改订单状态
                                    db('orders')->update(['id'=>$value['id'],'refund_amount'=>$trade['pay_amount'],'state'=>'9','refund_state'=>'8','refund_trade_id'=>$new_trade_id,'updated_at'=>$now]);
                                    //插入平台流水记录
                                    $insert_plat=[
                                        'sn'=>get_platform_water_sn(),
                                        'amount'=>-$trade['pay_amount'],
                                        'channel'=>'支付宝',
                                        'source'=>'用户商品退货',
                                        'relate_order'=>$trade['trade_sn'],
                                        'created_at'=>$now,
                                        'updated_at'=>$now
                                    ];
                                    db('platform_waters')->insertGetId($insert_plat);
                                }else{
                                    db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                                }
                            }else{
                                db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
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
                              'txnTime' => date('YmdHis', $now),    //订单发送时间，格式为YYYYMMDDhhmmss，重新产生，不同于原消费
                              'txnAmt' => $trade['pay_amount'],   //交易金额，退货总金额需要小于等于原消费
                            );
                          
                            $AcpService->sign ( $params ); // 签名
                            $url = $config->getSDKConfig()->backTransUrl;
                          
                            $result_arr = $AcpService->post ( $params, $url);
                            if(count($result_arr)<=0) { //没收到200应答的情况 
                                db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            }
                          
                            if (!$AcpService->validate ($result_arr) ){
                                db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            }
                            if ($result_arr["respCode"] == "00"){
                              //交易已受理，等待接收后台通知更新订单状态，如果通知长时间未收到也可发起交易状态查询
                                $new_trade=[
                                    'trade_sn'=>$trade['trade_sn'],
                                    'trade_sn_return'=>$result_arr['queryId'],
                                    'pay_type'=>$trade['pay_type'],
                                    'trade_type'=>$trade['trade_type'],
                                    'real_amount'=>-$trade['pay_amount'],
                                    'pay_amount'=>-$result_arr['settleAmt'],
                                    'created_at'=>$now,
                                    'updated_at'=>$now,
                                    'attach'=>'',
                                    'state'=>1,
                                    'user_id'=>$trade['user_id'],
                                    'paid_at'=>strtotime($result_arr['traceTime']),
                                ];
                                $new_trade_id=db('trades')->insertGetId($new_trade);
                                db('trade_logs')->insertGetId(['trade_id'=>$new_trade_id,'response'=>json_encode($result_arr),'created_at'=>$now,'updated_at'=>$now]);
                                db('orders')->where('id',$value['id'])->update(['state'=>'9','refund_amount'=>$result_arr['settleAmt'],'refund_trade_id'=>$new_trade_id,'updated_at'=>$now]);
                                db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'退货完成（已退款￥'.number_format($result_arr['settleAmt']/100,2,'.','').'）','created_at'=>$now,'updated_at'=>$now]);
                                //计入平台流水
                                $insert_plat=[
                                    'sn'=>get_platform_water_sn(),
                                    'amount'=>-$result_arr['settleAmt'],
                                    'channel'=>'银联',
                                    'source'=>'用户商品退款',
                                    'relate_order'=>$trade['trade_sn'],
                                    'created_at'=>$now,
                                    'updated_at'=>$now
                                ];
                                db('platform_waters')->insertGetId($insert_plat);
                            } else if ($result_arr["respCode"] == "03"
                              || $result_arr["respCode"] == "04"
                              || $result_arr["respCode"] == "05" ){
                              //后续需发起交易状态查询交易确定交易状态
                              db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            } else {
                              //其他应答码做以失败处理
                             db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            }
                            break;
                        case 'wechat':
                            db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            break;
                        default:
                            db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                            break;
                    }
                }else{
                    db('orders')->where('id',$value['id'])->update(['refund_state'=>'5','refund_product_state'=>'2','updated_at'=>$now]);
                }
            }
        }
        //用户规定时间内未确认收到换货则系统自动确认
        $orders=db('orders')->where('state','in','4,5')->where('refund_state','6')->where('refund_product_state','5')->where('barter_refund_user_arrived_at','<',$now-config('crontab.barter_refund_user_limit')*24*60*60)->select();
        foreach ($orders as $key => $value) {
            $rows=db('orders')->where('state','in','4,5')->where('refund_state','6')->where('refund_product_state','5')->where('id',$value['id'])->update(['refund_state'=>'9','updated_at'=>$now]);
            if($rows>0){
                db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'已收到新货（系统自动），换货完成','created_at'=>$now,'updated_at'=>$now]);
                if($value['state']=='4'){
                    $rows=db('orders')->where('state','4')->where('id',$value['id'])->update(['state'=>'5','received_at'=>$value['arrived_at'],'updated_at'=>$now]);
                    if($rows>0){
                        db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'已确认收货（系统自动）','created_at'=>$value['arrived_at'],'updated_at'=>$value['arrived_at']]);
                    }
                }
                $rows=db('orders')->where('id',$value['id'])->update(['state'=>'8','updated_at'=>$now]);
                db('order_logs')->insertGetId(['order_id'=>$value['id'],'content'=>'交易成功，订单完成','created_at'=>$now,'updated_at'=>$now]);
            }
        }
        //print_r(['status'=>'1']);
    }
}