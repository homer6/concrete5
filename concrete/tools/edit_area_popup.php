<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
$c = Page::getByID($_REQUEST['cID']);
$cp = new Permissions($c);
if (!$cp->canWrite()) {
	die(_("Access Denied."));
}

$a = Area::get($c, $_GET['arHandle']);
$ap = new Permissions($a);

$btl = $a->getAddBlockTypes($c, $ap );
$blockTypes = $btl->getBlockTypeList();
$ci = Loader::helper('concrete/urls');

?>

<ul class="ccm-dialog-tabs" id="ccm-area-tabs">
<li class="ccm-nav-active"><a href="javascript:void(0)" id="ccm-add">Add New</a></li>
<li><a href="javascript:void(0)" id="ccm-add-existing">Add From Scrapbook</a></li>
<?php  if (PERMISSIONS_MODEL != 'simple') { ?><li><a href="javascript:void(0)" id="ccm-permissions">Permissions</a></li><?php  } ?>
</ul>

<div id="ccm-add-tab">
<h1>Add New Block</h1>
<div id="ccm-block-type-list">
<?php  if (count($blockTypes) > 0) {

	foreach($blockTypes as $bt) { 
		$btIcon = $ci->getBlockTypeIconURL($bt);
		?>
	
	<div class="ccm-block-type">
		<a class="ccm-block-type-help" href="javascript:ccm_showBlockTypeDescription(<?php echo $bt->getBlockTypeID()?>)" title="Learn more about this block type." id="ccm-bt-help-trigger<?php echo $bt->getBlockTypeID()?>"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/help.png" width="14" height="14" /></a>
		<a class="dialog-launch ccm-block-type-inner" dialog-modal="true" dialog-width="<?php echo $bt->getBlockTypeInterfaceWidth()?>" dialog-height="<?php echo $bt->getBlockTypeInterfaceHeight()?>" style="background-image: url(<?php echo $btIcon?>)" dialog-title="Add <?php echo $bt->getBlockTypeName()?>" href="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/add_block_popup.php?cID=<?php echo $c->getCollectionID()?>&btID=<?php echo $bt->getBlockTypeID()?>&arHandle=<?php echo $a->getAreaHandle()?>"><?php echo $bt->getBlockTypeName()?></a>
		<div class="ccm-block-type-description"  id="ccm-bt-help<?php echo $bt->getBlockTypeID()?>"><?php echo $bt->getBlockTypeDescription()?></div>
	</div>
	<?php  }
} else { ?>
	<p>No block types can be added to this area.</p>
<?php  } ?>
</div>
</div>

<script type="text/javascript">
ccm_showBlockTypeDescription = function(btID) {
	$("#ccm-bt-help" + btID).show();
}

var ccm_areaActiveTab = "ccm-add";

$("#ccm-area-tabs a").click(function() {
	$("li.ccm-nav-active").removeClass('ccm-nav-active');
	$("#" + ccm_areaActiveTab + "-tab").hide();
	ccm_areaActiveTab = $(this).attr('id');
	$(this).parent().addClass("ccm-nav-active");
	$("#" + ccm_areaActiveTab + "-tab").show();
});

$(function() {

	$("a.ccm-scrapbook-delete").click(function() {

		var pcID = $(this).attr('id').substring(2);
		$.ajax({
			type: 'POST',
			url: CCM_DISPATCHER_FILENAME,
			data: 'pcID=' + pcID + '&ptask=delete_content',
			success: function(msg) {
				$("#ccm-pc-" + pcID).fadeOut();
			}
		});
		
	});

});
</script>

<div id="ccm-add-existing-tab" style="display:none">
<h1>Add From Scrapbook</h1>
<div id="ccm-scrapbook-list">
<?php 
Loader::model('pile');
$sp = Pile::getDefault();
$contents = $sp->getPileContentObjects('date_desc');
if (count($contents) == 0) { 
	print 'You have no items in your scrapbook.';
}
foreach($contents as $obj) { 
	$item = $obj->getObject();
	if (is_object($item)) {
	$bt = $item->getBlockTypeObject();
		$btIcon = $ci->getBlockTypeIconURL($bt);
?>
	
	<div class="ccm-scrapbook-list-item" id="ccm-pc-<?php echo $obj->getPileContentID()?>">
	<div class="ccm-block-type">
		<a class="ccm-scrapbook-delete" title="Remove from Scrapbook" href="javascript:void(0)" id="sb<?php echo $obj->getPileContentID()?>"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/delete_small.png" width="16" height="16" /></a>
		<a class="ccm-block-type-inner" style="background-image: url(<?php echo $btIcon?>)" href="<?php echo DIR_REL?>/index.php?pcID[]=<?php echo $obj->getPileContentID()?>&add=1&processBlock=1&cID=<?php echo $c->getCollectionID()?>&arHandle=<?php echo $a->getAreaHandle()?>&btask=alias_existing_block"><?php echo $bt->getBlockTypeName()?></a>
	<div class="ccm-scrapbook-list-item-detail">	
	<?php 
	
	try {
		$bv = new BlockView();
		$bv->render($item);
	} catch(Exception $e) {
		print BLOCK_NOT_AVAILABLE_TEXT;
	}
	
	?>

	</div>
	</div>
	</div>
	
	<?php 
	$i++;
}	
	
}	?>
</div>
</div>

<div id="ccm-permissions-tab" style="display: none">
<h1>Set Area Permissions</h1>

<?php 

if ($cp->canAdminPage() && is_object($a)) {

	$btArray = BlockTypeList::getAreaBlockTypes($a, $cp);
	
	
	// if the area overrides the collection permissions explicitly (with a one on the override column) we check
	
	if ($a->overrideCollectionPermissions()) {
		$gl = new GroupList($a);
		$ul = new UserInfoList($a);
	} else {
		// now it gets more complicated. 
		$permsSet = false;
		
		if ($a->getAreaCollectionInheritID() > 0) {
			// in theory we're supposed to be inheriting some permissions from an area with the same handle,
			// set on the collection id specified above (inheritid). however, if someone's come along and
			// reverted that area to the page's permissions, there won't be any permissions, and we 
			// won't see anything. so we have to check
			$areac = Page::getByID($a->getAreaCollectionInheritID());
			$inheritArea = Area::get($areac, $_GET['arHandle']);
			if ($inheritArea->overrideCollectionPermissions()) {
				// okay, so that area is still around, still has set permissions on it. So we
				// pass our current area to our grouplist, userinfolist objects, knowing that they will 
				// smartly inherit the correct items.
				$gl = new GroupList($a);
				$ul = new UserInfoList($a);
				$permsSet = true;				
			}
		}		
		
		if (!$permsSet) {
			// otherwise we grab the collection permissions for this page
			$gl = new GroupList($c);
			$ul = new UserInfoList($c);
		}	
	}
	
	$gArray = $gl->getGroupList();
	$ulArray = $ul->getUserInfoList();
	
	?>
	<script type="text/javascript">
		function ccm_triggerSelectUser(uID, uName) {
			rowValue = "uID:" + uID;
			rowText = uName;
			if ($("#_row_uID_" + uID).length > 0) {
				return false;
			}			

			tbl = document.getElementById("ccmPermissionsTable");	   
			row1 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
			row2 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
			row3 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?    
			
			row1.className = rowValue.replace(":","_");
			row1.id = "_row_uID_" + uID;
			row2.className = rowValue.replace(":","_");
			row3.className = rowValue.replace(":","_");
			
			row1innerHTML = '<th colspan="7" style="text-align: left; white-space: nowrap"><a href="javascript:removePermissionRow(\'' + rowValue.replace(':','_') + '\',\'' + rowText + '\')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12" style="float: right" ></a>' + uName + '</td>';
			row2innerHTML = '<td valign="top" style="text-align: center"><strong>Read</strong></td>';
			row2innerHTML += '<td valign="top" ><input type="checkbox" name="areaRead[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td><div style="width: 54px; text-align: right"><strong>Write</strong></div></td><td><input type="checkbox" name="areaEdit[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td><div style="width: 54px; text-align: right"><strong>Delete</strong></div></td><td><input type="checkbox" name="areaDelete[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td valign="top" width="100%"><div style="width: 225px">&nbsp;</div></td>';
			row3innerHTML = '<td valign="top"  style="text-align: center"><strong>Add</strong></td>';
			row3innerHTML += '<td colspan="6" width="100%"><div style="width: 460px;">';
			<?php  foreach ($btArray as $bt) { ?>
				row3innerHTML += '<span style="white-space: nowrap; float: left; width: 80px; margin-right: 20px"><input type="checkbox" name="areaAddBlockType[<?php echo $bt->getBlockTypeID()?>][]" value="' + rowValue + '" />&nbsp;<?php echo $bt->getBlockTypeName()?></span>';
			<?php  } ?>	
			row3innerHTML += '</div></td>';
			
			row1.innerHTML = row1innerHTML;
			row2.innerHTML = row2innerHTML;
			row3.innerHTML = row3innerHTML;
			
			tbl.appendChild(row1);
			tbl.appendChild(row2);
			tbl.appendChild(row3);
		}
		
		function ccm_triggerSelectGroup(gID, gName) {
			// we add a row for the selected group
			var rowText = gName;
			var rowValue = "gID:" + gID;
			
			if ($("#_row_gID_" + gID).length > 0) {
				return false;
			}
			
			tbl = document.getElementById("ccmPermissionsTable");	   
			row1 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
			row1.id = "_row_gID_" + gID;
			row2 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?                            
			row3 = tbl.insertRow(-1); // insert at bottom of table. safari, wtf ?    
			
			row1.className = rowValue.replace(":","_");
			row2.className = rowValue.replace(":","_");
			row3.className = rowValue.replace(":","_");
			
			row1innerHTML = '<th colspan="7" style="text-align: left; white-space: nowrap"><a href="javascript:removePermissionRow(\'' + rowValue.replace(':','_') + '\',\'' + rowText + '\')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12" style="float: right" ></a>' + rowText + '</td>';
			row2innerHTML = '<td valign="top" style="text-align: center"><strong>Read</strong></td>';
			row2innerHTML += '<td valign="top" ><input type="checkbox" name="areaRead[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td><div style="width: 54px; text-align: right"><strong>Write</strong></div></td><td><input type="checkbox" name="areaEdit[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td><div style="width: 54px; text-align: right"><strong>Delete</strong></div></td><td><input type="checkbox" name="areaDelete[]" value="' + rowValue + '"></td>';
			row2innerHTML += '<td valign="top" width="100%"><div style="width: 225px">&nbsp;</div></td>';
			row3innerHTML = '<td valign="top"  style="text-align: center"><strong>Add</strong></td>';
			row3innerHTML += '<td colspan="6" width="100%"><div style="width: 460px;">';
			<?php  foreach ($btArray as $bt) { ?>
				row3innerHTML += '<span style="white-space: nowrap; float: left; width: 80px; margin-right: 20px"><input type="checkbox" name="areaAddBlockType[<?php echo $bt->getBlockTypeID()?>][]" value="' + rowValue + '" />&nbsp;<?php echo $bt->getBlockTypeName()?></span>';
			<?php  } ?>	
			row3innerHTML += '</div></td>';
			
			row1.innerHTML = row1innerHTML;
			row2.innerHTML = row2innerHTML;
			row3.innerHTML = row3innerHTML;
			
			tbl.appendChild(row1);
			tbl.appendChild(row2);
			tbl.appendChild(row3);
			
		}
		
		function removePermissionRow(rowID, origText) {
			$("." + rowID + " input[type=checkbox]").each(function() {
				this.checked = false;
			});
			$("." + rowID).hide();

    	  if (rowID && origText) {
        	  select = document.getElementById("groupSelect");
        	  currentLength = select.options.length;
        	  optionDivider = select.options[currentLength - 2];
	       	  optionAddUser = select.options[currentLength - 1];
	
	       	  select.options[currentLength - 2] = new Option(origText, rowID.replace('_',':'));
	       	  select.options[currentLength - 1] = optionDivider;
	       	  select.options[currentLength] = optionAddUser;
		  }

		}
		
		function setPermissionAvailability(value) {
			tbl = document.getElementById("ccmPermissionsTable");
			switch(value) {
				case "OVERRIDE":
					inputs = tbl.getElementsByTagName("INPUT");
					for (i = 0; i < inputs.length; i++) {
						inputs[i].disabled = false;
					}
					document.getElementById("groupSelect").disabled = false;
					break;
				default:
					inputs = tbl.getElementsByTagName("INPUT");
					for (i = 0; i < inputs.length; i++) {
						inputs[i].disabled = true;
					}
					document.getElementById("groupSelect").disabled = true;
					break;
			}
		}
		
	</script>

<form method="post" name="permissionForm" action="<?php echo $a->getAreaUpdateAction()?>">
<?php  
if ($a->getAreaCollectionInheritID() != $c->getCollectionID() && $a->getAreaCollectionInheritID() > 0) {
	$pc = $c->getPermissionsCollectionObject(); 
	$areac = Page::getByID($a->getAreaCollectionInheritID());
?>

The following area permissions are inherited from an area set on <a href="<?php echo DIR_REL?>/index.php?cID=<?php echo $areac->getCollectionID()?>"><?php echo $areac->getCollectionName()?></a>. To change them everywhere, edit this area on that page. To override them here and on all sub-pages, edit below.</p>

<?php  } else if (!$a->overrideCollectionPermissions()) {

?>

The following area permissions are inherited from the page's permissions. To override them, edit below.

<?php  } else { ?>

<span class="ccm-important">
	Permissions for this area currently override those of the page. To revert to the page's permissions, click <strong>revert to page permissions</strong> below.<br/><br/>
</span>

<?php  } ?>

<div class="ccm-buttons" style="margin-bottom: 10px"> 
<a href="<?php echo REL_DIR_FILES_TOOLS_REQUIRED?>/user_group_selector.php?cID=<?php echo $_REQUEST['cID']?>" dialog-width="600" dialog-title="Choose User/Group"  dialog-height="400" class="dialog-launch ccm-button-right"><span><em class="ccm-button-add">Add Group or User</em></span></a>
</div>
<div class="ccm-spacer">&nbsp;</div><br/>

<table id="ccmPermissionsTable" border="0" cellspacing="0" cellpadding="0" class="ccm-grid" style="width: 100%">
<?php  

$rowNum = 1;
foreach ($gArray as $g) { 
$displayRow = false;
$display = (($g->getGroupID() == GUEST_GROUP_ID || $g->getGroupID() == REGISTERED_GROUP_ID) || $g->canRead() || $g->canWrite() || $g->canAddBlocks() || $g->canDeleteBlock()) 
? true : false;

if ($display) { ?>


<tr class="gID_<?php echo $g->getGroupID()?>" id="_row_gID_<?php echo $g->getGroupID()?>">
<th colspan="7" style="text-align: left; white-space: nowrap"><?php  if ($g->getGroupID() != GUEST_GROUP_ID && $g->getGroupID() != REGISTERED_GROUP_ID) { ?>    
			<a href="javascript:removePermissionRow('gID_<?php echo $g->getGroupID()?>', '<?php echo $g->getGroupName()?>')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12" style="float: right" ></a>
		<?php  } ?>
		<?php echo $g->getGroupName()?></th>		
</tr>
<tr class="gID_<?php echo $g->getGroupID()?>">
<td valign="top" style="text-align: center"><strong>Read</strong></td>
<td valign="top" ><input type="checkbox" name="areaRead[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canRead()) { ?> checked<?php  } ?>></td>
<td><div style="width: 54px; text-align: right"><strong>Write</strong></div></td>
<td><input type="checkbox" name="areaEdit[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canWrite()) { ?> checked<?php  } ?>></td>
<td><div style="width: 54px; text-align: right"><strong>Delete</strong></div></td>
<td><input type="checkbox" name="areaDelete[]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($g->canDeleteBlock()) { ?> checked<?php  } ?>></td>
<td valign="top" width="100%"><div style="width: 225px">&nbsp;</div></td>
</tr>
<tr class="gID_<?php echo $g->getGroupID()?>">
<td valign="top"  style="text-align: center"><strong>Add</strong></td>
<td colspan="6" width="100%">
<div style="width: 460px;">
<?php  foreach ($btArray as $bt) { ?>
		<span style="white-space: nowrap; float: left; width: 80px; margin-right: 20px"><input type="checkbox" name="areaAddBlockType[<?php echo $bt->getBlockTypeID()?>][]" value="gID:<?php echo $g->getGroupID()?>"<?php  if ($bt->canAddBlock($g)) { ?> checked<?php  } ?>>&nbsp;<?php echo $bt->getBlockTypeName()?></span>
		
	<?php  } ?>
</div>
</td>
</tr>

<?php  
$rowNum++;
} ?>
<?php   }
	
foreach ($ulArray as $ui) { ?>

<tr id="_row_uID_<?php echo $ui->getUserID()?>" class="uID_<?php echo $ui->getUserID()?>" class="no-bg">

<th colspan="7" style="text-align: left; white-space: nowrap">
<a href="javascript:removePermissionRow('uID_<?php echo $ui->getUserID()?>')"><img src="<?php echo ASSETS_URL_IMAGES?>/icons/remove.png" width="12" height="12" style="float: right"></a>
<?php echo $ui->getUserName()?>
</th>		
</tr>
<tr class="uID_<?php echo $ui->getUserID()?>" >
<td valign="top" style="text-align: center"><strong>Read</strong></td>
<td valign="top" ><input type="checkbox" name="areaRead[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canRead()) { ?> checked<?php  } ?>></td>
<td><div style="width: 54px; text-align: right"><strong>Write</strong></div></td>
<td><input type="checkbox" name="areaEdit[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canWrite()) { ?> checked<?php  } ?>></td>
<td><div style="width: 54px; text-align: right"><strong>Delete</strong></div></td>
<td><input type="checkbox" name="areaDelete[]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($ui->canDeleteBlock()) { ?> checked<?php  } ?>></td>
<td valign="top" width="100%"><div style="width: 225px">&nbsp;</div></td>
</tr>
<tr class="uID_<?php echo $ui->getUserID()?>" >
<td valign="top"  style="text-align: center"><strong>Add</strong></td>
<td colspan="6" width="100%">
<div style="width: 460px;">
<?php  foreach ($btArray as $bt) { ?>
		<span style="white-space: nowrap; float: left; width: 80px; margin-right: 20px"><input type="checkbox" name="areaAddBlockType[<?php echo $bt->getBlockTypeID()?>][]" value="uID:<?php echo $ui->getUserID()?>"<?php  if ($bt->canAddBlock($ui)) { ?> checked<?php  } ?>>&nbsp;<?php echo $bt->getBlockTypeName()?></span>
		
	<?php  } ?>
</div>
</td>
</tr>

<?php  
$rowNum++;
} ?>

</table>

<input type="hidden" name="aRevertToPagePermissions" id="aRevertToPagePermissions" value="0" />

<div class="ccm-buttons">
<?php  if ($a->overrideCollectionPermissions()) { ?>
	<a href="javascript:void(0)" onclick="$('#aRevertToPagePermissions').val(1);$('form[name=permissionForm]').get(0).submit()" class="ccm-button-left cancel"><span>Revert to Page Permissions</span></a>
<?php  } ?>

	<a href="javascript:void(0)" onclick="$('form[name=permissionForm]').get(0).submit()" class="ccm-button-right accept"><span>Update</span></a>
</div>
<div class="ccm-spacer">&nbsp;</div>
</div>

</form>


<?php  } ?>
