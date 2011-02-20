<?php  

defined('C5_EXECUTE') or die(_("Access Denied."));


/**
 * @package Core
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * A generic object that every block or page controller extends
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

class Controller {

	// sets is an array of items set by the set() method. Whew.
	private $sets = array();
	private $helperObjects = array();
	private $headerItems = array();
	private $theme = null;
	private $c; // collection

	
	/**
	 * Items in here CANNOT be called through the URL
	 */
	private $restrictedMethods = array();
	
	public function __construct() {

		if (!isset($this->helpers)) {
			$this->helpers[] = 'html';
		}
		foreach($this->helpers as $h) {
			$$h = Loader::helper($h);
			$this->helperObjects[$h] = $$h;
		}
		
	}	

	/**
	 * @access private
	 */
	public function getRenderOverride() {
		return $this->renderOverride;
	}
	
	
	private function setupQueryParameters($task, $params) {
		$tmpArray = explode('/', $params);
		$data = array();

		foreach($tmpArray as $d) {
			if (isset($d) && $d != '') {
				$data[] = $d;
			}
		}
		
		unset($tmpArray);
		
		if (!method_exists($this, $task) && $task != '') {
			array_unshift($data, $task);
		}
		
		return $data;
	}
	
	
	/** 
	 * Is responsible for taking a method passed and ensuring that it is valid for the current request. You can't
	 * 1. Pass a method that starts with "on_"
	 * 2. Pass a method that's in the restrictedMethods array
	 */
	private function setupRequestTask($method) {
		if (method_exists($this, $method) && (strpos($method, 'on_') !== 0) && (!in_array($method, $this->restrictedMethods))) {
			return $method;			
		} else if (is_object($this->c) && method_exists($this, $this->c->getCollectionHandle())) {
			return $this->c->getCollectionHandle();
		} else if (method_exists($this, 'view')) {
			return 'view';
		}
	}
	
	/** 
	 * Based on the current request, the Controller object is loaded with the parameters and task requested
	 * The requested method is then run on the active controller (if that method exists)
	 * @return void
	 */	
	public function setupAndRun() {
		$req = Request::get();
		$data = $this->setupQueryParameters($req->getRequestTask(), $req->getRequestTaskParameters());
		$method = $this->setupRequestTask($req->getRequestTask());

		if ($method) {
			$this->task = $method;
		}
		if (method_exists($this, 'on_start')) {
			call_user_func_array(array($this, 'on_start'), array($method));
		}
		if ($method) {
			$this->runTask($method, $data);
		}
		
		if (method_exists($this, 'on_before_render')) {
			call_user_func_array(array($this, 'on_before_render'), array($method));
		}
	}
	
	/** 
	 * Runs a task in the active controller if it exists.
	 * @return void
	 */
	public function runTask($method, $params) {
		if (method_exists($this, $method)) {
			$ret = call_user_func_array(array($this, $method), $params);
		}
		return $ret;
	}

	private function isCallable($method) {
		if (in_array($method, $this->restrictedMethods)) {
			return false;
		}
		
		if (method_exists($this, $method)) {
			return true;
		}
	}
	
	/**
	 * @access private
	 */
	public function setupRestrictedMethods() {
		$methods = get_class_methods('Controller');
		$this->restrictedMethods = $methods;
	}
	
	/** 
	 * Returns true if the current request is a POST requested
	 * @return bool
	 */
	public function isPost() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	/** 
	 * If no arguments are passed, returns the post array. If a key is passed, it returns the value as it exists in the post array.
	 * @param string $key
	 * @return string $value
	 */
	public function post($key = null) {
		if ($key == null) {
			return $_POST;
		}
		
		if (isset($_POST[$key])) {
			if (is_string($_POST[$key])) {
				return trim($_POST[$key]);
			} else {
				return $_POST[$key];
			}
		} else {
			return null;
		}
	}
	
	/** 
	 * Sets a variable to be passed through from the controller to the view
	 * @param string $key
	 * @param string $val
	 * @return void
	 */
	public function set($key, $val) {
		$this->sets[$key] = $val;
	}
	
	/** 
	 * Returns the value of a previous set()
	 * @param string $key
	 * @return string $value
	 */
	public function get($key) {
		return $this->sets[$key];
	}
	
	/** 
	 * Adds an item to the header. This item will then be automatically printed out in the <head> section of the page
	 * @param string $item
	 * @return void
	 */
	public function addHeaderItem($item) {
		$this->headerItems[] = $item;
	}

	/** 
	 * Redirects to a given URL
	 * @param string $location
	 * @param string $task
	 * @param string $params
	 * @return void
	 */
	public function redirect() {
		$args = func_get_args();
		$url = call_user_func_array(array('View', 'url'), $args);
		if ($url == '') {
			$url = BASE_URL . DIR_REL;
		}
		header("Location: " . $url);
		exit;
	}
	
	/** 
	 * Renders a view with the current controller as its controller
	 * @param string $view
	 * @return void
	 */
	public function render($view) {
		$v = View::getInstance();
		$this->renderOverride = $view;
		$v->setCollectionObject($this->getCollectionObject());
		$v->setController($this);
		if (method_exists($this, 'on_before_render')) {
			call_user_func_array(array($this, 'on_before_render'), array($method));
		}
		$v->render($view);
	}
	
	/**
	 * Sets the current controller's page object
	 * @param Page $c
	 * @return void
	 */
	public function setCollectionObject($c) {$this->c = $c;}
	
	/** 
	 * Gets the current controller's page object.
	 * @return Page
	 */
	public function getCollectionObject() {return $this->c;}
	
	/** 
	 * Gets the current view for the controller (typically the page's handle)
	 * @return string $view
	 */
	public function getView() {return $this->c->getCollectionHandle();}
	
	/** 
	 * Gets the task requested of the controller
	 * @return string
	 */
	public function getTask() {return $this->task;}
	
	/** 
	 * Gets the array of items that have been set using set()
	 * @return array
	 */
	public function getSets() { return $this->sets; }
	
	/** 
	 * Gets an array of helper objects that have been set using the $helpers array
	 * @return array
	 */
	public function getHelperObjects() { return $this->helperObjects; }
	
	/** 
	 * Outputs a list of items set by the addHeaderItem() function
	 * @return void
	 */
	public function outputHeaderItems() {
		foreach($this->headerItems as $hi) {
			print $hi;
		}
	}

}

?>