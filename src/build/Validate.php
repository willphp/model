<?php
/*--------------------------------------------------------------------------
 | Software: [WillPHP framework]
 | Site: www.113344.com
 |--------------------------------------------------------------------------
 | Author: no-mind <24203741@qq.com>
 | WeChat: www113344
 | Copyright (c) 2020-2022, www.113344.com. All Rights Reserved.
 |-------------------------------------------------------------------------*/
namespace willphp\model\build;
use willphp\validate\build\ValidateRule;
/**
 * 自动验证
 * Class Validate
 * @package willphp\model\build
 */
trait Validate {	
	protected $validate = []; //自动验证	
	protected $error = []; //验证错误	
	/**
	 * 获取操作错误信息
	 * @return array
	 */
	public function getError() {
		return $this->error;
	}	
	/**
	 * 设置错误提示
	 * @param array|string $error
	 */
	public function setError($error) {
		$error = is_array($error) ? $error : [$error];
		$this->error = array_merge($this->error, $error);
	}
	/**
	 * 自动验证数据
	 * @return bool
	 * @throws \Exception
	 */
	final protected function autoValidate() {
		$this->setError([]);
		if (empty($this->original)) {
			throw new \Exception('No data for operation');
		}
		if (empty($this->validate)) {
			return true;
		}
		$validateRule = new ValidateRule();
		$data = &$this->original;
		foreach ($this->validate as $validate) {			
			$validate[3] = isset($validate[3]) ? $validate[3] : self::EXIST_VALIDATE;
			if ($validate[3] == self::EXIST_VALIDATE && ! isset($data[$validate[0]])) {
				continue;
			} else if ($validate[3] == self::NOT_EMPTY_VALIDATE && empty($data[$validate[0]])) {				
				continue;
			} else if ($validate[3] == self::EMPTY_VALIDATE && ! empty($data[$validate[0]])) {				
				continue;
			} else if ($validate[3] == self::NOT_EXIST_VALIDATE && isset($data[$validate[0]])) {				
				continue;
			} else if ($validate[3] == self::MUST_VALIDATE) {
				//必须处理
			}
			$validate[4] = isset($validate[4]) ? $validate[4] : self::MODEL_BOTH;			
			if ($validate[4] != $this->action() && $validate[4] != self::MODEL_BOTH) {
				continue;
			}			
			$field = $validate[0];			
			$actions = explode('|', $validate[1]);			
			$error = $validate[2];			
			$value = isset($data[$field]) ? $data[$field] : '';
			foreach ($actions as $action) {
				$info   = explode(':', $action);
				$method = $info[0];
				$params = isset($info[1]) ? $info[1] : '';				
				if (method_exists($this, $method)) {					
					if ($this->$method($field, $value, $params, $data) != true) {
						$this->error[$field] = $error;
					}
				} else if (method_exists($validateRule, $method)) {
					if ($validateRule->$method($field, $value, $params, $data) != true) {
						$this->error[$field] = $error;
					}
				} else if (function_exists($method)) {
					if ($method($value) != true) {
						$this->error[$field] = $error;
					}
				} else if (substr($method, 0, 1) == '/') {					
					if ( ! preg_match($method, $value)) {
						$this->error[$field] = $error;
					}
				}
			}
		}
		\willphp\validate\Validate::respond($this->error);		
		return $this->error ? false : true;
	}
	/**
	 * 自动验证字段值唯一(自动验证使用)
	 * @param $field 字段名
	 * @param $value 字段值
	 * @param $param 参数
	 * @param $data  提交数据
	 * @return bool 验证状态
	 */
	final protected function unique($field, $value, $param, $data) {
		//表主键
		$db = $this->db->where($field, $value);
		if ($this->action() == self::MODEL_UPDATE) {
			$db->where($this->pk, '<>', $this->data[$this->pk]);
		}
		if (empty($value) || !$db->find()) {
			return true;
		}
	}
}