<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}

// Fetch some config values
$company_name = dPgetConfig('company_name', 'dotProject');
$page_title = dPgetConfig('page_title', 'dotProject');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>
		<?php echo $page_title; ?> :: Login
	</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap">
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle; ?>/main.css" media="all" />
	<link rel="shortcut icon" href="./style/<?php echo $uistyle; ?>/images/favicon.ico" type="image/ico" />
	<style type="text/css">
		:root {
			--primary-color: #1976d2;
			--primary-dark: #115293;
			--bg-color: #f0f2f5;
			--card-bg: #ffffff;
			--text-main: #333333;
			--text-muted: #666666;
			--border-color: #dddddd;
		}

		body {
			background-color: var(--bg-color);
			margin: 0;
			padding: 0;
			font-family: 'Roboto', sans-serif;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
		}

		.login-card {
			background: var(--card-bg);
			width: 100%;
			max-width: 400px;
			padding: 40px;
			border-radius: 12px;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
			text-align: center;
		}

		.login-header h1 {
			font-size: 24px;
			font-weight: 500;
			color: var(--text-main);
			margin-bottom: 8px;
		}

		.login-header p {
			font-size: 14px;
			color: var(--text-muted);
			margin-bottom: 32px;
		}

		.form-group {
			margin-bottom: 20px;
			text-align: left;
		}

		.form-group label {
			display: block;
			font-size: 13px;
			font-weight: 500;
			color: var(--text-muted);
			margin-bottom: 6px;
		}

		.form-group input {
			width: 100%;
			padding: 12px 16px;
			border: 1px solid var(--border-color);
			border-radius: 6px;
			font-size: 15px;
			font-family: inherit;
			box-sizing: border-box;
			transition: border-color 0.2s, box-shadow 0.2s;
		}

		.form-group input:focus {
			outline: none;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
		}

		.login-btn {
			width: 100%;
			padding: 12px;
			background-color: var(--primary-color);
			color: white;
			border: none;
			border-radius: 6px;
			font-size: 16px;
			font-weight: 500;
			cursor: pointer;
			transition: background-color 0.2s, transform 0.1s;
			margin-top: 10px;
		}

		.login-btn:hover {
			background-color: var(--primary-dark);
		}

		.login-btn:active {
			transform: scale(0.98);
		}

		.login-footer {
			margin-top: 24px;
			font-size: 14px;
		}

		.login-footer a {
			color: var(--primary-color);
			text-decoration: none;
			font-weight: 500;
		}

		.login-footer a:hover {
			text-decoration: underline;
		}

		.system-info {
			margin-top: 40px;
			font-size: 12px;
			color: var(--text-muted);
		}

		.logo-area {
			margin-bottom: 24px;
		}

		.logo-area img {
			max-height: 50px;
		}
	</style>
</head>

<body onload="document.loginform.username.focus();">

	<div class="login-card">
		<div class="logo-area">
			<a href="http://www.dotproject.net/">
				<img src="./style/default/images/dp_icon.gif" alt="dotProject Logo">
			</a>
		</div>

		<div class="login-header">
			<h1>
				<?php echo $company_name; ?>
			</h1>
			<p>Sign in to your account</p>
		</div>

		<form method="post" action="<?php echo $loginFromPage; ?>" name="loginform">
			<input type="hidden" name="login" value="login" />
			<input type="hidden" name="lostpass" value="0" />
			<input type="hidden" name="redirect" value="<?php echo $redirect; ?>" />

			<div class="form-group">
				<label for="username">
					<?php echo $AppUI->_('Username'); ?>
				</label>
				<input type="text" id="username" name="username" maxlength="255" required>
			</div>

			<div class="form-group">
				<label for="password">
					<?php echo $AppUI->_('Password'); ?>
				</label>
				<input type="password" id="password" name="password" maxlength="32" required>
			</div>

			<button type="submit" name="login" value="login" class="login-btn">
				<?php echo $AppUI->_('login'); ?>
			</button>
		</form>

		<div class="login-footer">
			<a href="javascript:void(0);" onclick="document.loginform.lostpass.value=1;document.loginform.submit();">
				<?php echo $AppUI->_('forgotPassword'); ?>
			</a>
		</div>

		<div class="system-info">
			<?php if (@$AppUI->getVersion()) { ?>
			Version
			<?php echo @$AppUI->getVersion(); ?><br />
			<?php
}?>
			<div style="margin-top: 8px;">
				<?php echo dPcheckLoginSystem(); ?>
			</div>
			<p style="font-size: 11px; margin-top: 12px; opacity: 0.7;">
				*
				<?php echo $AppUI->_("You must have cookies enabled in your browser"); ?>
			</p>
		</div>
	</div>

</body>

</html>