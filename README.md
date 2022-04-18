# 数据模型
model组件用于对数据逻辑和存取进行处理

#开始使用

####安装组件
使用 composer 命令进行安装或下载源代码使用。

    composer require willphp/model

> WillPHP 框架已经内置此组件，无需再安装。

####定义模型

如在app/home/model下定义一个模型：

	namespace app\home\model;
	use willphp\model\Model;
	class Test extends Model {}

定义后可使用 willphp/db 组件的所有功能：

	use app\home\model\Test;
	Test::where('status', 1)->paginate(6);

####模型属性

可设置如下属性：

	protected $table; //表名 (默认为模型名称，不包含表前缀)
	protected $pk = 'id'; //表自增主键(默认id)
	protected $config = []; //数据库配置	
	protected $allowFill = []; //允许填充字段	
	protected $denyFill = []; //禁止填充字段
	protected $autoTimestamp = 'int'; //自动写入时间戳字段类型(false不自动写入)：int|date|datetime|timestamp 
	protected $createTime = 'ctime'; //创建时间字段(新增时自动写入)
	protected $updateTime = 'uptime'; //更新时间字段(更新时自动写入)
	protected $auto = []; //自动完成设置	
      protected $filter = [];	 //自动过滤设置
	protected $validate = []; //自动验证设置	

####模型常量

	//----------自动验证----------	
	const EXIST_VALIDATE = 1; //有字段时验证	
	const NOT_EMPTY_VALIDATE = 2; //值不为空时验证	
	const MUST_VALIDATE = 3; //必须验证	
	const EMPTY_VALIDATE = 4; //值是空时验证	
	const NOT_EXIST_VALIDATE = 5; //不存在字段时处理
	//----------自动完成----------	
	const EXIST_AUTO = 1; //有字段时	
	const NOT_EMPTY_AUTO = 2; //值不为空时
	const MUST_AUTO = 3; //必须处理
	const EMPTY_AUTO = 4; //值是空时	
	const NOT_EXIST_AUTO = 5; //不存在字段时
	//----------自动过滤----------	
	const EXIST_FILTER = 1;	//存在时过滤
	const NOT_EMPTY_FILTER = 2; //值不为空时过滤
	const MUST_FILTER = 3; //必须过滤
	const EMPTY_FILTER = 4; //值是空时过滤
	const NOT_EXIST_FILTER = 5; //不存在字段时过滤
	//--------处理时机/自动完成&自动验证共用
	const MODEL_INSERT = 1; //插入时处理	
	const MODEL_UPDATE = 2; //更新时处理	
	const MODEL_BOTH = 3; //全部情况下处理

####自动验证

使用示例(验证条件, 处理时机 请查看以上常量)：

	//格式：[字段名, 验证规则, 错误信息, 验证条件, 处理时机]
	protected $validate=[
			['username','unique','用户已存在',self::MUST_VALIDATE, self::MODEL_BOTH],
			['status', '/^\d+$/', '状态必须是数字', self::MUST_VALIDATE, self::MODEL_BOTH]
	];

更多验证规则请查看 willphp/validate 组件

####自动完成

使用示例(处理条件, 处理时机 请查看以上常量)：

	//格式：[字段名, 处理设置, 处理方式, 处理条件, 处理时机]
	protected $auto = [		
			//自动对pwd使用setPassword方法					
			['pwd', 'setPassword', 'method', self::MUST_AUTO,  self::MODEL_BOTH],
			//自动添加status=2
			['status', 2, 'string',  self::MUST_AUTO,  self::MODEL_BOTH],
			//使用函数处理
			['addtime','strtotime|strupper','function', self::MUST_AUTO , self::MODEL_INSERT],
	];	
	public function setPassword($val, $data){
		return md5($val);
	}

处理方式：method 使用方法, string 直接赋值， function 使用函数结果

####自动过滤

使用示例(处理条件, 处理时机 请查看以上常量)：

	//格式：[字段名, 处理条件, 处理时机]
	protected $filter= [				
				//当密码为空时，从更新或添加数据中删除密码字段
				['pwd', self::EMPTY_FILTER, self::MODEL_BOTH ]
	];
 
####获取处理

当获取ctime字段时进行处理：

	//格式：get[字段名(第一个字母大写)]AtAttribute
	public function getCtimeAtAttribute($val) {
		return date('Y-m-d H:i:s', $val);
	}

####使用示例

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

	
