<?php $target = urldecode($_GET['target']); ?>
<!doctype html public "-//W3C//DTD HTML 4.0 Frameset//EN">
<html>
<head>
<title> Test suite for JpGraph - <?php echo htmlspecialchars($target, ENT_QUOTES); ?></title>
<script type="text/javascript" language="javascript">
<!--
function resize()
{
	return true;
}
//-->
</script>
</head>
<frameset rows="*,*" onLoad="resize()">
	<?php 
	if( !strstr($target,"csim") )
		echo "<frame src=\"show-image.php?target=".htmlspecialchars(basename($target), ENT_QUOTES)."\" name=\"image\">";
	else
		echo	"<frame src=\"".htmlspecialchars(basename($target), ENT_QUOTES)."\" name=\"image\">";
	?>
	<frame src="show-source.php?target=<?php echo htmlspecialchars(basename($target), ENT_QUOTES); ?>" name="source">
</frameset>
</html>
