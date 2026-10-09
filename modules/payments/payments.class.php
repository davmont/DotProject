<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PAYMENTS $Id: payments.class.php,v 1.1.1.1 2004/04/01 16:20:41 aardvarkads Exp $ */
/**
 *	@package dotProject
 *	@subpackage modules
 *	@version $Revision: 1.1.1.1 $
*/

require_once( $AppUI->getSystemClass ('dp' ) );

/**
 *	Companies Class
 *	@todo Move the 'address' fields to a generic table
 */
class CPayment extends CDpObject {
	var $payment_id = NULL;
	var $payment_authcode = NULL;
       	var $payment_company = NULL;
	var $payment_amount = NULL;
	var $payment_type = NULL;
	var $payment_date = NULL;
	var $payment_owner = NULL;

	function __construct() {
		parent::__construct( 'payments', 'payment_id' );
	}

	function updatePaymentsInvoices( $cslist ) {
	// delete all current entries
		$q = new DBQuery();
		$q -> setDelete('invoice_payment');
		$q -> addWhere('payment_id = '.$this->payment_id);		
		$q -> exec();

	// process dependencies
		if(isset($cslist)) {
		  foreach ($cslist as $invoice_id) {
		    if (intval( $invoice_id ) > 0) {
		      $q -> clear();
		      $q -> addTable('invoice_payment');
		      $q -> addInsert('payment_id',$this->payment_id);
		      $q -> addInsert('invoice_id',$invoice_id);
		      $q -> exec();

		      $q -> clear();
		      $q -> addTable('invoices');
		      $q -> addUpdate('invoice_status', 1);
		      $q -> addWhere('invoice_id = '.$invoice_id);
		      $q -> exec();     		      
		    }
		  }
		}
	}


// overload check
	function check() {
		if ($this->payment_id === NULL) {
			return 'payment id is NULL';
		}
		$this->payment_id = intval( $this->payment_id );

		return NULL; // object is ok
	}

// overload canDelete
	function canDelete( &$msg, $oid=null ) {
		$tables[] = array( 'label' => 'Payment Invoices', 'name' => 'invoice_payment', 'idfield' => 'invoice_id', 'joinfield' => 'payment_id' );
	// call the parent class method to assign the oid
		return parent::canDelete( $msg, $oid, $tables );
	}
}
?>