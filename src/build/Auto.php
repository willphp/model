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
/**
 * 自动完成
 * Class Auto
 * @package willphp\model\build
 */
trait Auto {	
	protected $auto = []; //自动完成设置	
	/**
	 * 自动完成处理
	 * @return void/mixed
	 */
	final protected function autoOperation() {		
		if (empty($this->auto)) {
			return;
		}
		$data = & $this->original;
		foreach ($this->auto as $name => $auto) {
			//处理类型
			$auto[2] = isset($auto[2]) ? $auto[2] : 'string';
			//验证条件
			$auto[3] = isset($auto[3]) ? $auto[3] : self::EXIST_AUTO;
			//验证时间
			$auto[4] = isset($auto[4]) ? $auto[4] : self::MODEL_BOTH;			
			if ($auto[3] == self::EXIST_AUTO && ! isset($data[$auto[0]])) {
				//有这个字段处理
				continue;
			} else if ($auto[3] == self::NOT_EMPTY_AUTO && empty($data[$auto[0]])) {
				//不为空时处理
				continue;
			} else if ($auto[3] == self::EMPTY_AUTO && ! empty($data[$auto[0]])) {
				//值为空时处理
				continue;
			} else if ($auto[3] == self::NOT_EXIST_AUTO && isset($data[$auto[0]])) {
				//值不存在时处理
				continue;
			} else if ($auto[3] == self::MUST_AUTO) {
				//必须处理
			}
			if ($auto[4] == $this->action() || $auto[4] == self::MODEL_BOTH) {
				//为字段设置默认值				
				if (empty($data[$auto[0]])) {
					$data[$auto[0]] = '';					
				}
				if ($auto[2] == 'method') {
					$data[$auto[0]] = call_user_func_array([$this, $auto[1]], [$data[$auto[0]], $data]);
				} else if ($auto[2] == 'function') {	
					if (!function_exists($auto[1])) {
						throw new \Exception($auto[1].' 函数不存在');
					}
					$data[$auto[0]] = $this->need_params($auto[1])? $auto[1]($data[$auto[0]]) : $auto[1]();
				} else if ($auto[2] == 'string') {
					$data[$auto[0]] = $auto[1];
				}
			}
		}		
		return true;
	}
	/**
	 * 检测函数是否需要参数 
	 * @param string $func_name
	 * @return boolean
	 */
	protected function need_params($func_name) {
		$reflect = new \ReflectionFunction($func_name);		
		return !empty($reflect->getParameters());
	}
}