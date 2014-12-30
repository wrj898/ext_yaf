<?php

/**
 * @name Bootstrap
 * @author jonson
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf_Bootstrap_Abstract {
	public function _initConfig() {
//        Yaf_Dispatcher::throwException(false);
		//把配置保存起来
		$arrConfig = Yaf_Application::app()->getConfig();
		Yaf_Registry::set('config', $arrConfig);
	}
    
    public function _initLoader(){
    	$result = Yaf_Loader::getInstance();
    }

	public function _initPlugin(Yaf_Dispatcher $dispatcher) {
		//注册一个插件
	}

	public function _initRoute(Yaf_Dispatcher $dispatcher) {
		//在这里注册自己的路由协议,默认使用简单路由
		$config = new Yaf_Config_Ini(APPLICATION_PATH . "/conf/router.ini");
		$router = Yaf_Dispatcher::getInstance()->getRouter();
		$router->addConfig($config->routes);
	}

	public function _initView(Yaf_Dispatcher $dispatcher) {
		//在这里注册自己的view控制器，例如smarty,firekylin
		//关闭Yaf View
		$view = Yaf_Dispatcher::getInstance();
		$view->disableView();
	}
}
