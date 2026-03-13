<?php /* earnings $Id: inv_aed.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
$earning_id = intval( dPgetParam( $_GET, "earning_id", 0 ) );

require_once( $AppUI->getSystemClass( 'libmail' ) );

// Respond To inv_dosql Commands in _POST
if (isset( $_POST['inv_dosql'] ) ) {
	// Figure out what kind

	// Add AdHoc Item
	if ($_POST['inv_dosql'] == "adhoc") {
		// Add items from AdHoc Form To earning
		$sql="INSERT INTO earnings_items (earning_parent_id, earning_tasklog_id, earning_item_date, earning_item_description, earning_item_hours,earning_item_costcode,earning_item_rate) VALUES (";
		// earning_parent_id
		$sql .= "'" . $_POST["adhoc_earning_parent_id"] . "',";
		// earning_tasklog_id (n/a)
		$sql .= "'" . $_POST["adhoc_earning_tasklog_id"] . "',";
		// earning_item_date (formatted for MySQL)
		$sql .= "'" . $_POST["adhoc_earning_item_date"] . "',";
		// earning_item_description
		$sql .= "'" . addslashes($_POST["adhoc_earning_item_description"]) . "',";
		// earning_item_hours
		$sql .= $_POST["adhoc_earning_item_hours"] . ",";
		// earning_item_costcode
		$sql .= "'n/a',";
		// earning_item_rate
		$sql .= $_POST["adhoc_earning_item_rate"];
		// end sql statement.
		$sql .= ");";
		if (!db_exec( $sql )) {
			echo db_error();
		}
		$AppUI->redirect();
	}

	// Remove Items That Were Checked
	if ($_POST['inv_dosql'] == "remitem") {
		// Remove earning Item(s) From earning
		$remove_items = "";
		foreach ($_POST as $item) {
			@list($item_action,$item_value) = explode(":",$item);
			if ( $item_action = "remove" ) {
				if ( intval($item_value) > 0 ) {
					if ( strlen($remove_items) > 0 ) {
						$remove_items .= ",";
					}
					$remove_items .= "'" . strval($item_value) . "'";
				}
			}
		}
		$sql = "DELETE FROM earnings_items WHERE earning_items_id IN (";
		$sql .= $remove_items . ");";
		if (!db_exec( $sql )) {
			echo db_error();
		}
		echo $sql;
		$AppUI->redirect();
	}

	// Update Rate Changes On Items
	if ($_POST['inv_dosql'] == "updrates") {
		$cases = array();
		$ids = array();
		foreach ($_POST as $key => $value) {
			$parts = explode(":", $key);
			if (count($parts) < 2) continue;
			$item_action = $parts[0];
			$item_rec = $parts[1];

			if ( $item_action == "rate" ) {
				$cases[] = "WHEN " . (int)$item_rec . " THEN " . (float)$value;
				$ids[] = (int)$item_rec;
			}
		}

		if (count($ids) > 0) {
			$sql = "UPDATE earnings_items SET earning_item_rate = CASE earning_items_id ";
			$sql .= implode(" ", $cases);
			$sql .= " END WHERE earning_items_id IN (" . implode(",", $ids) . ")";
			if (!db_exec( $sql )) {
				echo db_error();
			}
		}
		$AppUI->redirect();
	}


	// Add Items That Were Checked
	if ($_POST['inv_dosql'] == "additem") {
		$add_items = "";
		foreach ($_POST as $item) {
			@list($item_action,$item_value) = explode(":",$item);
			if ( $item_action = "add" ) {
				if ( intval($item_value) > 0 ) {
					if ( strlen($add_items) > 0 ) {
						$add_items .= ",";
					}
					$add_items .= "'" . $item_value . "'";
				}
			}
		}
		// First gather all the selected Task_log Items
		$sql="SELECT task_log.*, tasks.*, projects.* FROM task_log, tasks, projects WHERE task_id = task_log_task AND project_id = task_project AND task_log_id IN (";
		$sql .= $add_items . ");";
		$arc= db_exec( $sql );
		echo db_error();
		$add_records = array();
		while ($row = db_fetch_assoc($arc)) {
			$add_records[$row["task_log_id"]] = $row;
		}
		foreach($add_records as $x) {
			$sql="INSERT INTO earnings_items (earning_parent_id, earning_tasklog_id, earning_item_date, earning_item_description, earning_item_hours,earning_item_costcode,earning_item_rate) VALUES (";
			// earning_parent_id
			$sql .= "'" . $earning_id . "',";
			// earning_tasklog_id (n/a)
			$sql .= "'" . $x["task_log_id"] . "',";
			// earning_item_date (formatted for MySQL)
			$sql .= "'" . $x["task_log_date"] . "',";
			// earning_item_description
			$sql .= "'" . addslashes($x["task_log_description"]) . "',";
			// earning_item_hours
			$sql .= $x["task_log_hours"] . ",";
			// earning_item_costcode
			$sql .= "'" . addslashes($x["task_log_costcode"]) . "',";
			// earning_item_rate
			$sql .= '0';
			// end sql statement.
			$sql .= ");";
			if (!db_exec( $sql )) {
				echo db_error();
			}
		}
		$relocate = "m=earnings&a=view&earning_id=" . $earning_id . "&tab=0";
		$AppUI->redirect($relocate);
	}

	// Kill An earning and All Its Supporting earning Items
	if ($_POST['inv_dosql'] == "killearning") {
		// Confirmation Has Already Been Received
		// Delete All Supporting earning_Items First
		$sql = "DELETE FROM earnings_items WHERE earning_parent_id = " . $earning_id . ";";
		echo $sql;
		//if (!db_exec( $sql )) {
		//	echo db_error();
		//}

		// Then Delete earning Itself
		$sql = "DELETE FROM earnings WHERE earning_id = " . $earning_id . ";";
		//echo $sql;
		if (!db_exec( $sql )) {
			echo db_error();
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);

	}	

	// Update An Existing earning
	if ($_POST['inv_dosql'] == "editinv") {
		// Create SQL UPDATE command from POST variables
		$sql = "UPDATE earnings SET";
		$sql .= " earning_date='" . $_POST["earning_date"] . "',";
		$sql .= " earning_num='" . $_POST["earning_num"] . "',";
		$sql .= " earning_submit_contact='" . $_POST["earning_submit_contact"] . "',";
		$sql .= " earning_submit_company_id='" . $_POST["earning_submit_company_id"] . "',";
		$sql .= " earning_submit_email='" . $_POST["earning_submit_email"] . "',";
		$sql .= " earning_terms='" . $_POST["earning_terms"] . "',";
		$sql .= " earning_comments='" . addslashes($_POST["earning_comments"]) . "',";
		$sql .= " earning_title='" . addslashes($_POST["earning_title"]) . "',";
		if ( strcmp($_POST["earning_submit_address1"], "") == 0 ) {
			// The earning address override hasn't been used so let's fill it in automatically
			// Gather Company Details
			$sql2="SELECT companies.* FROM companies WHERE company_id='" . $_POST["earning_submit_company_id"] . "';";
			$crc= db_exec( $sql2 );
			echo db_error();
			while ($row = db_fetch_assoc($crc)) {
				$company_records[$row["task_log_id"]] = $row;
			}
			$sql .= " earning_submit_address1='" . $company_records[""]["company_address1"] . "',";
			$sql .= " earning_submit_address2='" . $company_records[""]["company_address2"] . "',";
			$sql .= " earning_submit_city='" . $company_records[""]["company_city"] . "',";
			$sql .= " earning_submit_state='" . $company_records[""]["company_state"] . "',";
			$sql .= " earning_submit_zip='" . $company_records[""]["company_zip"] . "',";
			$sql .= " earning_submit_phone='" . $company_records[""]["company_phone1"] . "'";
		} else {
			$sql .= " earning_submit_address1='" . $_POST["earning_submit_address1"] . "',";
			$sql .= " earning_submit_address2='" . $_POST["earning_submit_address2"] . "',";
			$sql .= " earning_submit_city='" . $_POST["earning_submit_city"] . "',";
			$sql .= " earning_submit_state='" . $_POST["earning_submit_state"] . "',";
			$sql .= " earning_submit_zip='" . $_POST["earning_submit_zip"] . "',";
			$sql .= " earning_submit_phone='" . $_POST["earning_submit_phone"] . "'";
		}
		$sql .= " WHERE earning_id=" . $earning_id . ";";
		echo $sql;
		if (!db_exec( $sql )) {
			$AppUI->setMsg( "Error: Duplicate earning Number?", UI_MSG_ERROR );
			$AppUI->redirect();
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings&a=view&earning_id=" . $earning_id;
		$AppUI->redirect($relocate);
	}

	// Add A New earning
	if ($_POST['inv_dosql'] == "addinv") {
		// Create SQL INSERT command from POST variables
		$sql = "INSERT INTO earnings (earning_user_id, earning_date, earning_num, earning_submit_contact, earning_submit_company_id,";
		$sql .= "earning_submit_email, earning_terms, earning_comments, earning_title, earning_submit_address1, earning_submit_address2,";
		$sql .= "earning_submit_city, earning_submit_state, earning_submit_zip, earning_submit_phone";
		$sql .= ") VALUES ( ";
		$sql .= "'" .$AppUI->user_id . "',";
		$sql .= "'" .$_POST["earning_date"] . "',";
		$sql .= "'" . $_POST["earning_num"] . "',";
		$sql .= "'" . $_POST["earning_submit_contact"] . "',";
		$sql .= "'" . $_POST["earning_submit_company_id"] . "',";
		$sql .= "'" . $_POST["earning_submit_email"] . "',";
		$sql .= "'" . $_POST["earning_terms"] . "',";
		$sql .= "'" . addslashes($_POST["earning_comments"]) . "',";
		$sql .= "'" . addslashes($_POST["earning_title"]) . "',";
		if ( strcmp($_POST["earning_submit_address1"], "") == 0 ) {
			// The earning address override hasn't been used so let's fill it in automatically
			// Gather Company Details
			$sql2="SELECT companies.* FROM companies WHERE company_id='" . $_POST["earning_submit_company_id"] . "';";
			$crc= db_exec( $sql2 );
			echo db_error();
			while ($row = db_fetch_assoc($crc)) {
				$company_records[$row["task_log_id"]] = $row;
			}
			$sql .= "'" . $company_records[""]["company_address1"] . "',";
			$sql .= "'" . $company_records[""]["company_address2"] . "',";
			$sql .= "'" . $company_records[""]["company_city"] . "',";
			$sql .= "'" . $company_records[""]["company_state"] . "',";
			$sql .= "'" . $company_records[""]["company_zip"] . "',";
			$sql .= "'" . $company_records[""]["company_phone1"] . "'";
		} else {
			$sql .= "'" . $_POST["earning_submit_address1"] . "',";
			$sql .= "'" . $_POST["earning_submit_address2"] . "',";
			$sql .= "'" . $_POST["earning_submit_city"] . "',";
			$sql .= "'" . $_POST["earning_submit_state"] . "',";
			$sql .= "'" . $_POST["earning_submit_zip"] . "',";
			$sql .= "'" . $_POST["earning_submit_phone"] . "'";
		}
		$sql .= ");";
		echo $sql;
		if (!db_exec( $sql )) {
			echo db_error();
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);
	}

	// Processing Activity Updates
	if ( ($_POST['inv_dosql'] == "postsubmit") || ($_POST['inv_dosql'] == "postapprove") || ($_POST['inv_dosql'] == "postdecline") || ($_POST['inv_dosql'] == "postpaid") ) {
		$notifyMail = new Mail;
		switch ($_POST['inv_dosql']) {
			case "postsubmit":
				$sql="UPDATE earnings SET earning_submitted_comment='" . addslashes($_POST["earning_submitted_comment"]) . "', earning_submitted='" . date("Ymd") . "', earning_approved='', earning_approved_comment='' WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $_POST['earning_submit_contact'] . " <" . $_POST['earning_submit_email'] . ">" );
				$notifyMail->Subject( "Approval Requested from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "A pay request (timecard or invoice) has been placed into the queue for your approval.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n\n";
				$message .= "You can review and process this request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
			case "postapprove":
				$sql="UPDATE earnings SET earning_approved_comment='" . addslashes($_POST["earning_approved_comment"]) . "', earning_approved='" . date("Ymd") . "', earning_approved_by=" . $AppUI->user_id . " WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $_POST['earning_user_name'] . " <" . $_POST['earning_user_email'] . ">" );
				$notifyMail->Subject( "Approval Received from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "Your pay request (timecard or invoice) was approved.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n";
				$message .= "Approval Comments: " . stripslashes($_POST['earning_approved_comment']) . "\n\n";
				$message .= "You can review the pay request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
			case "postpaid":
				$sql="UPDATE earnings SET earning_paid_comment='" . addslashes($_POST["earning_paid_comment"]) . "', earning_paid='" . date("Ymd") . "' WHERE earning_id=" . $earning_id . ";";
				break;
			case "postdecline":
				$sql="UPDATE earnings SET earning_approved_comment='" . addslashes($_POST["earning_approved_comment"]) . "', earning_submitted='0000-00-00 00:00:00' WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $_POST['earning_user_name'] . " <" . $_POST['earning_user_email'] . ">" );
				$notifyMail->Subject( "Request Declined from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "Your pay request (timecard or invoice) was declined.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n";
				$message .= "Approval Comments: " . stripslashes($_POST['earning_approved_comment']) . "\n\n";
				$message .= "You can review and/or resubmit the pay request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
		}
		//echo $sql;
		if (!db_exec( $sql )) {
			echo db_error();
		}
		$notifyMail->Send();

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);
	}

}

var_dump($_POST);

