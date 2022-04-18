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
trait ArrayIterator {
	public function offsetSet($key, $value) {
		$this->original[$key] = $value;
		$this->data[$key] = $value;
		$this->fields[$key] = $value;
	}
	public function offsetGet($key)	{
		return isset($this->fields[$key]) ? $this->fields[$key] : null;
	}
	public function offsetExists($key) {
		return isset($this->data[$key]);
	}
	public function offsetUnset($key) {
		if (isset($this->original[$key])) {
			unset($this->original[$key]);
		}
		if (isset($this->data[$key])) {
			unset($this->data[$key]);
		}
		if (isset($this->fields[$key])) {
			unset($this->fields[$key]);
		}
	}
	function rewind() {
		reset($this->data);
	}
	public function current() {
		return current($this->fields);
	}
	public function next() {
		return next($this->fields);
	}
	public function key() {
		return key($this->fields);
	}
	public function valid()	{
		return current($this->fields);
	}
}