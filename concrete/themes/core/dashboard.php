<?php   defined('C5_EXECUTE') or die(_("Access Denied.")); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/2000/REC-xhtml1-20000126/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php  
$v = View::getInstance();
$v->disableEditing();
require(DIR_FILES_ELEMENTS_CORE . '/header_required.php'); ?>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_dashboard.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_menus.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_forms.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_calendar.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_dialog.css";</style>
<style type="text/css">@import "<?php  echo ASSETS_URL_CSS?>/ccm_asset_library.css";</style>

<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.form.2.0.2.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.ui.1.5.2.no_datepicker.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/jquery.ui.1.6b.datepicker.js"></script>
<?php   if (LANGUAGE != 'en') { ?>
	<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/i18n/ui.datepicker-<?php  echo LANGUAGE?>.js"></script>
<?php   } ?>

<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/ccm.dialog.js"></script>
<script type="text/javascript" src="<?php  echo ASSETS_URL_JAVASCRIPT?>/ccm.base.js"></script>

<script type="text/javascript">
$(function() {
	$("div.message").show('highlight', {
		color: '#ffffff'
	});
});
</script>
</head>
<body>

<div id="ccm-dashboard-page">

<div id="ccm-dashboard-header">
<a href="<?php  echo $this->url('/dashboard/')?>"><img src="<?php  echo ASSETS_URL_IMAGES?>/dashboard/logo.png" height="45" width="48" alt="Concrete5" /></a>
</div>


<div id="ccm-system-nav-wrapper1">
<div id="ccm-system-nav-wrapper2">
<ul id="ccm-system-nav">
<li><a id="ccm-nav-return" href="<?php  echo $this->url('/')?>"><?php  echo t('Return to Website')?></a></li>
<li><a id="ccm-nav-dashboard-help" href="<?php  echo MENU_HELP_URL?>"><?php  echo t('Help')?></a></li>
<li class="ccm-last"><a id="ccm-nav-logout" href="<?php  echo $this->url('/login/', 'logout')?>"><?php  echo t('Sign Out')?></a></li>
</ul>
</div>
</div>

<?php   
Loader::block('autonav');
$nh = Loader::helper('navigation');
$pc = Page::getByPath("/dashboard");
$nav = AutonavBlockController::getChildPages($pc);
?>

<div id="ccm-dashboard-nav">
<ul>
<?php  
foreach($nav as $n2) { 
	$cp = new Permissions($n2);
	if ($cp->canRead()) { 
		if ($c->getCollectionPath() == $n2->getCollectionPath() || (strpos($c->getCollectionPath(), $n2->getCollectionPath()) == 0) && strpos($c->getCollectionPath(), $n2->getCollectionPath()) !== false) {
			$isActive = true;
		} else {
			$isActive = false;
		}
?>
	<li <?php   if ($isActive) { ?> class="ccm-nav-active" <?php   } ?>><a href="<?php  echo $nh->getLinkToCollection($n2, false, true)?>"><?php  echo $n2->getCollectionName()?> <span><?php  echo $n2->getCollectionDescription()?></span></a></li>
<?php   }

}?>
</ul>
</div>

<?php   if (isset($subnav)) { ?>

<div id="ccm-dashboard-subnav">
<ul><?php   foreach($subnav as $item) { ?><li <?php   if (isset($item[2]) && $item[2] == true) { ?> class="nav-selected" <?php   } ?>><a href="<?php  echo $item[0]?>"><?php  echo $item[1]?></a></li><?php   } ?></ul>
<br/><div class="ccm-spacer">&nbsp;</div>
</div>
<?php   } ?>

<?php  
	if (isset($latest_version)){ 
		print Loader::element('dashboard/notification_update', array('latest_version' => $latest_version));
	}
?>

<div id="ccm-dashboard-content">

	<div style="margin:0px; padding:0px; width:100%; ">
	<?php   if (isset($error)) { ?>
		<?php   
		if ($error instanceof Exception) {
			$_error[] = $error->getMessage();
		} else if ($error instanceof ValidationErrorHelper) { 
			$_error = $error->getList();
		} else {
			$_error = $error;
		}
			?>
			<div class="message error">
			<strong><?php  echo t('The following errors occurred when attempting to process your request:')?></strong>
			<ul>
			<?php   foreach($_error as $e) { ?><li><?php  echo $e?></li><?php   } ?>
			</ul>
			</div>
		<?php   
	}
	
	if (isset($message)) { ?>
		<div class="message success"><?php  echo $message?></div>
	<?php   } ?>
	
	<?php   print $innerContent; ?>
	</div>
	
	<div class="ccm-spacer">&nbsp;</div>

	</div>

</div>

</body>
</html>