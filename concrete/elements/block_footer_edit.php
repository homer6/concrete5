<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
$step = ($_REQUEST['step']) ? "&step={$_REQUEST['step']}" : ""; ?>

<?php  global $c; ?>
	
	<?php  if (is_array($extraParams)) { // defined within the area/content classes 
		foreach($extraParams as $key => $value) { ?>
			<input type="hidden" name="<?php echo $key?>" value="<?php echo $value?>">
		<?php  } ?>
	<?php  } ?>
	
	<div class="ccm-buttons">
	<a href="javascript:void(0)" <?php  if ($replaceOnUnload) { ?>onclick="location.href='<?php echo DIR_REL?>/index.php?cID=<?php echo $c->getCollectionID()?><?php echo $step?>'; return true" class="ccm-button-left cancel"<?php  } else { ?>class="ccm-dialog-close ccm-button-left cancel"<?php  } ?>><span><em class="ccm-button-close">Cancel</em></span></a>
	<a href="javascript:clickedButton = true;$('#ccm-form-submit-button').get(0).click()" class="ccm-button-right accept"><span><em class="ccm-button-update">Update</em></span></a>
	</div>	

	<input type="hidden" name="update" value="1" />
	<input type="submit" name="submit" value="submit" style="display: none" id="ccm-form-submit-button" />
	<input type="hidden" name="processBlock" value="1">

	</form>

</div>