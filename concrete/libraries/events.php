<?php  

/**
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

/**
 * An events framework for Concrete. System events like "on_user_add" can be hooked into, so that when a user is added to the system, the new UserInfo object is passed to developers' custom functions.
 * Current events include:
 * on_user_add
 * @package Core
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
class Events {
	
	const EVENT_TYPE_PAGETYPE = "page_type";
	const EVENT_TYPE_GLOBAL = "global";
	
	/** 
	 * Returns an instance of the systemwide Events object.
	 */
	public function getInstance() {
		static $instance;
		if (!isset($instance)) {
			$v = __CLASS__;
			$instance = new $v;
		}
		return $instance;
	}		

	private $registeredEvents = array();
	
	
	/** 
	 * When passed an "event" as a string, a user-defined method will be run INSIDE this page's controller
	 * whenever an event takes place. The name/location of this event is not customizable. If you want more
	 * customization, used extend() below.
	 */
	public static function extendPageType($ctHandle, $event = false, $pkgHandle = null, $params = array()) {
		if ($event == false) {
			// then we're registering ALL the page type events for this particular page type
			Events::extendPageType($ctHandle, 'on_page_add', $pkgHandle, $params);
			Events::extendPageType($ctHandle, 'on_page_update', $pkgHandle, $params);
			Events::extendPageType($ctHandle, 'on_page_duplicate', $pkgHandle, $params);
			Events::extendPageType($ctHandle, 'on_page_move', $pkgHandle, $params);
			Events::extendPageType($ctHandle, 'on_page_view', $pkgHandle, $params);
			Events::extendPageType($ctHandle, 'on_page_delete', $pkgHandle, $params);
		} else {
			$ce = Events::getInstance();
			$class = Object::camelcase($ctHandle) . 'PageTypeController';
			$method = $event;
			$filename = Loader::pageTypeControllerPath($ctHandle, $pkgHandle);
			$ce->registeredEvents[$event][] = array(
				Events::EVENT_TYPE_PAGETYPE,
				$class,
				$method,
				$filename,
				$params
			);
		}
	}
	/**
	 * When passed an "event" as a string (e.g. "on_user_add"), a user-defined method can be run whenever this event
	 * takes place.
	 * <code>
	 * Events::extend('on_user_add', 'MySpecialClass', 'createSpecialUserInfo', 'models/my_special_class.php', array('foo' => 'bar'))
	 * </code>
	 * @param string $event
	 * @param string $class
	 * @param string $method
	 * @param string $filename
	 * @param array $params
	 * @return void
	 */
	public static function extend($event, $class, $method, $filename, $params = array()) {
		$ce = Events::getInstance();
		$ce->registeredEvents[$event][] = array(
			Events::EVENT_TYPE_GLOBAL,
			$class,
			$method,
			$filename,
			$params
		);	
	}
	
	/** 
	 * An internal function used by Concrete to "fire" a system-wide event. Any time this happens, events that 
	 * a developer has hooked into will be run.
	 * @param string $event
	 * @return void
	 */
	public static function fire($event) {
		if (ENABLE_APPLICATION_EVENTS == false) {
			return;
		}
		
		// any additional arguments passed to the fire function will get passed FIRST to the method, with the method's own registered
		// params coming at the end. e.g. if I fire Events::fire('on_login', $userObject) it will come in with user object first
		$args = func_get_args();
		if (count($args) > 1) {
			array_shift($args);
		} else {
			$args = false;
		}

		$ce = Events::getInstance();
		$events = $ce->registeredEvents[$event];
		if (is_array($events)) {
			foreach($events as $ev) {
				$type = $ev[0];
				if ($type == Events::EVENT_TYPE_PAGETYPE) {
					// then the first argument in the event fire() method will be the page
					// that this applies to. We check to see if the page type is the right type
					if (is_object($args[0]) && $args[0] instanceof Page) {
						if ($ev[3] != Loader::pageTypeControllerPath($args[0]->getCollectionTypeHandle())) {
							return false;
						}
					}
				}
				if (strpos($ev[3], DIR_BASE) === 0) {	
					// then this means that our path ALREADY has DIR_BASE in it
					require_once($ev[3]);
				} else {
					require_once(DIR_BASE . '/' . $ev[3]);
				}
				$params = (is_array($ev[4])) ? $ev[4] : array();
				
				// now if args has any values we put them FIRST
				$params = array_merge($args, $params);

				if (method_exists($ev[1], $ev[2])) {
					call_user_func_array(array($ev[1], $ev[2]), $params);
				}				
			}
		}
	}
}



	//	$controller = Loader::controller($this);
//		$ret = $controller->runTask('on_page_delete', array($this));
