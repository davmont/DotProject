<?php $target =  urldecode($_GET['target']); ?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title> Image <?php echo htmlspecialchars(basename($target), ENT_QUOTES); ?></title>
</head>
<body>
<img src="<?php echo htmlspecialchars(basename($target), ENT_QUOTES); ?>" border=0 alt="<?php echo htmlspecialchars(basename($target), ENT_QUOTES); ?>" align="left">
</body>
</html>
