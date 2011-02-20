<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

class InstallController extends Controller {

	public $helpers = array('form', 'html');
	
	protected function installDB() {
		
		$installDirectory = DIR_BASE_CORE . '/config';
		$file = $installDirectory . '/db.xml';
		if (!file_exists($file)) {
			throw new Exception('Unable to locate database import file.');
		}
		
		$db = Loader::db();
		$err = Package::installDB($file);		
	}
	
	public function configure() {
		
		try {

			$val = Loader::helper('validation/form');
			$val->setData($this->post());
			$val->addRequired("BASE_URL", 'Base URL is invalid');
			$val->addRequired("SITE", 'Please specify your site\'s name');
			$val->addRequiredEmail("uEmail", 'Please specify a valid email address');
			$val->addRequired("DB_DATABASE", 'You must specify a valid database name');
			$val->addRequired("DB_SERVER", 'You must specify a valid database server');
			
			$e = Loader::helper('validation/error');
			
			if (!is_writable(DIR_BASE . '/config')) {
				$e->add('Your configuration directory web/config/ does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_UPLOADED)) {
				$e->add('Your files directory ' . REL_DIR_FILES_UPLOADED . ' does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_UPLOADED_THUMBNAILS)) {
				$e->add('Your files directory ' . REL_DIR_FILES_UPLOADED_THUMBNAILS . ' does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_UPLOADED_ONSTATES)) {
				$e->add('Your files directory ' . REL_DIR_FILES_UPLOADED_ONSTATES . ' does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_TRASH)) {
				$e->add('Your files directory files/trash does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_CACHE)) {
				$e->add('Your files directory files/cache does not appear to be writable by the web server.');
			}

			if (!is_writable(DIR_FILES_AVATARS)) {
				$e->add('Your files directory files/avatars does not appear to be writable by the web server.');
			}
			
			// attempt to connect to the database
			$db = Loader::db( $_POST['DB_SERVER'], $_POST['DB_USERNAME'], $_POST['DB_PASSWORD'], $_POST['DB_DATABASE']);			
			
			if ($_POST['DB_SERVER'] && $_POST['DB_DATABASE']) {
				if (!$db) {
					$e->add('Unable to connect to a database using those credentials.');
				} else {
					
					$num = $db->GetCol("show tables");
					if (count($num) > 0) {
						$e->add('There are already ' . count($num) . ' tables in this database. Concrete must be installed in an empty database.');
					}
				}
			}

			if ($val->test() && (!$e->has())) {
				
				$this->installDB();

				$vh = Loader::helper('validation/identifier');
				
				// insert admin user into the user table
				$salt = ( defined('MANUAL_PASSWORD_SALT') ) ? MANUAL_PASSWORD_SALT : $vh->getString(64);
				$uPassword = rand(100000, 999999);
				$uEmail = $_POST['uEmail'];
				$uPasswordEncrypted = User::encryptPassword($uPassword, $salt);
				UserInfo::addSuperUser($uPasswordEncrypted, $uEmail);
				if (defined('PERMISSIONS_MODEL') && PERMISSIONS_MODEL != 'simple') {
					$setPermissionsModel = PERMISSIONS_MODEL;
				}
				
				if (file_exists(DIR_BASE . '/config')) {
	
					$fp = @fopen(DIR_BASE . '/config/site.php', 'w+');
					if ($fp) {

						Loader::model('single_page');
						Loader::model('dashboard/homepage');
						Loader::model('collection_types');
						Loader::model('user_attributes');
						Loader::model('collection_attributes');
						Loader::model("job");
						Loader::model("groups");
						
						// Add the home page to the system
						$home = Page::addHomePage();

						// create the groups our site users
						// have to add these in the right order so their IDs get set
						// starting at 1 w/autoincrement
						Group::add("Guest", "All unregistered visitors to your site.");
						Group::add("Registered Users", "All registered user accounts are in this group.");
						Group::add("Administrators", "");
						
						// Now the default site!
						// Add our right nav page type
						$data = array();
						$data['ctHandle'] = 'right_sidebar';
						$data['ctName'] = 'Right Sidebar';
						$data['ctIcon'] = 'template3.png'; 
						$data['uID'] = USER_SUPER_ID;
						$rst = CollectionType::add($data);
						
						// Add our left nav page type
						$data = array();
						$data['ctHandle'] = 'left_sidebar';
						$data['ctName'] = 'Left Sidebar';
						$data['ctIcon'] = 'template1.png';
						$data['uID'] = USER_SUPER_ID;
						$dt = CollectionType::add($data);	
						
						// Add our no side nav page type
						$data = array();
						$data['ctHandle'] = 'full';
						$data['ctName'] = 'Full Width';
						$data['ctIcon'] = 'main.png'; 
						$data['uID'] = USER_SUPER_ID;
						$nst = CollectionType::add($data);		
						
						// update the home page to set page type to the right sidebar one
						$data = array();
						$data['ctID'] = $rst->getCollectionTypeID();
						$home->update($data);
						
						// install everything into db

						// Add default page attributes
						$cab1 = CollectionAttributeKey::add('meta_title', 'Meta Title', true, null, 'TEXT');
						$cab2 = CollectionAttributeKey::add('meta_description', 'Meta Description', true, null, 'TEXT');
						$cab3 = CollectionAttributeKey::add('meta_keywords', 'Meta Keywords', true, null, 'TEXT');
						$cab4 = CollectionAttributeKey::add('exclude_nav', 'Exclude from Nav', true, null, 'BOOLEAN');
						
						$dt->assignCollectionAttribute($cab1);
						$dt->assignCollectionAttribute($cab2);
						$dt->assignCollectionAttribute($cab3);
						$dt->assignCollectionAttribute($cab4);

						$rst->assignCollectionAttribute($cab1);
						$rst->assignCollectionAttribute($cab2);
						$rst->assignCollectionAttribute($cab3);
						$rst->assignCollectionAttribute($cab4);

						$nst->assignCollectionAttribute($cab1);
						$nst->assignCollectionAttribute($cab2);
						$nst->assignCollectionAttribute($cab3);
						$nst->assignCollectionAttribute($cab4);
						
						// Add default user attributes
						UserAttributeKey::add('date_of_birth', 'Date of Birth', 0, 1, 1, 0, null, "TEXT");
						UserAttributeKey::add('gender', 'Gender', 0, 1, 1, 0, "Male\nFemale", "RADIO");
						
						// Add our core views
						SinglePage::add('/login');
						SinglePage::add('/register');
				
						// Install our blocks
						BlockType::installBlockType('library_file');
						BlockType::installBlockType('content');
						BlockType::installBlockType('autonav');
						BlockType::installBlockType('external_form');
						BlockType::installBlockType('form');
						BlockType::installBlockType('page_list');
						BlockType::installBlockType('file');
						BlockType::installBlockType('image');			
						BlockType::installBlockType('flash_content');			
						BlockType::installBlockType('guestbook');			
						BlockType::installBlockType('slideshow');			
						BlockType::installBlockType('search');			
						BlockType::installBlockType('google_map');			
						BlockType::installBlockType('video');			
						BlockType::installBlockType('rss_displayer');			
						BlockType::installBlockType('youtube');			
						BlockType::installBlockType('survey');			
						
						// Setup the default Theme
						$pl = PageTheme::add('default');
						$pl->applyToSite();
						
						// add the greensalad theme 						
						$salad = PageTheme::add('greensalad');
						
						// Add our dashboard items and their navs
						$d0 = SinglePage::add('/dashboard');
				
						$d1 = SinglePage::add('/dashboard/sitemap');
						$d2 = SinglePage::add('/dashboard/mediabrowser');
						$d3 = SinglePage::add('/dashboard/form_results');
						$d4 = SinglePage::add('/dashboard/users');
						$d5 = SinglePage::add('/dashboard/users/attributes');
						$d6 = SinglePage::add('/dashboard/groups');
						$d7 = SinglePage::add('/dashboard/collection_types');
						$d8 = SinglePage::add('/dashboard/collection_types/attributes');
						$d9 = SinglePage::add('/dashboard/themes');
						$d9a = SinglePage::add('/dashboard/themes/add');
						$d9b = SinglePage::add('/dashboard/themes/inspect');
						$d10 = SinglePage::add('/dashboard/install');
						$d11 = SinglePage::add('/dashboard/jobs');
						$d12 = SinglePage::add('/dashboard/logs');
						$d13 = SinglePage::add('/dashboard/settings');
						
						// add home page
						$dl1 = SinglePage::add('/download_file');
						$dl1->update(array('cName' => 'Download File'));
						
						$d1->update(array('cName'=>'Sitemap', 'cDescription'=>'Whole world at a glance.'));
						$d2->update(array('cName'=>'File Manager', 'cDescription'=>'All documents and images.'));
						$d3->update(array('cName'=>'Form Results', 'cDescription'=>'Get submission data.'));
						$d4->update(array('cName'=>'Users', 'cDescription'=>'Add and manage people.'));
						$d5->update(array('cName'=>'User Attributes'));
						$d6->update(array('cName'=>'Groups', 'cDescription'=>'Permission levels for users.'));
						$d7->update(array('cName'=>'Page Types', 'cDescription'=>'What goes in your site.'));
						$d8->update(array('cName'=>'Custom Page Attributes', 'cDescription'=>'Setup Special Metadata for Pages'));
						$d9->update(array('cName'=>'Themes', 'cDescription'=>'Reskin your site.'));		
						$d10->update(array('cName'=>'Add Functionality', 'cDescription'=>'Install blocks to extend your site.'));
						$d11->update(array('cName'=>'Maintenance', 'cDescription'=>'Run common cleanup tasks.'));
						$d12->update(array('cName'=>'Logging', 'cDescription'=>'Keep tabs on your site.'));
						$d13->update(array('cName'=>'Sitewide Settings', 'cDescription'=>'Secure and setup your site.'));
				
						// dashboard homepage
						$dh2 = new DashboardHomepageView();
						$dh2->add('activity', 'Site Activity');
						$dh2->add('reports', 'Statistics');
						$dh2->add('help', 'Help');
						$dh2->add('news', 'Latest News');
						$dh2->add('notes', 'Notes');
						
						// setup header autonav block we're going to add
						$data = array();
						$data['orderBy'] = 'display_desc';
						$data['displayPages'] = 'top';
						$data['displaySubPages'] = 'none';		
						$data['uID'] = USER_SUPER_ID;
						$autonav = BlockType::getByHandle('autonav');

						// add autonav block to left sidebar page type
						$detailTemplate = $dt->getMasterTemplate();
						$b1 = $detailTemplate->addBlock($autonav, 'Header Nav', $data);
						$b1->setCustomTemplate('header_menu.php');
						
						// Add an autonav block to right sidebar page type
						$rightNavTemplate = $rst->getMasterTemplate();
						$b2 = $rightNavTemplate->addBlock($autonav, 'Header Nav', $data);
						$b2->setCustomTemplate('header_menu.php');

						// Add an autonav block to full width header
						$fullWidthTemplate = $nst->getMasterTemplate();
						$b3 = $fullWidthTemplate->addBlock($autonav, 'Header Nav', $data);
						$b3->setCustomTemplate('header_menu.php');
						
						// Add an autonav block to Every detail page sidebar
						$data = array();
						$data['orderBy'] = 'display_asc';
						$data['displayPages'] = 'second_level';
						$data['displaySubPages'] = 'relevant';
						$data['displaySubPageLevels'] = 'enough_plus1';
						$data['uID'] = USER_SUPER_ID;
						$b2 = $detailTemplate->addBlock($autonav, 'Sidebar', $data);
						
						// Add an autonav block to Every detail page sidebar
						$b2 = $rightNavTemplate->addBlock($autonav, 'Sidebar', $data);
						
						// alias header nav to the home page
						$b1->alias($home);
						
						// Add Some Imagery
						$bt = BlockType::getByHandle('library_file');
						$data = array();
						$data['file'] = $pl->getThemeDirectory() . '/images/inneroptics_dot_net_aspens.jpg';
						$data['name'] = "aspens.jpg";
						$data['uID'] = USER_SUPER_ID;
						$image1 = $bt->add($data);

						$bt2 = BlockType::getByHandle('library_file');
						$data = array();
						$data['file'] = $pl->getThemeDirectory() . '/images/inneroptics_dot_net_canyonlands.jpg';
						$data['name'] = "canyonlands.jpg";
						$data['uID'] = USER_SUPER_ID;
						$image2 = $bt2->add($data);

						$bt3 = BlockType::getByHandle('library_file');
						$data = array();
						$data['file'] = $pl->getThemeDirectory() . '/images/inneroptics_dot_net_new_zealand_sheep.jpg';
						$data['name'] = "sheep.jpg";
						$data['uID'] = USER_SUPER_ID;
						$image3 = $bt3->add($data);

						$bt4 = BlockType::getByHandle('library_file');
						$data = array();
						$data['file'] = $pl->getThemeDirectory() . '/images/inneroptics_dot_net_starfish.jpg';
						$data['name'] = "starfish.jpg";
						$data['uID'] = USER_SUPER_ID;
						$image4 = $bt4->add($data);
						
						// Assign this imagery to the various pages.
						$btImage = BlockType::getByHandle('image');
						$data = array();
						$data['fID'] = $image1->getBlockID();
						$data['altText'] = 'Home Header Image';
						$data['uID'] = USER_SUPER_ID;
						$home->addBlock($btImage, 'Header', $data);

						// Assign imagery to left sidebar page
						$data['fID'] = $image2->getBlockID();
						$data['altText'] = 'Left Sidebar Page Type Image';
						$b1 = $detailTemplate->addBlock($btImage, 'Header', $data);

						// Assign imagery to right sidebar page
						$data['fID'] = $image3->getBlockID();
						$data['altText'] = 'Right Sidebar Page Type Image';
						$b2 = $rightNavTemplate->addBlock($btImage, 'Header', $data);
						
						// Assign imagery to full width page
						$data['fID'] = $image3->getBlockID();
						$data['altText'] = 'Full Width Page Type Image';
						$b3 = $fullWidthTemplate->addBlock($btImage, 'Header', $data);

						// add two subpages
						$data = array();
						$data['uID'] = USER_SUPER_ID;
						$data['name'] = 'About';
						$aboutPage = $home->add($dt, $data);
						$data['name'] = 'Examples';
						$examplesPage = $home->add($dt, $data);
						$data['name'] = 'Contact';
						$contactPage = $home->add($dt, $data);
						
						// Add Content to Home page
						$bt = BlockType::getByHandle('content');
						$data = array();
						$data['uID'] = USER_SUPER_ID;
						$data['content'] = '<h1>Welcome to Concrete.</h1><p>You are currently viewing the front page of your website. This is an example of a content block - rich text that can be added through a WYSIWYG editor.</p><p>Get started by putting the page in edit mode, adding sub-pages, or checking out the <a href="' . BASE_URL . '/index.php/dashboard/">Dashboard</a>.</p>
						<h3>Examples of Blocks</h3>
						Listed below are some of the more interesting blocks that Concrete5 ships with, installed and ready to use. Click through to explore the blocks on their own page.
						</p>
						<p>These pages are actually listed using the <b>page list</b> block. To check it out, put the page in edit mode, mouse over the list of pages below, click, and then select edit.</p>';

						$home->addBlock($bt, "Main", $data);

						// add page list block below the examples intro text
						$pageListBT = BlockType::getByHandle('page_list');
						$plData['cParentID'] = $examplesPage->getCollectionID();
						$plData['orderBy'] = 'display_asc';
						$plData['num'] = 99;
						$plData['cThis'] = 1;
						$home->addBlock($pageListBT, "Main", $plData);

						
						$data['content']  = '<h3>Learn More</h3><p>
						There are many more blocks installed with Concrete5 than these. Start editing to check them out!</p>';
						$home->addBlock($bt, "Main", $data);


						$data['content'] = '<h2>Sidebar</h2><p>Everything about Concrete is completely customizable through the CMS. This is a separate area from the main content on the homepage.</p><p>We\'ve named this area the "sidebar" (pretty imaginative, eh?)</p>';
						$home->addBlock($bt, "Sidebar", $data);
						
				
						$data['content']  = '<h1 class="noTopSpace">Sed ut perspiciatis unde omnis iste natus error (H1)</h1><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>';
						$data['content'] .= '<h2 class="noTopSpace">Sed ut perspiciatis unde omnis iste natus error (H2)</h2><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>';
						$data['content'] .= '<h3 class="noTopSpace">Sed ut perspiciatis unde omnis iste natus error (H3)</h3><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>';
						$data['content'] .= '<h4 class="noTopSpace">Sed ut perspiciatis unde omnis iste natus error (H4)</h4><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>';
						$data['content'] .= '<h5 class="noTopSpace">Sed ut perspiciatis unde omnis iste natus error (H5)</h5><p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>';
						$aboutPage->addBlock($bt, "Main", $data);
						
						$data['content']  = '<h1 class="noTopSpace">Examples of Blocks</h1><p>
						Listed below are some of the more interesting blocks that Concrete5 ships with, installed and ready to use. Click through to explore the blocks on their own page.
						</p>
						<p>These pages are actually listed using the <b>page list</b> block. To check it out, put the page in edit mode, mouse over the list of pages below, click, and then select edit.</p>';
						$examplesPage->addBlock($bt, "Main", $data);
						
						// add page list block below the examples intro text
						$pageListBT = BlockType::getByHandle('page_list');
						$plData['cParentID'] = $examplesPage->getCollectionID();
						$plData['orderBy'] = 'display_asc';
						$plData['num'] = 99;
						$plData['cThis'] = 1;
						$examplesPage->addBlock($pageListBT, "Main", $plData);
						
						$data['content']  = '<h3>Learn More</h3><p>
						There are many more blocks installed with Concrete5 than these. Start editing to check them out!</p>';
						$examplesPage->addBlock($bt, "Main", $data);
						
						// add javascript slideshow page beneath examples
						$data['name'] = 'Image Slideshow';
						$example0Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Image Slideshow</h1><p>
						Check out the image block above. It\'s actually multiple images setup as a JavaScript slideshow.
						</p>';
						$example0Page->addBlock($bt, "Main", $data);
						
						// remove image block from header
						$blocks = $example0Page->getBlocks('Header');
						if (is_object($blocks[0])) {
							$blocks[0]->deleteBlock();
						}
						
						$jsBT = BlockType::getByHandle('slideshow');
						$jsData['playback'] = 'ORDER';
						$jsData['imgBIDs'] = array(
							$image1->getBlockID(),
							$image2->getBlockID(),
							$image3->getBlockID(),
							$image4->getBlockID()
						);
						
						$fimage1 = $image1->getInstance();
						$fimage2 = $image2->getInstance();
						$fimage3 = $image3->getInstance();
						$fimage4 = $image4->getInstance();
						
						$jsData['fileNames'] = array(
							$fimage1->getFilename(),
							$fimage2->getFilename(),
							$fimage3->getFilename(),
							$fimage4->getFilename()
						);

						$jsData['origfileNames'] = array(
							$fimage1->getOriginalFilename(),
							$fimage2->getOriginalFilename(),
							$fimage3->getOriginalFilename(),
							$fimage4->getOriginalFilename()
						);

						$jsData['thumbPaths'] = array(
							REL_DIR_FILES_UPLOADED_THUMBNAILS . '/' . $fimage1->getFilename(),
							REL_DIR_FILES_UPLOADED_THUMBNAILS . '/' . $fimage2->getFilename(),
							REL_DIR_FILES_UPLOADED_THUMBNAILS . '/' . $fimage3->getFilename(),
							REL_DIR_FILES_UPLOADED_THUMBNAILS . '/' . $fimage4->getFilename()
						);

						$jsData['duration'] = array(3, 3, 3, 3);
						$jsData['imgHeight'] = array(192, 192, 192, 192);
						$jsData['fadeDuration'] = array(1, 1, 1, 1);
						$example0Page->addBlock($jsBT, "Header", $jsData);


						// add sitemap page beneath examples page
						$data['name'] = 'Sitemap';
						$example1Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Sitemap Example</h1><p>
						Below we\'re using the autonav block to build a nested sitemap of the whole site. When pages are added to the site this will automatically update.
						</p>';
						$example1Page->addBlock($bt, "Main", $data);
						
						// add sitemap block to example 1 page
						$autonavBT = BlockType::getByHandle('autonav');
						$autonavData['displayPages'] = 'top';
						$autonavData['orderBy'] = 'display_asc';
						$autonavData['displaySubPages'] = 'all';
						$autonavData['displaySubPageLevels'] = 'all';
						$example1Page->addBlock($autonavBT, "Main", $autonavData);
						
						// add youtube video
						$data['name'] = 'YouTube Video';
						$data['handle'] = 'youtube';
						$example2Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Youtube Video Example</h1>';
						$example2Page->addBlock($bt, "Main", $data);
						
						// add youtube block to example 2 page
						$ytBT = BlockType::getByHandle('youtube');
						$ytData['videoURL'] = 'http://youtube.com/watch?v=CewglxElBK0';
						$ytData['title'] = 'Step Brothers Trailer';
						$example2Page->addBlock($ytBT, "Main", $ytData);

						// add search page
						$data['name'] = 'Search';
						$example3Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Search Block Example</h1>';
						$example3Page->addBlock($bt, "Main", $data);
						
						// add search block to example 3 page
						$searchBT = BlockType::getByHandle('search');
						$searchData['title'] = 'Search Your Site';
						$searchData['buttonText'] = 'Search';
						$searchData['resultsURL'] = '/examples/search';
						$example3Page->addBlock($searchBT, "Main", $searchData);

						// add form block page
						$data['name'] = 'Interactive Form';
						$data['cHandle'] = 'form';
						$example4Page = $examplesPage->add($dt, $data);
						$nh = Loader::helper('navigation');
						$formURL = $nh->getLinkToCollection($contactPage);
						$data['content']  = '<h1 class="noTopSpace">Interactive Form Block</h1><p>An example of our interactive form block can be found on the <a href="' . $formURL . '">Contact Page</a>.';
						$example4Page->addBlock($bt, "Main", $data);					

						// add survey page
						$data['name'] = 'Survey';
						$example5Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Survey Example</h1>';
						$example5Page->addBlock($bt, "Main", $data);
						
						// add survey to example 3 page
						$surveyBT = BlockType::getByHandle('survey');
						$surveyData['title'] = 'Search Your Site';
						$surveyData['question'] = 'What is your favorite color?';
						$surveyData['pollOption'] = array("Red", "White", "Green", "Blue", "Yellow", "Black", "Purple", "Orange");
						$example5Page->addBlock($surveyBT, "Main", $surveyData);


						// add guestbook page
						$data['name'] = 'Guestbook/Comments';
						$data['cHandle'] = 'guestbook';
						$example6Page = $examplesPage->add($dt, $data);
						$data['content']  = '<h1 class="noTopSpace">Guestbook/Comments Example</h1><p>Using Concrete5 you can add blog-style comments to any page easily, using the guestbook block below.</p>';
						$example6Page->addBlock($bt, "Main", $data);
						
						// add guestbook to example page
						$gbBT = BlockType::getByHandle('guestbook');
						$gbData['requireApproval'] = 0;
						$gbData['title'] = 'Comments';
						$gbData['displayGuestBookForm'] = 1;
						$example6Page->addBlock($gbBT, "Main", $gbData);
						
						// Add a Contact Form to the Contact page
						$bt = BlockType::getByHandle('form');	
						$data['qsID']=1;
						$data['surveyName'] = 'Contact Form';	
						$data['notifyMeOnSubmission'] = 1;
						$data['recipientEmail'] = $uEmail;
						$data['questions'][] = array( 'qsID'=>$data['qsID'], 'question'=>'Name', 'inputType'=>'field', 'options'=>'', 'position'=>1 );
						$data['questions'][] = array( 'qsID'=>$data['qsID'], 'question'=>'Email', 'inputType'=>'field', 'options'=>'', 'position'=>2 );
						$data['questions'][] = array( 'qsID'=>$data['qsID'], 'question'=>'Comments/Questions?', 'inputType'=>'text', 'options'=>'', 'position'=>3 );
						$contactPage->addBlock($bt, "Main", $data);	
						
						/* set it so anyone can read the site */
						$args = array();
						$args['cInheritPermissionsFrom'] = 'OVERRIDE';
						$args['cOverrideTemplatePermissions'] = 1;
						$args['collectionRead'][] = 'gID:' . GUEST_GROUP_ID;
						$args['collectionAdmin'][] = 'gID:' . ADMIN_GROUP_ID;
						$args['collectionRead'][] = 'gID:' . ADMIN_GROUP_ID;
						$args['collectionApprove'][] = 'gID:' . ADMIN_GROUP_ID;
						$args['collectionReadVersions'][] = 'gID:' . ADMIN_GROUP_ID;
						$args['collectionWrite'][] = 'gID:' . ADMIN_GROUP_ID;
						$args['collectionDelete'][] = 'gID:' . ADMIN_GROUP_ID;
						
						$home = Page::getByID(1, "RECENT");
						$home->updatePermissions($args);
					
						// Install & Run Jobs  
						Job::installByHandle('index_search');
						Job::installByHandle('generate_sitemap');
						// NOTE: This is too memory intensive to run during initial install. Let's not run it and just give nicer feedback
						//Job::runAllJobs();

						// write the config file
						$configuration = "<?php \n";
						$configuration .= "define('DB_SERVER', '" . addslashes($_POST['DB_SERVER']) . "');\n";
						$configuration .= "define('DB_USERNAME', '" . addslashes($_POST['DB_USERNAME']) . "');\n";
						$configuration .= "define('DB_PASSWORD', '" . addslashes($_POST['DB_PASSWORD']) . "');\n";
						$configuration .= "define('DB_DATABASE', '" . addslashes($_POST['DB_DATABASE']) . "');\n";
						$configuration .= "define('BASE_URL', '" . addslashes($_POST['BASE_URL']) . "');\n";
						$configuration .= "define('DIR_REL', '" . addslashes($_POST['DIR_REL']) . "');\n";
						if (isset($setPermissionsModel)) {
							$configuration .= "define('PERMISSIONS_MODEL', '" . addslashes($setPermissionsModel) . "');\n";
						}
						$configuration .= "define('PASSWORD_SALT', '{$salt}');\n";
						$configuration .= "?" . ">";
						$res = fwrite($fp, $configuration);
						fclose($fp);
						
						// save some options into the database
						Config::save('SITE', $_POST['SITE']);
						// add the current app version as our site's app version
						Config::save('SITE_APP_VERSION', APP_VERSION);
						Config::save('SITE_DEBUG_LEVEL', DEBUG_DISPLAY_ERRORS);
						Config::save('ENABLE_LOG_EMAILS', 1);
						Config::save('ENABLE_LOG_ERRORS', 1);
						
						// login 
						define('PASSWORD_SALT', $salt);
						$u = new User(USER_SUPER, $uPassword);						
						$this->set('message', 'Congratulations. Concrete has been installed. You have been logged in as <b>' . USER_SUPER . '</b> with the password <b>' . $uPassword . '</b>.<br/><br/>If you wish to change this password, you may do so from the users area of the dashboard.');
						
						
					} else {
						throw new Exception('Unable to open config/site.php for writing.');
					}
				
	
				} else {
					throw new Exception('Unable to locate config directory.');
				}
			
			} else {
				if ($e->has()) {
					$this->set('error', $e);
				} else {
					$this->set('error', $val->getError());
				}
			}
			
		} catch (Exception $e) {
			$this->set('error', $e);
		}
	}

	
}

?>