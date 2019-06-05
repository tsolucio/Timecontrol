<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'modules/Timecontrol/Timecontrol.php';
$record = (isset($_REQUEST['record']) ? vtlib_purify($_REQUEST['record']) : 0);
$isduplicate = (isset($_REQUEST['isDuplicate']) ? vtlib_purify($_REQUEST['isDuplicate']) : '');
if (!empty($_REQUEST['calendarrecord'])) { // coming from Calendar
	require_once 'modules/Calendar/Calendar.php';
	$c4y = CRMEntity::getInstance('Calendar');
	$c4yrecord = vtlib_purify($_REQUEST['calendarrecord']);
	$c4y->id = vtlib_purify($c4yrecord);
	$activity_mode = vtlib_purify($_REQUEST['activity_mode']);
	$c4y->retrieve_entity_info($c4yrecord, ($activity_mode == 'Task' ? 'Calendar' : 'Events'));
	$_REQUEST['title'] = getTranslatedString($c4y->column_fields['activitytype'], 'Calendar').' :: '.$c4y->column_fields['subject'];
	$_REQUEST['date_start'] = $c4y->column_fields['date_start'];
	$_REQUEST['time_start'] = $c4y->column_fields['time_start'];
	$_REQUEST['date_end'] = $c4y->column_fields['due_date'];
	if ($activity_mode == 'Task') {
		$vtnow=new DateTimeField(null);
		$_REQUEST['time_end'] = $vtnow->getDisplayTime($current_user);
	} else {
		$_REQUEST['time_end'] = $c4y->column_fields['time_end'];
	}
	$_REQUEST['relatedto'] = $c4y->column_fields['parent_id'];
	$_REQUEST['tcunits'] = 1;
	$_REQUEST['assigned_user_id'] = $c4y->column_fields['assigned_user_id'];
	$_REQUEST['description'] = $c4y->column_fields['description'];
} elseif ($isduplicate=='restart') {
	$vtnow=new DateTimeField();
	$_REQUEST['time_start'] = $vtnow->getDisplayTime($current_user);
	$_REQUEST['time_end'] = '';
	if (Timecontrol::$now_on_resume) {
		$_REQUEST['date_start'] = $vtnow->getDBInsertDateValue($current_user);
		$_REQUEST['date_end'] = $vtnow->getDBInsertDateValue($current_user);
	}
	$_REQUEST['tcunits'] = 1;
	$_REQUEST['totaltime'] = '';
	$_REQUEST['isDuplicate'] = 'true';
} elseif (empty($record)) { // creating
	$cbnow=new DateTimeField(null);
	$_REQUEST['time_start'] = $cbnow->getDisplayTime($current_user);
	$rshd=$adb->pquery('select tcproduct from vtiger_users where id=?', array($current_user->id));
	if ($rshd) {
		$tcpdo = $adb->query_result($rshd, 0, 'tcproduct');
		if (!empty($tcpdo)) {
			$_REQUEST['product_id']=$tcpdo;
		}
	}
}

$rsusrpdo=$adb->pquery('select tcproduct from vtiger_users where id=?', array($current_user->id));
if ($rsusrpdo) {
	$tcpdo = $adb->query_result($rsusrpdo, 0, 'tcproduct');
	if (!empty($tcpdo)) {
		$_REQUEST['product_id']=$tcpdo;
	}
}

// Contribution made by Ted Janzen of Janzen & Janzen ICT Services http://www.j2ict.nl
$relto = (!empty($_REQUEST['relatedto']) ? vtlib_purify($_REQUEST['relatedto']) : '');
if (!empty($relto) && getSalesEntityType($relto)=='HelpDesk') { // coming from TT, pickup data
	$rshd=$adb->pquery('select ticket_no,product_id from vtiger_troubletickets where ticketid=?', array($relto));
	if (empty($_REQUEST['product_id'])) {
		$_REQUEST['product_id']=$adb->query_result($rshd, 0, 'product_id');
	}
	$_REQUEST['title']=$adb->query_result($rshd, 0, 'ticket_no');
}

require_once 'modules/Vtiger/EditView.php';

$smarty->display('salesEditView.tpl');
?>
