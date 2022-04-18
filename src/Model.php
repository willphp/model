<?php
/*--------------------------------------------------------------------------
 | Software: [WillPHP framework]
 | Site: www.113344.com
 |--------------------------------------------------------------------------
 | Author: no-mind <24203741@qq.com>
 | WeChat: www113344
 | Copyright (c) 2020-2022, www.113344.com. All Rights Reserved.
 |-------------------------------------------------------------------------*/
namespace willphp\model;
use willphp\model\build\ArrayIterator;
use willphp\model\build\Auto;
use willphp\model\build\Validate;
use willphp\model\build\Filter;
use willphp\db\Db;
use willphp\db\build\Query;
use willphp\collection\Collection;
/**
 * 模型基类
 * Class Model
 * @package willphp\model;
 * @author  no-mind
 */
abstract class Model implements \ArrayAccess, \Iterator {
	use ArrayIterator, Auto, Validate, Filter;
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
	protected $table; //表名
	protected $pk = 'id'; //表自增主键
	protected $db; //数据库驱动
	protected $config = []; //数据库配置	
	protected $allowFill = []; //允许填充字段	
	protected $denyFill = []; //禁止填充字段	
	protected $data = []; //模型数据	
	protected $fields = []; //读取字段	
	protected $original = []; //构建数据
	protected $autoTimestamp = 'int'; //自动写入时间戳字段类型(false不自动写入)：int|date|datetime|timestamp 
	protected $createTime = 'ctime'; //创建时间字段
	protected $updateTime = 'uptime'; //更新时间字段
	/**
	 * 构造函数
	 */
	public function __construct() {
		if (!$this->table) {
			$this->setTable($this->table);
		}
		$this->db = Db::connect($this->config, $this->table);
	}
	/**
	 * 设置表名
	 * @param $table
	 * @return $this
	 */
	protected function setTable($table) {
		if (empty($table)) {
			$model = basename(str_replace('\\', '/', get_class($this)));			
			$table = strtolower(trim(preg_replace('/([A-Z])/', '_\1\2', $model), '_'));
		}
		$this->table = $table;
		return $this;
	}
	/**
	 * 获取表名
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
	/**
	 * 获取主键
	 * @return mixed
	 */
	public function getPk() {
		return $this->pk;
	}
	/**
	 * 动作类型(新增或更新)
	 * @return int
	 */
	final public function action() {		
		return empty($this->data[$this->pk]) ? self::MODEL_INSERT : self::MODEL_UPDATE;
	}
	/**
	 * 获取数据
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}
	/**
	 * 设置data 记录信息属性
	 * @param array $data
	 * @return $this
	 */
	public function setData(array $data) {
		$this->data = array_merge($this->data, $data);
		$this->fields = $this->data;
		$this->getFormatAttribute();		
		return $this;
	}
	/**
	 * 用于读取数据成功时的对字段的处理后返回
	 * @param $field
	 * @return mixed
	 */
	protected function getFormatAttribute()	{
		foreach ($this->fields as $name => $val) {
			$n = preg_replace_callback('/_([a-z]+)/', function ($v) {
					return strtoupper($v[1]);
				}, $name);			
			$method = 'get'.ucfirst($n).'AtAttribute';
			if (method_exists($this, $method)) {
				$this->fields[$name] = $this->$method($val);
			}
		}		
		return $this->fields;
	}
	/**
	 * 对象数据转为数组
	 * @return array
	 */
	final public function toArray() {
		$data = $this->fields;
		foreach ($data as $k => $v) {
			if (is_object($v) && method_exists($v, 'toArray')) {
				$data[$k] = $v->toArray();
			}
		}		
		return $data;
	}
	/**
	 * 更新模型的时间戳
	 * @return bool
	 */
	final public function touch() {
		if ($this->action() == self::MODEL_UPDATE && $this->autoTimestamp && $this->updateTime) { 
			$data = [];
			$data[$this->updateTime] = $this->getFormatTime($this->autoTimestamp);
			return $this->db->where($this->pk, $this->data[$this->pk])->update($data);
		}		
		return false;
	}
	/**
	 * 更新或添加数据
	 * @param array $data 批量添加的数据
	 * @return bool
	 * @throws \Exception
	 */
	final public function save(array $data = []) {		
		$this->fieldFillCheck($data); //自动填充数据处理		
		$this->autoFilter(); //自动过滤		
		$this->autoOperation(); //自动完成		
		$this->formatFields(); //处理时期字段
		if ($this->action() == self::MODEL_UPDATE) {
			$this->original = array_merge($this->data, $this->original);			
		}	
		//自动验证
		if (!$this->autoValidate()) {
			return false;
		}
		//更新条件检测
		$res = null;			
		switch ($this->action()) {				
			case self::MODEL_UPDATE:
				$res = $this->db->where($this->pk, $this->data[$this->pk])->update($this->original);
				if ($res) {
					$this->setData($this->db->find($this->data[$this->pk]));
				}
				break;
			case self::MODEL_INSERT:
				$res = $this->db->insertGetId($this->original);
				if ($res) {
					if (is_numeric($res) && $this->pk) {
						$this->setData($this->db->find($res));
					}
				}
				break;
		}
		$this->original = [];		
		return $res ? $this : false;
	}	
	/**
	 * 批量设置做准备数据
	 * @return $this
	 */
	final private function formatFields() {		
		if ($this->action() == self::MODEL_UPDATE) {
			$this->original[$this->pk] = $this->data[$this->pk];			
		}
		//自动填充创建时间和更新时间
		if ($this->autoTimestamp) {
			if ($this->updateTime) {
				$this->original[$this->updateTime] = $this->getFormatTime($this->autoTimestamp);
			}			
			if ($this->action() == self::MODEL_INSERT && $this->createTime) {
				$this->original[$this->createTime] = $this->getFormatTime($this->autoTimestamp);
			}
		}		
		return $this;
	}	
	/**
	 * 根据类型获取时间  类型：int|date|datetime|timestamp
	 * @return 格式时间
	 */
	protected function getFormatTime($type = '') {
		$time = $_SERVER['REQUEST_TIME'];
		if ($type == 'date') {
			return date('Y-m-d', $time);
		} elseif ($type == 'datetime') {
			return date('Y-m-d H:i:s', $time);
		} elseif ($type == 'timestamp') {
			return date('Ymd His', $time);
		}
		return $time;		
	}
	/**
	 * 自动填充数据处理
	 * @param array $data
	 * @throws \Exception
	 */
	final private function fieldFillCheck(array $data) {
		if (empty($this->allowFill) && empty($this->denyFill)) {
			return;
		}
		//允许填充的数据
		if (!empty($this->allowFill) && $this->allowFill[0] != '*') {
			$data = $this->filterKeys($data, $this->allowFill, 0);
		}
		//禁止填充的数据
		if (!empty($this->denyFill)) {
			if ($this->denyFill[0] == '*') {
				$data = [];
			} else {
				$data = $this->filterKeys($data, $this->denyFill, 1);
			}
		}
		$this->original = array_merge($this->original, $data);
	}
	/**
	 * 根据下标过滤数据元素
	 * @param array $data 原数组数据
	 * @param       $keys 参数的下标
	 * @param int   $type 1 存在在$keys时过滤  0 不在时过滤
	 * @return array
	 */
	public function filterKeys(array $data, $keys, $type = 1) {
		$tmp = $data;
		foreach ($data as $k => $v) {
			if ($type == 1) {				
				if (in_array($k, $keys)) {
					unset($tmp[$k]);
				}
			} else {				
				if (!in_array($k, $keys)) {
					unset($tmp[$k]);
				}
			}
		}		
		return $tmp;
	}
	/**
	 * 删除数据
	 * @return bool
	 */
	final public function destory() {		
		if (!empty($this->data[$this->pk])) {
			if ($this->db->delete($this->data[$this->pk])) {
				$this->setData([]);				
				return true;
			}
		}		
		return false;
	}
	/**
	 * 获取模型值
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)	{
		if (isset($this->fields[$name])) {
			return $this->fields[$name];
		}
		if (method_exists($this, $name)) {
			return $this->$name();
		}
	}
	/**
	 * 设置模型数据值
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value) {
		$this->original[$name] = $value;
		$this->data[$name] = $value;
	}
	/**
	 * 魔术方法
	 * @param $method
	 * @param $params
	 * @return mixed
	 */
	public function __call($method, $params) {
		$res = call_user_func_array([$this->db, $method], $params);
		return $this->returnParse($method, $res);
	}	
	protected function returnParse($method, $result) {
		if (!empty($result)) {
			switch (strtolower($method)) {
				case 'find':					
					return $this->setData($result);
				case 'paginate':
					$collection = Collection::make([]);
					foreach ($result as $k => $v) {
						$instance = new static();
						$collection[$k] = $instance->setData($v);
					}					
					return $collection;
				default:
					if ($result instanceof Query) {
						return $this;
					}
			}
		}		
		return $result;
	}
	/**
	 * 调用静态方法
	 * @param $method
	 * @param $params
	 * @return mixed
	 */
	public static function __callStatic($method, $params) {
		return call_user_func_array([new static(), $method], $params);
	}
}