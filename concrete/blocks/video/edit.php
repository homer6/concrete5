<?php  
	defined('C5_EXECUTE') or die(_("Access Denied."));
	$bObj=$controller;
	$al = Loader::helper('concrete/asset_library');
	$bf = null;
	if ($controller->getFileID() > 0) { 
		$bf = $controller->getFileObject();
	}
?>
<h2><?php  echo t("Video File") ?></h2>
<?php  echo $al->file('ccm-b-flv-file', 'fID', t('Choose Video File'), $bf);?>

<?php   include($this->getBlockPath() .'/form_setup_html.php'); ?> 