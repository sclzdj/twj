<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace app\index\controller;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index extends Home
{
    public function index()
    {
        // 默认跳转模块
        /*if (config('home_default_module') != 'index') {
            $this->redirect(config('home_default_module'). '/index/index');
        }
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> '.config("dolphin.product_name").' '.config("dolphin.product_version").'<br/><span style="font-size:30px">极速 · 极简 · 极致</span></p></div>';*/
        //$now=time();
        //$token=md5($now.config("mall.upload_code"));
        //echo '<form action="http://adm.gz.sclzdj.cn/public/index.php/index/upload/index/type/image/time/'.$now.'/token/'.$token.'" method="post" enctype="multipart/form-data"><input name="file" type="file" /><input type="submit" value="上传" /></form>';//上传图片接口测试
        //<input name="thumb_max_width" type="hidden" value="20" /><input name="thumb_max_height" type="hidden" value="10" />
        return $this->fetch();
    }
    public function upshow(){
        $pix=0;
        if($pix){
            $menus=db('admin_role')->where('id','2')->value('menu_auth');
            $menus=trim($menus,'[');
            $menus=trim($menus,']');
            $menus=explode('","',$menus);
            //dump($menus);die;
            $rt=db('admin_menu')->where('id','in',$menus)->update(['is_show'=>'1']);
            if($rt!==false){
                db('admin_menu')->where('id','not in',$menus)->update(['is_show'=>'0']);
                echo "成功";
            }else{
                echo "失败";
            }
        }
    }
    public function pr(){
        $pix=1;
        if($pix){
            $products=db('products')->where('sn','eq','')->select();
            foreach ($products as $key => $value) {
                $sn=$value['supplier_id'].'_'.mt_rand(1000000000,9999999999);
                db('products')->where('id',$value['id'])->update(['sn'=>$sn]);
            }
        }
    }
    public function unionpayreceive(){//银联接受通知   暂时无用
        echo "银联";
    }
    public function alipaypayreceive(){//支付宝接受通知   暂时无用
        echo "支付宝";
    }
}
