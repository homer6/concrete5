<?php 
defined('C5_EXECUTE') or die(_("Access Denied."));
$url = parse_url($videoURL);
parse_str($url['query'], $query);
?>
<object width="425" height="344">
	<param name="movie" value="http://www.youtube.com/v/<?php echo $query['v']?>&hl=en"></param>
	<param name="wmode" value="transparent"></params>
	<embed src="http://www.youtube.com/v/<?php echo $query['v']?>&hl=en" type="application/x-shockwave-flash" wmode="transparent" width="425" height="344"></embed>
</object>