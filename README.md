##数据模型

model组件是对数据进行处理的ORM框架

###安装组件

使用 composer命令进行安装或下载源代码使用(依赖config,db,validate,collection组件)。

    composer require willphp/model

>WillPHP框架已经内置此组件，无需再安装。

###条件常量(验证,处理,过滤通用)

	const AT_MUST = 1; //必须(默认)
	const AT_NOT_NULL = 2; //有值
	const AT_NULL = 3; //空值
	const AT_SET = 4; //有字段
	const AT_NOT_SET = 5; //无字段

###时机常量(验证,处理,过滤通用)

    const IN_BOTH = 1; //全部(默认)
    const IN_INSERT = 2; //新增
    const IN_UPDATE = 3; //更新   

###定义模型

如在app/home/model下定义一个模型：

    namespace app\home\model;
    use willphp\model\Model;
    class Test extends Model {
        protected $table = 'test'; //表名
        protected $pk = 'id'; //主键
    }

###模型属性

默认属性如下：

    protected $table; //表名
    protected $pk = 'id'; //表自增主键
    protected $dbConfig = []; //数据库配置(可选)       
    protected $allowFill = ['*']; //允许填充字段  
    protected $denyFill = []; //禁止填充字段  
    protected $autoTimestamp = 'int'; //自动时间戳int|date|datetime|timestamp(false) 
    protected $createTime = 'ctime'; //创建时间字段
    protected $updateTime = 'uptime'; //更新时间字段
    protected $prefix; //表前缀(可选)
    protected $errors = []; //错误信息
    protected $isBatch = false; //是否批量验证
    protected $validate = []; //验证规则        
    protected $auto = []; //自动处理
    protected $filter = [];  //自动过滤设置	

###前后置方法

    //新增
    protected function _before_insert(array &$data) {}
    protected function _after_insert(array $data) {}    
    //更新
    protected function _before_update(array &$data) {}
    protected function _after_update(array $old, array $new) {}     
    //删除
    protected function _before_delete(array $data) {}
    protected function _after_delete(array $data) {}    
    //唯一验证前置
    protected function _before_unique($data) {}
    
使用示例：

    protected function _before_insert(&$data) {
        //在新增之前用处理密码(同自动处理)
        $data['password'] = md5($data['password'].$data['username']);
    }   
    protected function _before_delete($data) {
        //删除之前设置条件status=0的才能删除
        $this->db = $this->db->where('status', 0);
    }   
    protected function _before_unique($data) {
        //验证唯一性时加入status=1的条件
        $this->db = $this->db->where('status', 1);
    }              
 
###模型方法

    //映射方式
    $test = new Test();
    $test['name'] = 'willphp';
    $test['status'] = 1;
    $test->save(); //新增(不存在id主键)
    $model ＝ Test::find(1); //获取单条数据
    $model->name = 'test name'; 
    $model->save(); //修改(id=1)数据
    $model->destory(); //删除(id=1)数据
    //传统方式
    $data = ['name'=>'abc'];
    Test::save($data); 
   
###数据库方法

在模型中可以使用所有数据库方法，如：

    $list = model('blog')->where('status', 1)->order('id DESC')->paginate(10);
    model('blog')->delete(1);

###自动验证

    namespace app\home\model;
    use willphp\core\Model;
    class User extends Model {
        protected $table = 'user'; //表名
        protected $pk = 'id'; //主键
        //格式['表单字段', '验证规则[|...]', '错误提示[|...]', '[条件]', '[时机]']
        protected $validate = [
            //新增时必须验证(模型中unique规则无须参数)
            ['username', 'required|unique', '用户必须|用户已存在', AT_MUST, IN_INSERT],
            ['password', '/^\w{6,12}$/', '密码6-12位', 1, 2],  
            ['repassword', 'confirm:password', '确认密码不一致', 1, 2],    
            ['iq', 'checkIq', 'IQ必须大于100', 1, 1],
            //新增或更新时，有值就验证
            ['email', 'email', '邮箱格式错误', AT_NOT_NULL, IN_BOTH],             
        ];
        //新增验证规则
        public function checkIq($value,$field,$params,$data) {
            return $value > 100;
        }
    } 

>更多验证规则请查看 willphp/validate 组件

###自动处理

处理类型：

    string      //填充(默认)
    function    //函数
    method      //自定义方法

示例：

    namespace app\home\model;
    use willphp\core\Model;
    class User extends Model {
        protected $table = 'user'; //表名
        protected $pk = 'id'; //主键
        //格式['表单字段', '处理方式[|...]', '处理类型', '[条件]', '[时机]']
        protected $auto = [     
            //有值时       
            ['password', 'setPwd', 'method', AT_NOT_NULL, IN_BOTH], 
            //新增时必须     
            ['status', '1', 'string', AT_MUST, IN_INSERT],          
            ['addtime', 'time', 'function', 1, 2],              
        ];
        //密码加密
        public function setPwd($val, $data) {
            return md5($val);
        }
    }  

###自动过滤

    namespace app\home\model;
    use willphp\core\Model;
    class User extends Model {
        protected $table = 'user'; //表名
        protected $pk = 'id'; //主键  
        //格式['表单字段', '[条件]', '[时机]']        
        protected $filter = [
            //在更新时，密码为空则不修改密码
            ['password', AT_NULL, IN_UPDATE],
        ];
    }
 
###获取处理

当获取ctime字段时进行处理：

	//格式：get[字段名(第一个字母大写)]Attr
	public function getCtimeAttr($val) {
	    return date('Y-m-d H:i:s', $val);
	}   

###使用示例

	use app\home\model\Test;
	
	//新增数据
	$test = new Test();
	$test['name'] = 'willphp';
	$test['status'] = 1;
	$test->save();

	//修改数据
	$model ＝ Test::find(1);
	$model->name = 'test name'; 
	$model->save(); 
	//更新时间
	$model->touch(); //更新updateTime

	//删除
	$model =Test::find(3);
	$model->destory();
	//或
	Test::delete(8); 
	Test::delete('5,6'); 

	//获取错误
	$model->getError();

	//使用db组件的方法
	$obj = Test::where('status', 1)->paginate(10); 
	foreach ($obj as $vo ) {
		dump($vo['username']);
	}
	echo $obj->links(); //显示分页html	
