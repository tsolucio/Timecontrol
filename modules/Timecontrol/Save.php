<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
global $current_user, $currentModule, $singlepane_view;

checkFileAccessForInclusion("modules/$currentModule/$currentModule.php");
require_once("modules/$currentModule/$currentModule.php");

$search = vtlib_purify($_REQUEST['search_url']);

$focus = new $currentModule();
if ($_REQUEST['stop_watch']) {
  $focus->retrieve_entity_info($_REQUEST['record'], $currentModule);
  foreach($focus->column_fields as $fieldname => $val) {    	
	$focus->column_fields[$fieldname] = decode_html($focus->column_fields[$fieldname]);
  }
  $date = new DateTimeField(null);
  $focus->column_fields['date_end'] = $date->getDisplayDate($current_user);
  $focus->column_fields['time_end'] = $date->getDisplayTime($current_user);
  $focus->column_fields['description'] = decode_html($focus->column_fields['description']);
}
else {
  setObjectValuesFromRequest($focus);
  if($_REQUEST['assigntype'] == 'U') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_user_id'];
  } elseif($_REQUEST['assigntype'] == 'T') {
    $focus->column_fields['assigned_user_id'] = $_REQUEST['assigned_group_id'];
  }
}

$mode = vtlib_purify($_REQUEST['mode']);
$record=vtlib_purify($_REQUEST['record']);
if($mode) $focus->mode = $mode;
if($record)$focus->id  = $record;

if ($focus->column_fields['date_end']=='' || $focus->column_fields['time_end']=='') {
  $focus->column_fields['date_end'] = '';
  $focus->column_fields['time_end'] = '';
}

$focus->save($currentModule);
$return_id = $focus->id;

$parenttab = getParentTab();
if(!empty($_REQUEST['return_module'])) {
	$return_module = vtlib_purify($_REQUEST['return_module']);
} else {
	$return_module = $currentModule;
}
if(!empty($_REQUEST['return_action'])) {
	$return_action = vtlib_purify($_REQUEST['return_action']);
} else {
	$return_action = 'DetailView';
}
if(isset($_REQUEST['return_id']) && $_REQUEST['return_id'] != '') {
	$return_id = vtlib_purify($_REQUEST['return_id']);
}
//code added for returning back to the current view after edit from list view
if(empty($_REQUEST['return_viewname']) or $singlepane_view == 'true') {
	$return_viewname='0';
} else {
	$return_viewname=vtlib_purify($_REQUEST['return_viewname']);
}
if(isset($_REQUEST['activity_mode'])) {
	$return_action .= '&activity_mode='.vtlib_purify($_REQUEST['activity_mode']);
}

header("Location: index.php?action=$return_action&module=$return_module&record=$return_id&parenttab=$parenttab&viewname=$return_viewname&start=".vtlib_purify($_REQUEST['pagenumber']).$search);
?>