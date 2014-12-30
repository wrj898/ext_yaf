<?php

class IndexController extends Yaf_Controller_Abstract {
	public function indexAction() {
		echo 111;
	}
	public function secondAction() {
		$v = I('get.v','',Null);
		var_dump($v);
		echo 'second';
	}
}
