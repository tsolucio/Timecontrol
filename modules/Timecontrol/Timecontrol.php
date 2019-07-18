<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class Timecontrol extends CRMEntity {
	public $db;
	public $log;

	// Variable to esablish start value on resume
	// true: dates and start time will be set to "now"
	// false: only start time will be set to "now"
	public static $now_on_resume=true;
	public $sumup_HelpDesk = true;
	public $sumup_ProjectTask = true;

	public $table_name = 'vtiger_timecontrol';
	public $table_index= 'timecontrolid';
	public $column_fields = array();
	public $moduleIcon = array('library' => 'utility', 'containerClass' => 'slds-icon_container slds-icon-standard-user', 'class' => 'slds-icon', 'icon'=>'clock');

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_timecontrolcf', 'timecontrolid');
	public $related_tables = array('vtiger_timecontrolcf'=>array('timecontrolid','vtiger_timecontrol', 'timecontrolid','Timecontrol'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_timecontrol', 'vtiger_timecontrolcf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_timecontrol'   => 'timecontrolid',
		'vtiger_timecontrolcf' => 'timecontrolid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array (
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Timecontrol Number' => array('timecontrol' => 'timecontrolnr'),
		'Title'=> array('timecontrol' => 'title'),
		'Date Start' => array('timecontrol' => 'date_start'),
		'Time Start' => array('timecontrol' => 'time_start'),
		'Total Time' => array('timecontrol' => 'totaltime'),
		'Description' => array('crmentity' => 'description'),
		'Assigned To' => array('crmentity' => 'smownerid')
	);
	public $list_fields_name = array(
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
	public $list_link_field = 'timecontrolnr';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Timecontrol Number' => array('timecontrol' => 'timecontrolnr'),
		'Title'=> array('timecontrol' => 'title')
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'Timecontrol Number' => 'timecontrolnr',
		'Title'=> 'title'
	);

	// For Popup window record selection
	public $popup_fields = array('timecontrolnr');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'timecontrolnr';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'title';

	// Required Information for enabling Import feature
	public $required_fields = array();

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'date_start';
	public $default_sort_order='DESC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('createdtime', 'modifiedtime', 'timecontrolnr', 'date_start', 'time_start');

	public function standarizetimefields() {
		// we format the time fields depending on the current user's timezone
		if (!empty($this->column_fields['date_start']) && !empty($this->column_fields['time_start'])) {
			$time_start = DateTimeField::convertToUserTimeZone($this->column_fields['date_start'].' '.DateTimeField::sanitizeTime($this->column_fields['time_start']));
			$this->column_fields['date_start'] = $time_start->format('Y-m-d');
			$ts = $time_start->format('H:i:s');
			$this->column_fields['time_start'] = $ts;
		}
		if (!empty($this->column_fields['date_end']) && !empty($this->column_fields['time_end'])) {
			$time_end = DateTimeField::convertToUserTimeZone($this->column_fields['date_end'].' '.DateTimeField::sanitizeTime($this->column_fields['time_end']));
			$this->column_fields['date_end'] = $time_end->format('Y-m-d');
			$te = $time_end->format('H:i:s');
			$this->column_fields['time_end'] = $te;
		}
	}

	public function preEditCheck($request, $smarty) {
		$this->standarizetimefields();
	}

	public function preViewCheck($request, $smarty) {
		$this->standarizetimefields();
	}

	public function formatValueForReport($dbField, $fieldType, $value, $fieldvalue, $crmid) {
		global $adb;
		if (!empty($crmid)) {
			if (isset($dbField->orgname) && $dbField->orgname == 'time_end') {
				$tc = $adb->pquery('select date_end,time_end from vtiger_timecontrol where timecontrolid=?', array($crmid));
				if ($tc && $adb->num_rows($tc)>0) {
					$dt = $adb->query_result($tc, 0, 'date_end');
					$ts = $adb->query_result($tc, 0, 'time_end');
					$time_start = DateTimeField::convertToUserTimeZone($dt.' '.DateTimeField::sanitizeTime($ts));
					return $time_start->format('H:i:s');
				}
			} elseif (isset($dbField->orgname) && $dbField->orgname == 'time_start') {
				$tc = $adb->pquery('select date_start,time_start from vtiger_timecontrol where timecontrolid=?', array($crmid));
				if ($tc && $adb->num_rows($tc)>0) {
					$dt = $adb->query_result($tc, 0, 'date_start');
					$ts = $adb->query_result($tc, 0, 'time_start');
					$time_start = DateTimeField::convertToUserTimeZone($dt.' '.DateTimeField::sanitizeTime($ts));
					return $time_start->format('H:i:s');
				}
			}
		}
		return $value;
	}

	public function preSaveCheck($request) {
		// We set the time fields to DB format
		$convertAll = false;
		$convertTS = false;
		$convertTE = false;
		if (empty($request['stop_watch'])) {
			if ($request['action'] == 'TimecontrolAjax') {
				switch ($request['fldName']) {
					case 'time_start':
						$convertTS = true;
						break;
					case 'time_end':
						$convertTE = true;
						break;
				}
			} else {
				$convertAll = true;
			}
			if (($convertAll || $convertTS) && (!empty($this->column_fields['date_start']) && !empty($this->column_fields['time_start']))) {
				$dt = new DateTimeField($this->column_fields['date_start']);
				$fmtdt = $dt->convertToDBFormat($this->column_fields['date_start']);
				$time_start = DateTimeField::convertToDBTimeZone($fmtdt.' '.$this->column_fields['time_start']);
				$this->column_fields['date_start'] = $time_start->format('Y-m-d');
				$ts = $time_start->format('H:i:s');
				$this->column_fields['time_start'] = $ts;
			}
			if (($convertAll || $convertTE) && (!empty($this->column_fields['date_end']) && !empty($this->column_fields['time_end']))) {
				$dt = new DateTimeField($this->column_fields['date_end']);
				$fmtdt = $dt->convertToDBFormat($this->column_fields['date_end']);
				$time_end = DateTimeField::convertToDBTimeZone($fmtdt.' '.$this->column_fields['time_end']);
				$this->column_fields['date_end'] = $time_end->format('Y-m-d');
				$te = $time_end->format('H:i:s');
				$this->column_fields['time_end'] = $te;
			}
		}
	}

	public function save_module($module) {
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
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
			$this->db->pquery('update vtiger_timecontrol set relatednum=?, relatedname=? where timecontrolid=?', array($enum,$ename,$this->id));
		}
	}

	/** Update totaltime field */
	public function updateTimesheetTotalTime() {
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
			} elseif (strpos($totaltime, '.') !== false || strpos($totaltime, ',') !== false) { // tenemos formato decimal proporcional, lo paso a minutos
				$tt = preg_split("/[.,]/", $totaltime);
				$mins = round(('0.'.$tt[1])*60, 0);
				$tt0 = substr('0'.$tt[0], -2);
				if ($tt[0] == '') {
					$tt0 = '0';
				}
				$ttmin = $tt0*60+$mins;
				$totaltime = $tt0.':'.$mins;
			} elseif (is_numeric($totaltime)) {
				$ttmin = $totaltime*60;
				$totaltime = substr('0'.$totaltime, -2).':00';
			} else {
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
			$adb->pquery(
				'update vtiger_timecontrol set totaltime=?, date_end=?, time_end=? where timecontrolid=?',
				array($totaltime, date('Y-m-d', $endtime), $this->column_fields['time_end'], $this->id)
			);
			self::update_totalday_control($this->id);
		}
	}

	public static function update_totalday_control($tcid) {
		global $adb;
		if (self::totalday_control_installed()) {
			$tcdat=$adb->query("select date_start, smownerid
					from vtiger_timecontrol
					inner join vtiger_crmentity on crmid=timecontrolid
					where crmid=".$tcid);
			$workdate=$adb->query_result($tcdat, 0, 'date_start');
			$user    =$adb->query_result($tcdat, 0, 'smownerid');
			$tctot=$adb->query("select coalesce(sum(time_to_sec(totaltime))/3600,0) as totnum, coalesce(sec_to_time(sum(time_to_sec(totaltime))),0) as tottime
					from vtiger_timecontrol
					inner join vtiger_crmentity on crmid=timecontrolid
					where date_start='$workdate' and smownerid=$user and deleted=0");
			$totnum=$adb->query_result($tctot, 0, 'totnum');
			$tottim=$adb->query_result($tctot, 0, 'tottime');
			$adb->query("update vtiger_timecontrol
					 inner join vtiger_crmentity on crmid=timecontrolid
					 set totaldayhours=$totnum,totaldaytime='$tottim'
					 where date_start='$workdate' and smownerid=$user");
		}
	}

	public static function totalday_control_installed() {
		global $adb;
		$cnacc=$adb->getColumnNames('vtiger_timecontrol');
		return (in_array('totaldaytime', $cnacc) && in_array('totaldayhours', $cnacc));
	}

	/** Update Related Entities */
	public function updateRelatedEntities($tcid) {
		global $adb;
		$relid=$adb->getone("select relatedto from vtiger_timecontrol where timecontrolid=$tcid");
		if (empty($relid)) {
			return true;
		}
		if ($this->sumup_HelpDesk && getSalesEntityType($relid)=='HelpDesk') {
			$query = "select sum(time_to_sec(totaltime))/3600 as stt
			 from vtiger_timecontrol
			 inner join vtiger_crmentity on crmid=timecontrolid
			 where relatedto=$relid and deleted=0";
			$res = $adb->query($query);
			$stt = $adb->query_result($res, 0, 'stt');
			$query = 'update vtiger_troubletickets set hours=? where ticketid=?';
			$adb->pquery($query, array((empty($stt) ? 0 : $stt), $relid));
		}
		if ($this->sumup_ProjectTask && getSalesEntityType($relid)=='ProjectTask') {
			$query = "select sec_to_time(sum(time_to_sec(totaltime))) as stt
			from vtiger_timecontrol
			inner join vtiger_crmentity on crmid=timecontrolid
			where relatedto=$relid and deleted=0";
			$res = $adb->query($query);
			$stt = $adb->query_result($res, 0, 'stt');
			$query = 'update vtiger_projecttask set projecttaskhours=? where projecttaskid=?';
			$adb->pquery($query, array((empty($stt) ? 0 : $stt), $relid));
		}
	}

	public function trash($module, $record) {
		global $adb;
		parent::trash($module, $record);
		self::update_totalday_control($record);
		$this->updateRelatedEntities($record);
		if (vtlib_isModuleActive('TCTotals')) {
			include_once 'modules/TCTotals/TCTotalsHandler.php';
			$tcdata=$adb->query("select smownerid,date_start,relatedto,product_id from vtiger_timecontrol inner join vtiger_crmentity on crmid=timecontrolid where timecontrolid=$record");
			$workdate=$adb->query_result($tcdata, 0, 'date_start');
			$tcuser=$adb->query_result($tcdata, 0, 'smownerid');
			// $relto=$adb->query_result($tcdata, 0, 'relatedto');
			// $pdoid=$adb->query_result($tcdata, 0, 'product_id');
			TCTotalsHandler::updateTotalTimeForUserOnDate($tcuser, $workdate);
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		global $adb;
		require_once 'include/events/include.inc';
		include_once 'vtlib/Vtiger/Module.php';
		$em = new VTEventsManager($adb);
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			$this->setModuleSeqNumber('configure', $modulename, 'TIME-BILLING-', '000001');
			$em->registerHandler('corebos.filter.CalendarModule.save', 'modules/Timecontrol/TCCalendarHandler.php', 'TCCalendarHandler');
			$em->registerHandler('corebos.filter.listview.render', 'modules/Timecontrol/convertTZListView.php', 'convertTZListViewOnTimecontrol');
			self::addTSRelations();
			global $adb;
			$result = $adb->pquery("SELECT crmtogo_module FROM berli_crmtogo_modules WHERE crmtogo_module = 'Timecontrol'", array());
			if ($adb->num_rows($result) == 0) {
				$result = $adb->pquery('SELECT id FROM vtiger_users', array());
				while ($row = $adb->fetch_array($result)) {
					$res_seq = $adb->pquery('SELECT coalesce(MAX(order_num), 0) as seq FROM berli_crmtogo_modules WHERE crmtogo_user=?', array($row['id']));
					$seq = (int)$adb->query_result($res_seq, 0, 'seq') + 1;
					if ($seq > 1) {
						$adb->pquery(
							'INSERT INTO `berli_crmtogo_modules` (`crmtogo_user`, `crmtogo_module`, `crmtogo_active`, `order_num`) VALUES (?, ?, ?, ?)',
							array($row['id'], $modulename, 1, $seq)
						);
					}
				}
			}
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
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

	public static function addTSRelations($dorel = true) {
		$Vtiger_Utils_Log = true;
		include_once 'vtlib/Vtiger/Module.php';

		$module = Vtiger_Module::getInstance('Timecontrol');

		$cfgTCMods = array('Vendors','Assets','ProjectTask','ProjectMilestone','Project','Leads','Accounts','Contacts',
			'Campaigns','Potentials','Invoice','PurchaseOrder','SalesOrder','Quotes','HelpDesk','Services','Products',
			'ServiceContracts');
		foreach ($cfgTCMods as $tcmod) {
			$rtcModule = Vtiger_Module::getInstance($tcmod);
			$rtcModule->setRelatedList($module, 'Timecontrol', array('ADD'), 'get_dependents_list');
			$rtcModule->addLink(
				'DETAILVIEWBASIC',
				'Timecontrol',
				'index.php?module=Timecontrol&action=EditView&createmode=link&return_id=$RECORD$&return_action=DetailView&return_module=$MODULE$&cbfromid=$RECORD$&relatedto=$RECORD$',
				'modules/Timecontrol/images/stopwatch.gif'
			);
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// public function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
