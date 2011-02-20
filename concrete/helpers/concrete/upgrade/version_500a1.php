<?php 
/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
class ConcreteUpgradeVersion500a1Helper {
	
	public function notes() {
		return 'Make sure your web root contains a jobs/ directory, or upgrading will not go smoothly.';
	}
	
	public function run() {
		// contains all the items that have changed from 5.0.0a1 to the next version
		$db = Loader::db();
		
		// create jobs dashboard page
		Loader::model("job");
		Loader::model('single_page');
		Job::installByHandle('index_search');
		$d11 = SinglePage::add('/dashboard/jobs');
		if (is_object($d11)) {
			$d11->update(array('cName'=>'Maintenance', 'cDescription'=>'Run common cleanup tasks.'));
		}
	}
	
}
		
	