<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$user_id = intval(dPgetParam($_GET, 'user_id', 0));
$token = dPgetParam($_GET, 'token', '');

if (!$user_id || !$token) {
	$AppUI->setMsg('Invalid password reset link.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Reset Password</div>
                <div class="card-body">
                    <form name="frmLogin" action="index.php?m=public&a=do_reset_password" method="post">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
                        <input type="hidden" name="token" value="<?php echo $token; ?>" />
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" class="form-control" required />
                        </div>
                        <div class="form-group">
                            <label for="password_confirm">Confirm New Password</label>
                            <input type="password" name="password_confirm" class="form-control" required />
                        </div>
                        <button type="submit" class="btn btn-primary">Set New Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
