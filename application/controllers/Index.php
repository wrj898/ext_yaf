<?php

class IndexController extends ControllerExtends {
	public function indexAction() {
		echo $this->layouts;
		echo 111;
	}
	public function secondAction() {
		echo 'second';
	}
}
