<?php   defined('C5_EXECUTE') or die(_("Access Denied.")); ?> 
<style>
table#videoBlockSetup {margin-top:16px}
table#videoBlockSetup th {font-weight: bold; text-style: normal; padding-right: 8px; white-space: nowrap; vertical-align:top ; padding-bottom:8px}
table#videoBlockSetup td{ font-size:12px; vertical-align:top; padding-bottom:8px;}
table#videoBlockSetup .note{ font-size:10px; color:#999999; font-weight:normal }
</style> 

<table id="videoBlockSetup"> 
	<tr>
		<th><?php  echo t("Width")?></th>
		<td><input type="text" style="width: 40px" name="width" value="<?php  echo $bObj->width?>"/></td>
	</tr>	
	<tr>
		<th><?php  echo t("Height")?></th>
		<td>
			<input type="text" style="width: 40px" name="height" value="<?php  echo $bObj->height?>" />
		</td>
	</tr>	
</table>