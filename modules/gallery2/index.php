<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

##
## Gallery2 Integration Module 
##
## This is basically just the example from the gallery2 websire on how to create an entrypoint
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##

// DotProject and PHP stuff
$AppUI->savePlace();

// Load configuration and load gallery2
$q = new DBQuery();
$q -> addTable('gallery2');
$q -> addQuery('gallery_uri');
$gallery_uri= $q -> loadResult();

$q -> clearQuery();
$q -> addQuery('gallery_folder');
$gallery_folder= $q-> loadResult();

echo db_error();
if($gallery_folder && $gallery_uri)
{
	// initiate G2
	$q -> clearQuery();
	$q -> addQuery('gallery_user');
	$gallery_user= $q -> loadResult();
	require_once($gallery_folder."/embed.php");
	$ret = GalleryEmbed::init(array('g2Uri' => $gallery_uri ,
		'embedUri' => $dPconfig['base_url']."index.php?m=gallery2" ,
		'activeUserId' => $gallery_user));
		
	if ($ret) {
		$data = array();
		$data['bodyHtml'] = $ret->getAsHtml();
	}
	else
	{
		$data = runGallery();
	}
	$data['title'] = (isset($data['title']) && !empty($data['title'])) ? $data['title'] : 'Gallery';
	if (isset($data['bodyHtml'])) {
print <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>{$data['title']}</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
{$data['javascript']}
{$data['css']}
</head>
<body>
{$data['bodyHtml']}
</body>
</html>
<?php
EOF;
	}
}
else
{
	// Warn user about missing configuration 
	?>
	Please use admin console to configure the gallery2 module 
	<?php
}


function runGallery() 
{
	$data = array();
	
	// user interface: you could disable sidebar in G2 and get it as separate HTML to put it into a block
	// GalleryCapabilities::set('showSidebarBlocks', false);

	// handle the G2 request
	$g2moddata = GalleryEmbed::handleRequest();
  
	// show error message if isDone is not defined
	if (!isset($g2moddata['isDone'])) {
		$data['bodyHtml'] = 'isDone is not defined, something very bad must have happened.';
		return $data;
	}
	// exit if it was an immediate view / request (G2 already outputted some data)
	if ($g2moddata['isDone']) {
		exit; 
	}
  
	// put the body html from G2 into the xaraya template 
	$data['bodyHtml'] = isset($g2moddata['bodyHtml']) ? $g2moddata['bodyHtml'] : '';

	// get the page title, javascript and css links from the <head> html from G2
	$title = ''; $javascript = array();	$css = array();

	if (isset($g2moddata['headHtml'])) {
		list($data['title'], $css, $javascript) = GalleryEmbed::parseHead($g2moddata['headHtml']);
		$data['headHtml'] = $g2moddata['headHtml'];
	}

	// Add G2 javascript
	$data['javascript'] = '';
	if (!empty($javascript)) {
		foreach ($javascript as $script) {
			$data['javascript'] .= "\n".$script;
		}
	}

	// Add G2 css
	$data['css'] = '';
	if (!empty($css)) {
		foreach ($css as $style) {
			$data['css'] .= "\n".$style;
		}
	}

	// sidebar block
	if (isset($g2moddata['sidebarBlocksHtml']) && !empty($g2moddata['sidebarBlocksHtml'])) {
		$data['sidebarHtml'] = $g2moddata['sidebarBlocksHtml'];
	}
	return $data;
} 
?>
