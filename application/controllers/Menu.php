<?php

class MenuController extends ControllerExtends {
	public function indexAction() {

	}

	public function createAction() {
		print_r($this->Request());
	}

	public function readAction() {
		$menu = Common_MenuModel::getInstance();
		$menuCollection = $menu->findByCondition();
		$m = new stdClass();
		$m->code = $menuCollection ? EXECUTE_SUCCESS : EXECUTE_FAIL;
		$m->data = $menuCollection ? Operation::generateTree($menuCollection, "menu_id", "parent_menu", "children") : "Empty";
		exit(json_encode($m));
	}

	public function deleteAction($id = null) {
		print_r($this->Request());
	}

	public function updateAction($id) {
		print_r($this->Request());
	}
}
