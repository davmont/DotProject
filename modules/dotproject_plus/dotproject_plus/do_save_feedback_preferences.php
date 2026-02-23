<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/dotproject_plus/feedback/feedback.php");
require_once (DP_BASE_DIR . "/modules/dotproject_plus/feedback/feedback_controller.php");
$url = $_POST['url'];

session_start();
$_SESSION["user_especific_feedback"]=$_POST["especific_feedback"];
$_SESSION["user_generic_feedback"]=$_POST["generic_feedback"];
setcookie("user_generic_feedback", $_POST["generic_feedback"]==""?0:1, time()+60*60*24*30);
setcookie("user_especific_feedback", $_POST["especific_feedback"]==""?0:1, time()+60*60*24*30);


$AppUI->redirect($url);
?>