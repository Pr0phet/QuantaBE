<?php
namespace Home\Controller;
use Think\Controller;
use Think\Upload;

class IndexController extends Controller
{

    /**
     * @name 检查登陆状态
     * @global  $_SESSION['validate']
     * @return 已登陆 返回用户名  未登陆(0)
     */
    public function check()
    {
        if($_SESSION['validate'] == 1)
        {
            echo '0';
        }
        else
        {
            echo $_SESSION['name'];
        }
    }//检查登录状况


    /**
     * @name 登陆操作
     * @param POST(user) 账号
     * @param POST(pass) 密码
     * @return 密码正确(1) 密码错误 (0)
     */
    private function login()
    {
        $user = I('post.user');
        $pass = I('post.pass');
        $admin = M('admin');
        $condition['name'] = $user;
        $res = $admin -> WHERE($condition) -> find();
        if($res['password'] != $pass)
        {
            echo '0'; //密码错误
        }
        else
        {
            $_SESSION['validate'] = 1;
            $_SESSION['name'] = $user;
            echo '1';//正确
        }
    }//登录操作

    /**
     * @name 新增banner
     * @param POST(title) 标题
     * @param POST(url) 添加链接
     * @param POST(remarks) 备注
     * @return 新增成功 1 新增失败 0
     */
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

    /**
     * @name 获取所有banner
     * @return 所有banner的信息(ajax方式)
     * @return 返回的结构为$res['*'] *包括字段 id，title，picurl，url，createtime
     */
    public function getBanner()
    {
        $banner = M('banner');
        $res = $banner -> select();
        $this -> ajaxReturn($res);
    }//获取所有banner信息

    /**
     * @name 删除banner
     * @param POST(id) PS:此id为之前获取banner时发送的id
     * @return 查询数据库失败(00) 操作数据库失败(01) 成功(1)
     */
    public function rmBanner()
    {
        $id = I('post.id');
        $banner = M('banner');
        $file = $banner -> WHERE("id = $id") -> find();
        $res = unlink($file['picurl']);
        if(!$res)
        {
            echo '00';
            exit();
        }
        $res = $banner -> WHERE("id= $id") -> delete();
        if($res)
        {
            echo "1";
        }
        else
        {
            echo '01';
        }
    }//删除banner

    /**
     * @name 删除单文件|清空文件夹内的文件并删除文件夹
     * @param $filepath 文件夹路径
     * @return 成功string（已删除$filepath） 失败string(WrongUrl)
     */
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
        else
        {
            echo "WrongUrl";
        }
    }//删除文件或文件夹！慎用！

    //---------------------WangEditor处理函数-----------------

    /**
     * @name 标题下的上传图片
     * @param $type 图片分类
     * @return mixed 二维数组0为数据库链接，1为上传文件信息
     */
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

    /**
     * @name WangEditor在文中插入图片
     * @param 在WangEditor中的参数传输（type）图片类型
     * @param 在WangEditor中的参数传输（code）表单ID
     * @return 上传成功文件url
     */
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


    /**
     * @name 正式保存时的图片url处理
     * @param $verify_code 表单id
     * @param $content 代码形式的内容
     * @param $type 保存类型
     * @return mixed 修改过url的代码内容
     */
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

    /**
     * @name WangEditor正式保存 || 新增动态的保存
     * @param POST(title) 标题
     * @param POST(type) 内容类型
     * @param POST(vc) 表单ID
     * @param POST(content) 内容（html代码形式）
     * @return 成功(1) 上传图片失败(00) 新增数据错误(01)
     */
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
                echo '00';
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
            echo '01';
        }
        else
        {
            $this -> deldir($config['rootPath'].$config['savePath'].$verify_code);
            echo '1';
        }
    }


    //---------------------WangEditor处理函数-----------------

    /**
     * @name 更新官网联系人
     * @param POST(name) 姓名
     * @param POST(qq) QQ
     * @param POST(mail) 邮箱
     * @param POST(tel) 联系号码
     * @param POST(shorttel)若无请勿POST
     * @return 成功(1) 失败(0)
     */
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
            echo '1';
        }
        else
        {
            echo '0';
        }
    }//新增联系人

    /**
     * @name 删除动态
     * @param POST(id) 之前传输的动态id
     * @return 成功(1) 失败(0)
     */
    public function rmNews()
    {
        $id = I('post.id');
        $news = M('news');
        $res = $news -> WHERE('id ='.$id) -> delete();
        if($res)
        {
            echo 'Success';
        }
        else
        {
            echo '删除失败，请联系管理员';
        }
    }//删除动态

    /**
     * @name 获取所有同台
     * @param POST(flag) 是否为草稿 是(1) 否(0)
     * @return 数组，字段('id','title','picurl,'url',createtime)分别是(动态id：删除用，标题，图片链接，网站链接，创建时间)
     */
    public function getNews()
    {
        $flag = I('post.flag');
        $flag = $flag ? 1 : 0;
        $news = M('news');
        $conditon['draft'] = $flag;
        $res = $news -> WHERE($conditon) -> select();
        $this -> ajaxReturn($res);
    }//获取动态

    /**
     * @name 查询关键字
     * @param POST(type) 查询类别
     * @param POST(key) 关键字
     * @return 成功 数组，字段('id'....)类别不同查询不同，详情Debug的时候可以商量 失败(0)
     */
    public function search()
    {
        $key = I('post.key');
        $type = I('post.type');
        $news = M($type);
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
            echo '0';
        }
    }//在标题模糊搜索

    /**
     * @name 保存为草稿
     * @param 与saveALL方法参数一样
     */
    public function drafts()
    {
        $data['draft'] = 1;
        $this -> saveAll();
    }//调用之前的存储方法

    /**
     * @name 项目中心的保存
     * @param POST(title) 标题
     * @param POST(date) 日期
     * @param POST(link)
     * @param POST(tool)
     * @param POST(type)
     * @param POST(developer)
     * @param POST(vcc)
     * @param POST(content)
     * @return 成功(1) 失败(0)
     */
    public function saveProject()
    {
        $title = I('post.title');
        $date = I('post.date',NULL);
        $link = I('post.link',NULL);
        $tool = I('post.tool',NULL);
        $type = I('post.type');
        $developer = I('post.developer',NULL);
        $verify_code = I('post.vcc');
        $content = I('post.content');
        $content = $this -> saveimg($verify_code,$content,$type);
        $data['title'] = $title;
        $data['date'] = $date;
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

    /**
     * @name 新增成员时返回届数和上传照片
     * @param POST(grade) 届数
     * @param POST(dep) 部门
     * @return 数组，字段('grade','dep','url'),（届数，部门，图片url(数组)）
     */
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

    /**
     * @name 保存部门成员
     * @param POST(num) 保存人数
     * @param POST(raw) 这个是将每名成员的数据单独打包成一个组，内应有name,pic,grade,dep,pos,remark(名称，图片URL，届数，部门，职位，备注:可空)
     * @return 成功(1) 失败(0)
     */
    public function saveMember()
    {
        $num = I('post.num');
        $content = I('post.raw');
        $content = json_decode($content,TRUE);
        $db = M('member');
        $res = NULL;
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

    /**
     * @name 获取某个管理层信息
     * @param POST(id) 该成员的ID
     * @return 数组，字段（'id','name','sex','grade','dep','pos','remark'）,（id，姓名，性别：新成员为空，届数，部门，职位，备注）
     */
    public function showMember()
    {
        $id = I('post.id');
        $db = M('member');
        $res = $db -> WHERE("id = $id") -> find();
        $this -> ajaxReturn($res);
    }//提取人员信息

    /**
     * @name 更新成员信息
     * @param POST(id) 成员id
     * @param POST(name) 名称
     * @param POST(sex) 性别
     * @param POST(pos) 职位
     * @param POST(dep) 部门
     * @param POST(grade) 届数
     * @param POST(year) 年级
     * @param POST(remark) 备注
     * @return 成功(1) 失败(0)
     */
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

    /**
     * @name 删除人员
     * @param POST(id) 人员ID
     * @return 成功(1) 失败(0)
     */
    public function delMember()
    {
        $id = I('post.id');
        $db = M('member');
        $res = $db -> WHERE("id = $id") -> delete();
        echo $res ? 1 : 0;
    }//删除人员

    /**
     * @name 提取所有人员信息
     * @return 二维数组，字段('id','name','sex','grade','dep','pos','remark'),(id，姓名，性别，届数，部门，职位，备注)
     */
    public function showAllMember()
    {
        $db = M('member');
        $res = $db -> select();
        $this -> ajaxReturn($res);
    }//展示全部人员
}