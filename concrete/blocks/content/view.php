<?php 
	defined('C5_EXECUTE') or die(_("Access Denied."));
	// now that we're in the specialized content file for this block type, 
	// we'll include this block type's class, and pass the block to it, and get
	// the content
	
	// so that they can add links and what-not
	//$content = eregi_replace('(<a [^<]*href=["|\']?([^"mailto"][^ "\']*)["|\']?[^>]*>(.*)</a>)','<a href="\\2" onclick="javascript:popOffsiteWindow(\'\\2\',\'640\',\'480\'); return false">\\3</a>', $bc->getContent());
	$content = $controller->getContent();
	print $content;
	
?>