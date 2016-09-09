<?php
namespace Home\Controller;
use Think\Controller;
use Think\Upload;

class IndexController extends Controller
{
    public function check()
    {
        if(isset($_SESSION['validate']) && $_SESSION['validate'] == 1)
        {
            $this -> ajaxReturn('0');
        }
        else
        {
            $this-> ajaxReturn('1');
        }
    }//检查登录状况

    private function login($user,$pass)
    {
        $admin = M('admin');
        $condition['name'] = $user;
        $res = $admin -> WHERE($condition) -> find();
        if($res['password'] != $pass)
        {
            $this -> ajaxReturn('0'); //密码错误
        }
        else
        {
            $_SESSION['validate'] = 1;
            $_SESSION['name'] = $user;
            $this -> ajaxReturn('1');//正确
        }
    }//登录操作

    public function addBanner()
    {
        $title = I('post.title');
        $url = I('post.url', 0);
        $remarks = I('post.remarks');
        $res = $this -> uploadImg("banner");
        $data['title'] = $title;
        $data['picurl'] = $res[1]['savepath'].$res[1]['savename'];
        $url ? $data['url'] = $url : 0 ;
        $data['remarks'] = $remarks;
        $data['createtime'] = date('Y-M-D');
        $feed = $res[0] -> add($data);
        if(!$feed)
        {
            echo '0';
        }
        else
        {
            echo '1';
        }
    }//新增banner

    public function getBanner()
    {
        $banner = M('banner');
        $res = $banner -> select();
        $this -> ajaxReturn($res);
    }//获取所有banner信息

    public function rmBanner($id)
    {
        $banner = M('banner');
        $file = $banner -> WHERE("id = $id") -> find();
        $res = unlink($file['picurl']);
        if(!$res)
        {
            echo '发生错误,请联系管理员';
        }
        $res = $banner -> WHERE("id= $id") -> delete();
        if($res)
        {
            echo "1";
        }
        else
        {
            echo '删除失败,请联系管理员';
        }
    }//删除banner

    private function deldir($filepath)
    {
        if(is_dir($filepath))
        {
            if($dir = opendir($filepath))
            {
                while(($file = readdir($dir)) !== FALSE)
                {
                    unlink($filepath.$file); //删除文件
                }
                rmdir($filepath);
                echo "已删除$filepath";
            }
        }
        else if(is_file($filepath))
        {

            unlink($filepath);
            echo "已删除文件";
        }
    }//删除文件或文件夹！慎用！

    //---------------------WangEditor处理函数-----------------

    private function uploadImg($type)
    {
        $db = M("$type");
        $record = $db -> ORDER('id desc') -> find();
        $config = array(
            'maxSize' => '6291456', //最大6M
            'rootPath' => './Public/upload/', //根目录
            'savePath' => "$type/", //分类存储图片（相对于根目录）
            'subName' => '', //每个type的dir (相对于savePath目录)
            'saveName' => (string)($record + 1), //文件命名规则
            'exts' => array('jpg', 'jpeg', 'png'),
            'replace' => true
        );
        $upload = new \Think\Upload($config);
        $res = $upload->upload();
        if (!$res)
        {
            echo '0'; //上传错误
        }
        else
        {
            $feedback[0] = $db;
            $feedback[1] = $res;
            return $feedback;
        }
    }//标题下的上传文件

    public function addimg()
    {
        $type = $_REQUEST['type'];//图片类型位置
        $verify_code = $_REQUEST['code']; //表单ID
        $file = NULL;
        $_SESSION['picnum'] = isset($_SESSION['picnum']) ? $_SESSION['picnum'] : 0;
        $config = array(
            'maxSize' => '6291456', //最大6M
            'rootPath' => './Public/upload/', //根目录
            'savePath' => $type.'/', //分类存储图片（相对于根目录）
            'subName' => $verify_code, //每个表单的temp_dir (相对于savePath目录)
            'saveName' => (string)$verify_code.'-'.(string)($_SESSION['picnum']++), //文件命名规则
            'exts' => array('jpg','jpeg','png'),
            'replace' => true
        );
        $upload = new \Think\Upload($config);
        $res = $upload ->upload();
        if(!$res)
        {
            echo $upload->getError(); //上传失败
        }
        else
        {
            foreach($res as $file)
            {
                echo '../../newAdmin/'.$config['rootPath'].$config['savePath'].$verify_code.'/'.$file['savename'];//上传成功返回图片地址
            }
        }
    }//文中新增图片


    private function saveimg($verify_code,$content,$type)
    {
        $pattern = "\/$verify_code\/";
        $subject = $content;
        $news = M('news');
        $id = $news -> ORDER('id desc') -> field('id') -> find();
        $id = (int)$id+1;
        $replacement = "/$id/";
        $times = preg_match_all('/<img.+src="(.+?)".+?>/',$subject,$matches);
        for($i = 0; $i < $times; $i++)
        {
            $file = $matches[1][$times];
            $newfile = "../../newAdmin/Public/upload/$type/$id/";
            copy($file,$newfile);
        }
        $content = preg_replace($pattern,$replacement,$subject);
        return $content;
    }//保存时转移img

    public function saveAll()
    {
        $title = I('post.title');
        $type = I('post.type');
        $verify_code = I('post.vc');//表单ID
        $content = I('post.content');  //代码形式的内容
        $db = M("$type");
        if(isset($_FILES['pic']))
        {
            $config = array(
                'maxSize' => '6291456', //最大6M
                'rootPath' => './Public/upload/', //根目录
                'savePath' => "$type/", //分类存储图片（相对于根目录）
                'subName' => '', //每个type的dir (相对于savePath目录)
                'saveName' => $title, //文件命名规则
                'exts' => array('jpg','jpeg','png'),
                'replace' => true
            );
            $upload = new \Think\Upload($config);
            $res = $upload -> uploadOne($_FILES['pic']);
            if(!$res)
            {
                echo '0';
                exit();
            }
            $picurl = $res['savepath'].$res['savename'];
            $data['picurl'] = $picurl;
        }
        $content = $this -> saveimg($verify_code,$content,$type); //提取tempurl并转换成正式url,复制temp文件
        $data['title'] = $title;
        $data['content'] = $content;
        $data['createtime'] = date('Y-M-D');
        $feed = $db -> add($data);
        if(!$feed)
        {
            echo 'fail';
        }
        else
        {
            $this -> deldir($config['rootPath'].$config['savePath'].$verify_code);
            echo '1';
        }
    }


    //---------------------WangEditor处理函数-----------------

    public function primerman()
    {
        $raw = I('post.',NULL); //接收数据
        $data = array(
            'name' => $raw['name'],
            'qq' => $raw['qq'],
            'mail' => $raw['mail'],
            'tel' => $raw['tel'],
            'shorttel' => I('shorttel',NULL)
        );
        $db = M('primerman');
        $res = $db -> add($data);
        if($res)
        {
            $this -> ajaxReturn('1');
        }
        else
        {
            $this -> ajaxReturn('0');
        }
    }//新增联系人

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

    public function getNews($flag)
    {
        $flag = $flag ? 1 : 0;
        $news = M('news');
        $conditon['draft'] = $flag;
        $res = $news -> WHERE($conditon) -> select();
        $this -> ajaxReturn($res);
    }//获取动态

    public function search($key)
    {
        $news = M('news');
        $condition['title'] = array('like','%'.$key.'%');
//        $condition['contant'] = array('like','%'.$key.'%');
//        $condition['_logic'] = 'OR';
        $res = $news -> WHERE($condition) -> select();
        if($res)
        {
            $this -> ajaxReturn($res);
        }
        else
        {
            $this -> ajaxReturn('0');
        }
    }//在标题模糊搜索

    public function drafts($target)
    {
        $data['draft'] = 1;
        if($target == 'news')
        $this -> saveAll();
    }//调用之前的存储方法

    public function saveProject()
    {
        $title = I('post.title');
        $ddl = I('post.ddl',NULL);
        $link = I('post.link',NULL);
        $tool = I('post.tool',NULL);
        $type = I('post.type');
        $developer = I('post.developer',NULL);
        $verify_code = I('post.vcc');
        $content = I('post.content');
        $content = $this -> saveimg($verify_code,$content,$type);
        $data['title'] = $title;
        $data['ddl'] = $ddl;
        $data['url'] = $link;
        $data['tool'] = $tool;
        $data['developer'] = $developer;
        $data['content'] = $content;
        $db = M('project');
        $res = $db -> add($data);
        if($res)
        {
            echo '1';
        }
        else
        {
            echo '0';
        }
    }//保存项目中心

    public function addMember()
    {
        $grade = I('post.grade');
        $dep = I('post.dep');
        $res = $this -> uploadImg('member');
        $file = $res[1];
        $i = 0;
        foreach ($file as $item)
        {
            $url[$i] = $item['savepath'].$item['savename'];
            $i++;
        }
        $data['grade'] = $grade;
        $data['dep'] = $dep;
        $data['url'] = $url;
        $this -> ajaxReturn($data);
    }//返回一开始的届数和部门

    public function saveMember()
    {
        $num = I('post.num');
        $content = I('post.raw');
        $content = json_decode($content,TRUE);
        $db = M('member');
        for($i = 0; $i < $num; $i++) {
            $data['name'] = $content[$i]['name'];
            $data['pic'] = $content[$i]['pic'];
            $data['grade'] = $content[$i]['grade'];
            $data['dep'] = $content[$i]['dep'];
            $data['pos'] = $content[$i]['pos'];
            $data['remark'] = $content[$i]['remark'];
            $res = $db->add($data);
        }
        echo $res ? 1 : 0;
    }//保存部门成员

    public function showMember()
    {
        $id = I('post.id');
        $db = M('member');
        $res = $db -> WHERE("id = $id") -> find();
        $this -> ajaxReturn($res);
    }//提取人员信息

    public function updateMember()
    {
        $id = I('post.id');
        $data['name'] = I('post.name');
        $data['sex'] = I('post.sex');
        $data['pos'] = I('post.pos');
        $data['dep'] = I('post.dep');
        $data['grade'] = I('post.grade');
        $data['year'] = I('post.year');
        $data['remark'] = I('post.remark');
        $db = M('member');
        $res = $db -> WHERE("id = $id") -> save($data);
        echo $res ? 1 : 0;
    }//更新人员

    public function delMember()
    {
        $id = I('post.id');
        $db = M('member');
        $res = $db -> WHERE("id = $id") -> delete();
        echo $res ? 1 : 0;
    }//删除人员

    public function showAllMember()
    {
        $db = M('member');
        $res = $db -> select();
        $this -> ajaxReturn($res);
    }//展示全部人员
}