<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class Timecontrol extends CRMEntity {
	var $db, $log; // Used in class functions of CRMEntity

	// Variable to esablish start value on resume
	// true: dates and start time will be set to "now"
	// false: only start time will be set to "now"
	public static $now_on_resume=true;
	var $USE_RTE = 'true';
	var $sumup_HelpDesk = true;
	var $sumup_ProjectTask = true;

	var $table_name = 'vtiger_timecontrol';
	var $table_index= 'timecontrolid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;
	var $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_timecontrolcf', 'timecontrolid');
	var $related_tables = Array('vtiger_timecontrolcf'=>array('timecontrolid','vtiger_timecontrol', 'timecontrolid'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_timecontrol', 'vtiger_timecontrolcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_timecontrol'   => 'timecontrolid',
		'vtiger_timecontrolcf' => 'timecontrolid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Timecontrol Number' => array('timecontrol' => 'timecontrolnr'),
		'Title'=> Array('timecontrol' => 'title'),
		'Date Start' => array('timecontrol' => 'date_start'),
		'Time Start' => array('timecontrol' => 'time_start'),
		'Total Time' => array('timecontrol' => 'totaltime'),
		'Description' => Array('crmentity' => 'description'),
		'Assigned To' => Array('crmentity' => 'smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Timecontrol Number' => 'timecontrolnr',
		'Title'=> 'title',
		'Date Start' => 'date_start',
		'Time Start' => 'time_start',
		'Total Time' => 'totaltime',
		'Description' => 'description',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'timecontrolnr';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Timecontrol Number' => array('timecontrol' => 'timecontrolnr'),
		'Title'=> Array('timecontrol' => 'title')
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Timecontrol Number' => 'timecontrolnr',
		'Title'=> 'title'
	);

	// For Popup window record selection
	var $popup_fields = Array('timecontrolnr');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'timecontrolnr';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'title';

	// Required Information for enabling Import feature
	var $required_fields = Array();

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'date_start';
	var $default_sort_order='DESC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'timecontrolnr', 'date_start', 'time_start');

	function standarizetimefields() {
		// we format the time fields depending on the current user's timezone
		if (!empty($this->column_fields['date_start']) and !empty($this->column_fields['time_start'])) {
			$time_start = DateTimeField::convertToUserTimeZone($this->column_fields['date_start'].' '.$this->column_fields['time_start']);
			$this->column_fields['date_start'] = $time_start->format('Y-m-d');
			$ts = $time_start->format('H:i:s');
			$this->column_fields['time_start'] = $ts;
		}
		if (!empty($this->column_fields['date_end']) and !empty($this->column_fields['time_end'])) {
			$time_end = DateTimeField::convertToUserTimeZone($this->column_fields['date_end'].' '.$this->column_fields['time_end']);
			$this->column_fields['date_end'] = $time_end->format('Y-m-d');
			$te = $time_end->format('H:i:s');
			$this->column_fields['time_end'] = $te;
		}
	}

	function preEditCheck($request, $smarty) {
		$this->standarizetimefields();
	}

	function preViewCheck($request, $smarty) {
		$this->standarizetimefields();
	}

	function preSaveCheck($request) {
		// We set the time fields to DB format
		$convertAll = false;
		$convertTS = false;
		$convertTE = false;
		if (empty($request['stop_watch'])) {
			if($request['action'] == 'TimecontrolAjax'){
				switch ($request['fldName']) {
					case 'time_start':
						$convertTS = true;
						break;
					case 'time_end':
						$convertTE = true;
						break;
				}
			}else{
				$convertAll = true;
			}
			if (($convertAll || $convertTS) && (!empty($this->column_fields['date_start']) and !empty($this->column_fields['time_start']))) {
				$dt = new DateTimeField($this->column_fields['date_start']);
				$fmtdt = $dt->convertToDBFormat($this->column_fields['date_start']);
				$time_start = DateTimeField::convertToDBTimeZone($fmtdt.' '.$this->column_fields['time_start']);
				$this->column_fields['date_start'] = $time_start->format('Y-m-d');
				$ts = $time_start->format('H:i:s');
				$this->column_fields['time_start'] = $ts;
			}
			if (($convertAll || $convertTE) && (!empty($this->column_fields['date_end']) and !empty($this->column_fields['time_end']))) {
				$dt = new DateTimeField($this->column_fields['date_end']);
				$fmtdt = $dt->convertToDBFormat($this->column_fields['date_end']);
				$time_end = DateTimeField::convertToDBTimeZone($fmtdt.' '.$this->column_fields['time_end']);
				$this->column_fields['date_end'] = $time_end->format('Y-m-d');
				$te = $time_end->format('H:i:s');
				$this->column_fields['time_end'] = $te;
			}
		}
	}

	function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id,$module);
		}
		$this->updateTimesheetTotalTime();
		$this->updateRelatedEntities($this->id);
		if (!empty($this->column_fields['relatedto'])) {
			$relmod=getSalesEntityType($this->column_fields['relatedto']);
			$seqfld=getModuleSequenceField($relmod);
			$relm = CRMEntity::getInstance($relmod);
			$relm->retrieve_entity_info($this->column_fields['relatedto'], $relmod);
			$enum=$relm->column_fields[$seqfld['column']];
			$ename=getEntityName($relmod, array($this->column_fields['relatedto']));
			$ename=decode_html($ename[$this->column_fields['relatedto']]);
			$this->db->pquery('update vtiger_timecontrol set relatednum=?, relatedname=? where timecontrolid=?',array($enum,$ename,$this->id));
		}
	}

	/** Update totaltime field */
	function updateTimesheetTotalTime() {
	  global $adb;
	  if (!empty($this->column_fields['date_end']) && !empty($this->column_fields['time_end'])) {
	    $query = "select date_start, time_start, date_end, time_end from vtiger_timecontrol where timecontrolid={$this->id}";
	    $res = $adb->query($query);
	    $date = $adb->query_result($res, 0, 'date_start');
	    $time = $adb->query_result($res, 0, 'time_start');
	    list($year, $month, $day) = explode('-', $date);
	    list($hour, $minute) = explode(':', $time);
	    $starttime = mktime($hour, $minute, 0, $month, $day, $year);
	    $date = $adb->query_result($res, 0, 'date_end');
	    $time = $adb->query_result($res, 0, 'time_end');
	    list($year, $month, $day) = explode('-', $date);
	    list($hour, $minute) = explode(':', $time);
	    $endtime = mktime($hour, $minute, 0, $month, $day, $year);
	    $counter = round(($endtime-$starttime)/60);
	    $totaltime = str_pad(floor($counter/60), 2, '0', STR_PAD_LEFT).':'.str_pad($counter%60, 2, '0', STR_PAD_LEFT);
	    $query = "update vtiger_timecontrol set totaltime='{$totaltime}' where timecontrolid={$this->id}";
	    $adb->query($query);
	    self::update_totalday_control($this->id);
	  }
	  if (!empty($this->column_fields['totaltime']) && (empty($this->column_fields['date_end']) && empty($this->column_fields['time_end']))) {
	  	$totaltime = $this->column_fields['totaltime'];
	    if (strpos($this->column_fields['totaltime'], ':')) { // tenemos formato h:m:s, lo paso a minutos
	      $tt = explode(':', $this->column_fields['totaltime']);
	      $ttmin = $this->column_fields['totaltime'] = $tt[0]*60+$tt[1];
	    } elseif (strpos($totaltime, '.') !== false or strpos($totaltime, ',') !== false) { // tenemos formato decimal proporcional, lo paso a minutos
	      $tt = preg_split( "/[.,]/", $totaltime);
	      $mins = round(('0.'.$tt[1])*60,0);
		  $tt0 = substr('0'.$tt[0],-2);
		  if($tt[0] == '')
			$tt0 = '0';
	      $ttmin = $tt0*60+$mins;
	      $totaltime = $tt0.':'.$mins;
		} elseif (is_numeric($totaltime)){
			$ttmin = $totaltime*60;
			$totaltime = substr('0'.$totaltime,-2).':00';
		}else{
			$ttmin = 0;
			$totaltime = '00:00';
		}
		$this->column_fields['totaltime'] = $totaltime;
	    $query = "select date_start, time_start, date_end, time_end from vtiger_timecontrol where timecontrolid={$this->id}";
	    $res = $adb->query($query);
	    $date = $adb->query_result($res, 0, 'date_start');
	    $time = $adb->query_result($res, 0, 'time_start');
	    list($year, $month, $day) = explode('-', $date);
	    list($hour, $minute, $seconds) = explode(':', $time);
	    $endtime = mktime($hour, $minute+$ttmin, $seconds, $month, $day, $year);
	    $datetimefield = new DateTimeField(date('Y-m-d', $endtime));
	    $this->column_fields['date_end'] = $datetimefield->getDisplayDate();
	    $this->column_fields['time_end'] = date('H:i:s', $endtime);
	    $query = "update vtiger_timecontrol set totaltime='{$totaltime}', date_end='".date('Y-m-d', $endtime)."', time_end='{$this->column_fields['time_end']}' where timecontrolid={$this->id}";
	    $adb->query($query);
	    self::update_totalday_control($this->id);
	  }
	}

	public static function update_totalday_control($tcid) {
		global $adb,$log;
		if (self::totalday_control_installed()) {
			$tcdat=$adb->query("select date_start, smownerid
					from vtiger_timecontrol
					inner join vtiger_crmentity on crmid=timecontrolid
					where crmid=".$tcid);
			$workdate=$adb->query_result($tcdat,0,'date_start');
			$user    =$adb->query_result($tcdat,0,'smownerid');
			$tctot=$adb->query("select coalesce(sum(time_to_sec(totaltime))/3600,0) as totnum, coalesce(sec_to_time(sum(time_to_sec(totaltime))),0) as tottime
					from vtiger_timecontrol
					inner join vtiger_crmentity on crmid=timecontrolid
					where date_start='$workdate' and smownerid=$user and deleted=0");
			$totnum=$adb->query_result($tctot,0,'totnum');
			$tottim=$adb->query_result($tctot,0,'tottime');
			$adb->query("update vtiger_timecontrol
					 inner join vtiger_crmentity on crmid=timecontrolid
					 set totaldayhours=$totnum,totaldaytime='$tottim'
					 where date_start='$workdate' and smownerid=$user");
		}
		
	}

	public static function totalday_control_installed() {
		global $adb;
		$cnacc=$adb->getColumnNames('vtiger_timecontrol');
		if (in_array('totaldaytime', $cnacc)
		and in_array('totaldayhours', $cnacc)) return true;
		return false;
	}

	/**     Update Related Entities   */
	function updateRelatedEntities($tcid) {
		global $adb;
		$relid=$adb->getone("select relatedto from vtiger_timecontrol where timecontrolid=$tcid");
		if (empty($relid)) return true;
		if ($this->sumup_HelpDesk and getSalesEntityType($relid)=='HelpDesk') {
			$query = "select sum(time_to_sec(totaltime))/3600 as stt
			 from vtiger_timecontrol
			 inner join vtiger_crmentity on crmid=timecontrolid
			 where relatedto=$relid and deleted=0";
			$res = $adb->query($query);
			$stt = $adb->query_result($res, 0, 'stt');
			$query = 'update vtiger_troubletickets set hours=? where ticketid=?';
			$adb->pquery($query,array((empty($stt) ? 0 : $stt),$relid));
		}
		if ($this->sumup_ProjectTask and getSalesEntityType($relid)=='ProjectTask') {
			$query = "select sec_to_time(sum(time_to_sec(totaltime))) as stt
			from vtiger_timecontrol
			inner join vtiger_crmentity on crmid=timecontrolid
			where relatedto=$relid and deleted=0";
			$res = $adb->query($query);
			$stt = $adb->query_result($res, 0, 'stt');
			$query = 'update vtiger_projecttask set projecttaskhours=? where projecttaskid=?';
			$adb->pquery($query,array((empty($stt) ? 0 : $stt),$relid));
		}
	}

	function trash($module,$record) {
		global $adb;
		parent::trash($module,$record);
		self::update_totalday_control($record);
		$this->updateRelatedEntities($record);
		if (vtlib_isModuleActive('TCTotals')) {
			include_once 'modules/TCTotals/TCTotalsHandler.php';
			$tcdata=$adb->query("select smownerid,date_start,relatedto,product_id from vtiger_timecontrol inner join vtiger_crmentity on crmid=timecontrolid where timecontrolid=$record");
			$workdate=$adb->query_result($tcdata,0,'date_start');
			$tcuser=$adb->query_result($tcdata,0,'smownerid');
			$relto=$adb->query_result($tcdata,0,'relatedto');
			$pdoid=$adb->query_result($tcdata,0,'product_id');
			TCTotalsHandler::updateTotalTimeForUserOnDate($tcuser, $workdate);
			TCTotalsHandler::updateTotalTimeForRelatedTo($workdate,$relto, $pdoid);
		}
	}

	/**
	 * Apply security restriction (sharing privilege) query part for List view.
	 */
	function getListViewSecurityParameter($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		$sec_query = '';
		$tabid = getTabid($module);

		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {

				$sec_query .= " AND (vtiger_crmentity.smownerid in($current_user->id) OR vtiger_crmentity.smownerid IN
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid
						WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'
					)
					OR vtiger_crmentity.smownerid IN
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per
						WHERE userid=".$current_user->id." AND tabid=".$tabid."
					)
					OR (";

					// Build the query based on the group association of current user.
					if(sizeof($current_user_groups) > 0) {
						$sec_query .= " vtiger_groups.groupid IN (". implode(",", $current_user_groups) .") OR ";
					}
					$sec_query .= " vtiger_groups.groupid IN
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=".$current_user->id." and tabid=".$tabid."
						)";
				$sec_query .= ")
				)";
		}
		return $sec_query;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		global $adb;
		require_once('include/events/include.inc');
		include_once('vtlib/Vtiger/Module.php');
		$em = new VTEventsManager($adb);
		if($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$this->setModuleSeqNumber('configure', $modulename, 'TIME-BILLING-', '000001');
			$em->registerHandler('corebos.filter.CalendarModule.save', 'modules/Timecontrol/TCCalendarHandler.php', 'TCCalendarHandler');
			$em->registerHandler('corebos.filter.listview.render', 'modules/Timecontrol/convertTZListView.php', 'convertTZListViewOnTimecontrol');
			self::addTSRelations();
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
			$adb->query("update vtiger_field SET typeofdata='D~M', uitype=5 WHERE tablename='vtiger_timecontrol' and columnname='date_start'");
			$adb->query("update vtiger_field SET typeofdata='D~O', uitype=5 WHERE tablename='vtiger_timecontrol' and columnname='date_end'");
			$adb->query("update vtiger_field SET displaytype=1, uitype=14 WHERE tablename='vtiger_timecontrol' and columnname='time_start'");
			$adb->query("update vtiger_field SET displaytype=1, uitype=14 WHERE tablename='vtiger_timecontrol' and columnname='time_end'");
			$adb->query("update vtiger_field SET displaytype=1, typeofdata='V~O' WHERE tablename='vtiger_timecontrol' and columnname='totaltime'");
			$adb->query("ALTER TABLE vtiger_timecontrol ADD relatednum VARCHAR(255) NULL, ADD relatedname VARCHAR(255) NULL");
			$adb->query("ALTER TABLE vtiger_timecontrol CHANGE invoiced invoiced VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0'");
			$adb->query("UPDATE vtiger_timecontrol SET `invoiced`=0 WHERE invoiced!=1 or invoiced is null");
			$em->registerHandler('corebos.filter.CalendarModule.save', 'modules/Timecontrol/TCCalendarHandler.php', 'TCCalendarHandler');
			$em->registerHandler('corebos.filter.listview.render', 'modules/Timecontrol/convertTZListView.php', 'convertTZListViewOnTimecontrol');
		}
	}

	static function addTSRelations($dorel=true) {
		$Vtiger_Utils_Log = true;
		include_once('vtlib/Vtiger/Module.php');

		$module = Vtiger_Module::getInstance('Timecontrol');

		$cfgTCMods = array('Vendors','Assets','ProjectTask','ProjectMilestone','Project','Leads','Accounts','Contacts',
				'Campaigns','Potentials','Invoice','PurchaseOrder','SalesOrder','Quotes','HelpDesk','Services','Products',
				'ServiceContracts');
		foreach ($cfgTCMods as $tcmod) {
			$rtcModule = Vtiger_Module::getInstance($tcmod);
			$rtcModule->setRelatedList($module, 'Timecontrol', Array('ADD'), 'get_dependents_list');
			$rtcModule->addLink('DETAILVIEWBASIC', 'Timecontrol', 'index.php?module=Timecontrol&action=EditView&relatedto=$RECORD$','modules/Timecontrol/images/stopwatch.gif');
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
