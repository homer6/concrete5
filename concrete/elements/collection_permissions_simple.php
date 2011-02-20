<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
if ($cp->canAdminPage()) {
$gArray = array();
$gl = new GroupList($c, false, true);
$gArray = $gl->getGroupList();
?>

<div class="ccm-pane-controls">
<form method="post" name="ccmPermissionsForm" action="<?php echo $c->getCollectionAction()?>">
<input type="hidden" name="rel" value="<?php echo $_REQUEST['rel']?>" />

<h1>Page Access</h1>

<div class="ccm-form-area">

<div class="ccm-field">

<h2>Who can view this page?</h2>

<?php 

foreach ($gArray as $g) {
?>

<input type="checkbox" name="readGID[]" value="<?php echo $g->getGroupID()?>" <?php  if ($g->canRead()) { ?> checked <?php  } ?> /> <?php echo $g->getGroupName()?><br/>

<?php  } ?>

</div>

<div class="ccm-field">

<h2>Who can edit this page?</h2>

<?php 

foreach ($gArray as $g) {
?>

<input type="checkbox" name="editGID[]" value="<?php echo $g->getGroupID()?>" <?php  if ($g->canWrite()) { ?> checked <?php  } ?> /> <?php echo $g->getGroupName()?><br/>

<?php  } ?>


</div>
</div>

<div class="ccm-buttons">
<!--	<a href="javascript:void(0)" onclick="ccm_hidePane()" class="ccm-button-left cancel"><span><em class="ccm-button-close">Cancel</em></span></a>//-->
	<a href="javascript:void(0)" onclick="ccm_submit()" class="ccm-button-right accept"><span>Save</span></a>
</div>	
<input type="hidden" name="update_permissions" value="1" class="accept">
<input type="hidden" name="processCollection" value="1">

<script type="text/javascript">
ccm_submit = function() {
	//ccm_showTopbarLoader();
	$('form[name=ccmPermissionsForm]').get(0).submit();
}
</script>

<div class="ccm-spacer">&nbsp;</div>
</form>
</div>
<?php  } ?>