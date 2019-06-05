<?php
/*************************************************************************************************
 * Copyright 2016 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS customizations.
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
 *  Module       : Timecontrol Timezone support
 *  Version      : 1.0
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************/
class convertTZListViewOnTimecontrol extends VTEventHandler {

	public function handleEvent($handlerType, $entityData) {
	}

	public function handleFilter($handlerType, $parameter) {
		global $adb;
		if (getSalesEntityType($parameter[2])=='Timecontrol' && $handlerType=='corebos.filter.listview.render') {
			// 0 -> Row
			// 1 -> complete Data from Query
			// 2 -> recordID
			$tspos = $tepos = $depos = $dspos = -1;
			for ($cpos=0; $cpos<count($parameter[0])-1; $cpos++) {
				if (strpos($parameter[0][$cpos], "vtfieldname='date_start'")>0) {
					$dspos = $cpos;
				}
				if (strpos($parameter[0][$cpos], "vtfieldname='time_start'")>0) {
					$tspos = $cpos;
				}
				if (strpos($parameter[0][$cpos], "vtfieldname='date_end'")>0) {
					$depos = $cpos;
				}
				if (strpos($parameter[0][$cpos], "vtfieldname='time_end'")>0) {
					$tepos = $cpos;
				}
			}
			if ($dspos!=-1 && !empty($parameter[1]['date_start'])) {
				if (!isset($parameter[1]['time_start'])) {
					$tkrs = $adb->pquery('select time_start from vtiger_timecontrol where timecontrolid=?', array($parameter[2]));
					$parameter[1]['time_start'] = $adb->query_result($tkrs, 0, 'time_start');
				}
				$time_start = DateTimeField::convertToUserTimeZone($parameter[1]['date_start'].' '.DateTimeField::sanitizeTime($parameter[1]['time_start']));
				$ds = $time_start->format('Y-m-d');
				$display_ds = DateTimeField::convertToUserFormat($ds);
				$parameter[0][$dspos] = $display_ds . substr($parameter[0][$dspos], strpos($parameter[0][$dspos], ' <span'));
			}
			if ($tspos!=-1 && !empty($parameter[1]['time_start'])) {
				if (!isset($parameter[1]['date_start'])) {
					$tkrs = $adb->pquery('select date_start,date_end from vtiger_timecontrol where timecontrolid=?', array($parameter[2]));
					$parameter[1]['date_start'] = $adb->query_result($tkrs, 0, 'date_start');
					$parameter[1]['date_end'] = $adb->query_result($tkrs, 0, 'date_end');
				}
				$time_start = DateTimeField::convertToUserTimeZone($parameter[1]['date_start'].' '.DateTimeField::sanitizeTime($parameter[1]['time_start']));
				$ts = $time_start->format('H:i:s');
				$parameter[0][$tspos] = $ts . substr($parameter[0][$tspos], strpos($parameter[0][$tspos], ' <span'));
			}
			if ($depos!=-1 && !empty($parameter[1]['date_end'])) {
				if (!isset($parameter[1]['time_end'])) {
					$tkrs = $adb->pquery('select time_end from vtiger_timecontrol where timecontrolid=?', array($parameter[2]));
					$parameter[1]['time_end'] = $adb->query_result($tkrs, 0, 'time_end');
				}
				$time_end = DateTimeField::convertToUserTimeZone($parameter[1]['date_end'].' '.DateTimeField::sanitizeTime($parameter[1]['time_end']));
				$de = $time_end->format('Y-m-d');
				$display_de = DateTimeField::convertToUserFormat($de);
				$parameter[0][$depos] = $display_de . substr($parameter[0][$depos], strpos($parameter[0][$depos], ' <span'));
			}
			if ($tepos!=-1 && !empty($parameter[1]['time_end'])) {
				if (!isset($parameter[1]['date_end'])) {
					$tkrs = $adb->pquery('select date_end from vtiger_timecontrol where timecontrolid=?', array($parameter[2]));
					$parameter[1]['date_end'] = $adb->query_result($tkrs, 0, 'date_end');
				}
				$time_start = DateTimeField::convertToUserTimeZone($parameter[1]['date_end'].' '.DateTimeField::sanitizeTime($parameter[1]['time_end']));
				$ts = $time_start->format('H:i:s');
				$parameter[0][$tepos] = $ts . substr($parameter[0][$tepos], strpos($parameter[0][$tepos], ' <span'));
			}
		}
		return $parameter;
	}
}
