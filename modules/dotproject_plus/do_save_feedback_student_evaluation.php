<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/dotproject_plus/feedback/user_feedback_evaluation/feedback_evaluation.class.php");
$grade = (int)dPgetParam($_POST, 'grade', 0);
$evaluation=new CFeedbackEvaluation();
$evaluation->feedback_id=(int)dPgetParam($_POST, 'feedback_id', 0);
// Users rate feedback for themselves only.
$evaluation->user_id=(int)$AppUI->user_id;
$evaluation->grade=max(1, min(5, $grade));
if ($evaluation->feedback_id > 0 && $grade > 0) {
	$evaluation->store();
}
?>