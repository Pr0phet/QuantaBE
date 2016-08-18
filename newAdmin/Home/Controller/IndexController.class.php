<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller
{
    public function index()
    {
        if(I('post.name',0) && I('post.password',0))
        {
            if(isset($_SESSION['validate']) && $_SESSION['validate'] == 1)
            {
                redirect('main','',3,'欢迎您,'.$_SESSION['name'].'正在为您跳转');
            }
            $this -> display();
        }
        else
        {
            $this->login(I('post.name'),I('post.password'));
        }
    }//登录界面控制器

    private function login($user,$pass)
    {
        $admin = M('admin');
        $condition['name'] = $user;
        $res = $admin -> WHERE($condition) -> find();
        if($res['password'] != $pass)
        {
            $this -> error('密码错误','index',3); //密码错误的跳转
        }
        else
        {
            $_SESSION['validate'] = 1;
            $_SESSION['name'] = $user;
            redirect('main','',3,'欢迎您,'.$user.'正在为您跳转');
        }
    }//登录操作

    public function main()
    {
        if($_SESSION['validate'] != 1)
        {
            $this -> error('您还没登录呢','index',3);
        }//判断是否登录
        $this -> display();
    }//主界面

    public function getBanner()
    {
        $banner = M('banner');
        $res = $banner -> select();
        $this -> ajaxReturn($res);
    }//获取所有banner信息

    public function addBanner()
    {
        $config = array(
            'maxSize' => '31457280', //最大30M
            'rootPath' => '__PUBLIC__/upload/', //上传根目录
            'savePath' => 'banner', //保存目录（相对于根目录）
            'saveName' => array('date','Y-M-D'), //文件命名规则
            'exts' => array('jpg','jpeg','png')
        );
        $upload = new \Think\Upload($config);
        $res = $upload ->uploadOne($_FILES);
        if($res)
        {
            $banner = M('banner');
            $file = array(
                'picname' => I('post.title'),
                'picurl' => $res['savepath'].$res['savename'],
                'url' => I('post.url',NULL),
                'createtime' => date('Y-M-D')
            );
            $banner -> add($file); //更新数据库
            $this -> ajaxReturn('Success');
        }
        else
        {
            $this -> ajaxReturn($upload->getError());
        }
    }//新增banner

    public function rmBanner($id)
    {
        $banner = M('banner');
        $file = $banner -> WHERE("id = $id") -> find();
        $res = unlink($file['picurl']);
        if(!$res)
        {
            $this -> error('发生错误,请联系管理员');
        }
        $res = $banner -> WHERE("id= $id") -> delete();
        if($res)
        {
            $this -> ajaxReturn("Success");
        }
        else
        {
            $this -> error('删除失败,请联系管理员');
        }
    }//删除banner

    public function addmember()
    {
        $raw = I('post.',NULL); //接收数据
        $data = array(
            'name' => $raw['name'],
            'mail' => $raw['mail'],
            'tel' => $raw['tel'],
            'shorttel' => I('shorttel',NULL)
        );
        $member = M('member');
        $res = $member -> add($data);
        if($res)
        {
            $this -> ajaxReturn('Success');
        }
        else
        {
            $this -> error('添加失败，请联系管理员');
        }
    }//新增通讯录

    public function addNews()
    {
        $raw = I('post.');

        $data = array(
            'title' => $raw['title'],
            'pic' => I('post.',NULL),
            'content'
        );
    }//新增动态

    public function rmNews($id)
    {
        $news = M('news');
        $res = $news -> WHERE('id ='.$id) -> delete();
        if($res)
        {
            $this -> ajaxReturn('Success');
        }
        else
        {
            $this -> error('删除失败，请联系管理员');
        }
    }//删除动态

    private function mktxt()
    {

    }//将编辑框编辑为文本

    public function getNews()
    {
        $news = M('news');

    }//获取动态

    public function search($key)
    {
        $news = M('news');
        $condition['title'] = array('like','%'.$key.'%');
        $condition['contant'] = array('like','%'.$key.'%');
        $condition['_logic'] = 'OR';
        $res = $news -> WHERE($condition) -> select();
        if($res)
        {
            $this -> ajaxReturn($res);
        }
        else
        {
            $this -> ajaxReturn('0');
        }
    }

    public function drafts()
    {

    }//调用之前的存储方法
}