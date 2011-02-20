<?php  

defined('C5_EXECUTE') or die(_("Access Denied."));
$includeAssetLibrary = true;
$assetLibraryPassThru = array(
	'type' => 'image'
);
$al = Loader::helper('concrete/asset_library');
?>
<h2>Image</h2>
<?php echo $al->image('ccm-b-image', 'fID', ' Choose Image');?>

<br/><br/><br/>
<h2>Image On-State (Optional)</h2>
<?php echo $al->image('ccm-b-image-onstate', 'fOnstateID', ' Choose Image On-State');?>

<br/><br/><br/>
<strong>Alt Text/Caption</strong><br/>
<input type="text" style="width: 200px" name="altText" />