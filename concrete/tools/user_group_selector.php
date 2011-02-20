<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
$displayGroups = true;
$displayUsers = true;

if ($_REQUEST['mode'] == 'users') {
	$displayGroups = false;
} else if ($_REQUEST['mode'] == 'groups') {
	$displayUsers = false;
}

$c1 = Page::getByPath('/dashboard/users');
$cp1 = new Permissions($c1);
$c2 = Page::getByPath('/dashboard/groups');
$cp2 = new Permissions($c2);
if ((!$cp1->canRead()) && (!$cp2->canRead())) {
	die(_("Access Denied."));
}


?>

<script type="text/javascript">
var ccm_areaActiveTab = "ccm-select-group";

$("#ccm-ug-tabs a").click(function() {
	$("li.ccm-nav-active").removeClass('ccm-nav-active');
	$("#" + ccm_areaActiveTab + "-tab").hide();
	ccm_areaActiveTab = $(this).attr('id');
	$(this).parent().addClass("ccm-nav-active");
	$("#" + ccm_areaActiveTab + "-tab").show();
});

</script>

<?php  if ($displayGroups && $displayUsers) { ?>

<ul class="ccm-dialog-tabs" id="ccm-ug-tabs">
<li class="ccm-nav-active"><a href="javascript:void(0)" id="ccm-select-group">Groups</a></li>
<li><a href="javascript:void(0)" id="ccm-select-user">Users</a></li>
</ul>

<?php  } ?>

<?php  if ($displayGroups) { ?>

<div id="ccm-select-group-tab">

<h1>Select Group</h1>

<?php  include(DIR_FILES_TOOLS_REQUIRED . '/select_group.php'); ?>

</div>

<?php  } ?>

<?php  if ($displayUsers) { ?>

<div id="ccm-select-user-tab" style="display: none">
<h1>Select User</h1>

<?php  include(DIR_FILES_TOOLS_REQUIRED . '/select_user.php'); ?>

</div>

<?php  } ?>