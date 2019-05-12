<?php
/*************************************************************************************************
 * Copyright 2013 JPL TSolucio, S.L.  --  This file is a part of vtiger CRM TimeControl extension.
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
 *************************************************************************************************
 *  Module       : TimeMaterialControl
 *  Version      : 5.4.2
 *  Author       : Joe Bordes JPL TSolucio, S. L.
 *************************************************************************************************/
require_once 'Smarty_setup.php';

global $mod_strings, $app_strings, $currentModule, $current_user, $theme, $current_user;

$focus = CRMEntity::getInstance($currentModule);
$smarty = new vtigerCRM_Smarty();

$record = vtlib_purify($_REQUEST['record']);
if (empty($record)) {
	die();
}

$focus->id = $record;
$focus->retrieve_entity_info($record, $currentModule);
$focus->preViewCheck($_REQUEST, $smarty);

// Identify this module as custom module.
$smarty->assign('CUSTOM_MODULE', true);

$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
// TODO: Update Single Module Instance name here.
$smarty->assign('SINGLE_MOD', 'SINGLE_'.$currentModule);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('THEME', $theme);
$smarty->assign('ID', $focus->id);

if ($focus->column_fields['date_end']=='') {
	$tcdate = new DateTimeField($focus->column_fields['date_start'].' '.$focus->column_fields['time_start']);
	$date = $tcdate->getDBInsertDateValue($current_user);
	$time = $focus->column_fields['time_start'];
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute) = explode(':', $time);
	$starttime = mktime($hour, $minute, 0, $month, $day, $year);
	$vnow = new DateTimeField(null);
	$date = $vnow->getDBInsertDateValue($current_user);
	$time = $vnow->getDisplayTime($current_user);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute) = explode(':', $time);
	$nowtime = mktime($hour, $minute, 0, $month, $day, $year);
	$counter = abs($nowtime-$starttime);
	$smarty->assign('SHOW_WATCH', 'started');
	$smarty->assign('WATCH_COUNTER', $counter);
} else {
	$smarty->assign('SHOW_WATCH', 'halted');
	$smarty->assign('WATCH_DISPLAY', $focus->column_fields['totaltime']);
}

$smarty->display('modules/Timecontrol/detailview.tpl');
?>
