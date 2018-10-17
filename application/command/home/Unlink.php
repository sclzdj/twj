<?php
namespace app\command\home;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\common\controller\Common;
use think\Db;
class Unlink extends Command
{
    protected function configure()
    {
        //设置参数
        //$this->addArgument('timed', Argument::REQUIRED);//可选参数
        $this->setName('unlink')->setDescription('删除无效的上传文件');
    }
    protected function execute(Input $input, Output $output)
    {
        //放执行代码
        //$args = $input->getArguments();
        //print_r($args);
        //$timed=(int)$args['timed'];
       
        db('config')->where('name','is_allow_upload')->update(['value'=>'0','updated_at'=>time()]);

        //配置不检测的数据
        $ids=[];
        $admin_configs=db('admin_config')->field('id,value')->where('type','image')->select();
        foreach ($admin_configs as $key => $value) {
            $ids[]=$value['value'];
        }
        //配置需要检测的数据
        $unlinks=[];
        $unlinks['admin_user']=['id'=>'avatar'];
        $unlinks['banners']=['id'=>'admin_attachment_id'];
        $unlinks['collage_products']=['ids'=>'admin_attachment_ids','str'=>'detail'];
        $unlinks['hotrecommends']=['id'=>'admin_attachment_id'];
        $unlinks['notifys']=['id'=>'admin_attachment_id','str'=>'content'];
        $unlinks['product_albums']=['str'=>'picture'];
        $unlinks['product_sizes']=['str'=>'picture'];
        $unlinks['product_texts']=['str'=>'description'];
        $unlinks['product_types']=['id'=>'admin_attachment_id'];
        $unlinks['products']=['str'=>'picture'];
        $unlinks['quotations']=['str'=>'code'];
        $unlinks['salesmen']=['id'=>'idcard_pic'];
        $unlinks['suppliers']=['str'=>'license','str'=>'idcard_pic'];
        $unlinks['surveys']=['id'=>'pic'];
        $unlinks['users']=['str'=>'license'];
        //开始检测
        $is_ids=[];
        $admin_attachments=db('admin_attachment')->field('id,path,thumb')->where('id','not in',$ids)->select();
        foreach ($admin_attachments as $key => $value) {
            foreach ($unlinks as $k => $v) {
                foreach ($v as $_k => $_v) {
                    switch ($_k) {
                        case 'id':
                            $has=db($k)->where($_v.' = "'.$value['id'].'"')->find();
                            if($has){
                                $is_ids[]=$value['id'];
                                continue 4;
                            }
                            break;
                        case 'ids':
                            $has=db($k)->where($_v.' like "%'.$value['id'].',%" || '.$_v.' like "%,'.$value['id'].'%" || '.$_v.' = "'.$value['id'].'"')->find();
                            if($has){
                                $is_ids[]=$value['id'];
                                continue 4;
                            }
                            break;
                        case 'str':
                            $has=db($k)->where($_v.' like "%'.$value['path'].'%" || '.$_v.' like "%'.$value['thumb'].'%"')->find();
                            if($has){
                                $is_ids[]=$value['id'];
                                continue 4;
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        $is_unlinks=db('admin_attachment')->field('id,path,thumb')->where('id','not in',$ids)->where('id','not in',$is_ids)->select();
        //db('admin_attachment')->where('id','not in',$ids)->where('id','not in',$is_ids)->delete();
        foreach ($is_unlinks as $key => $value) {
            //@unlink($value['path']);
            //@unlink($value['thumb']);
        }
        db('config')->where('name','is_allow_upload')->update(['value'=>'1','updated_at'=>time()]);
        print_r($is_unlinks);
    }
}