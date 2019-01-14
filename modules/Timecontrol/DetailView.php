<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'Smarty_setup.php';

global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $log;

$smarty = new vtigerCRM_Smarty();

require_once 'modules/Vtiger/DetailView.php';
if ($focus->column_fields['date_end']=='') {
	$date = $focus->column_fields['date_start'];
	$time = $focus->column_fields['time_start'];
	$array_date = explode('-', $date);
	$year = $array_date[0];
	$month = $array_date[1];
	$day = $array_date[2];
	$array_time = explode(':', $time);
	$hour = $array_time[0];
	$minute = $array_time[1];
	$starttime = mktime($hour, $minute, 0, $month, $day, $year);
	$counter = time()-$starttime;
	$smarty->assign('SHOW_WATCH', 'started');
	$smarty->assign('WATCH_COUNTER', $counter);
} else {
	$smarty->assign('SHOW_WATCH', 'halted');
	$smarty->assign('WATCH_DISPLAY', $focus->column_fields['totaltime']);
}

$smarty->display('DetailView.tpl');
?>