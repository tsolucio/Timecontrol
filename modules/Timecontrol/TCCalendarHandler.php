<?php
/*************************************************************************************************
 * Copyright 2015 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
 * Licensed under the vtiger CRM Public License Version 1.1 (the "License"); you may not use this
 * file except in compliance with the License. You can redistribute it and/or modify it
 * under the terms of the License. JPL TSolucio, S.L. reserves all rights not expressly
 * granted by the License. coreBOS distributed by JPL TSolucio S.L. is distributed in
 * the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Unless required by
 * applicable law or agreed to in writing, software distributed under the License is
 * distributed on an "AS IS" BASIS, WITHOUT ANY WARRANTIES OR CONDITIONS OF ANY KIND,
 * either express or implied. See the License for the specific language governing
 * permissions and limitations under the License. You may obtain a copy of the License
 * at <http://corebos.org/documentation/doku.php?id=en:devel:vpl11>
 *************************************************************************************************
 *  Module       : coreBOS Timecontrol Calendar Events Handler
 *  Version      : 1.0
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************/
class TCCalendarHandler extends VTEventHandler {

	/**
	 * @param $handlerType
	 * @param $entityData VTEntityData
	 */
	public function handleEvent($handlerType, $entityData) {
	}

	public function handleFilter($handlerType, $parameter) {
		global $current_user;
		if (isset($parameter[0]['record']) && !empty($parameter[0]['record'])) {
			$setype = getSalesEntityType($parameter[0]['record']);
			if ($setype == 'Timecontrol') {
				if ($handlerType=='corebos.filter.CalendarModule.save') {
					if ($parameter[0]['mode'] == 'event_drop' || $parameter[0]['mode'] == 'event_resize') {
						require_once 'modules/Timecontrol/Timecontrol.php';
						$focus = new Timecontrol();
						$focus->retrieve_entity_info($parameter[0]['record'], 'Timecontrol');
						foreach ($focus->column_fields as $fieldname => $val) {
							$focus->column_fields[$fieldname] = decode_html($focus->column_fields[$fieldname]);
						}
						$focus->column_fields['description'] = decode_html($focus->column_fields['description']);
						if ($focus->column_fields['date_end']=='' && $focus->column_fields['time_end']=='') {
							$focus->column_fields['date_end'] = $focus->column_fields['date_start'];
							$focus->column_fields['time_end'] = $focus->column_fields['time_start'];
						}
						list($y,$m,$d) = explode('-', $focus->column_fields['date_end']);
						list($h,$i,$s) = explode(':', $focus->column_fields['time_end']);
						$t = mktime($h, $i+$parameter[0]['minute'], $s, $m, $d+$parameter[0]['day'], $y);
						$date = new DateTimeField(date('Y-m-d H:i:s', $t));
						$focus->column_fields['date_end'] = $date->getDisplayDate($current_user);
						$focus->column_fields['time_end'] = $date->getDisplayTime($current_user);
						if ($parameter[0]['mode'] == 'event_drop') {
							list($y,$m,$d) = explode('-', $focus->column_fields['date_start']);
							list($h,$i,$s) = explode(':', $focus->column_fields['time_start']);
							$t = mktime($h, $i+$parameter[0]['minute'], $s, $m, $d+$parameter[0]['day'], $y);
							$date = new DateTimeField(date('Y-m-d H:i:s', $t));
							$focus->column_fields['date_start'] = $date->getDisplayDate($current_user);
							$focus->column_fields['time_start'] = $date->getDisplayTime($current_user);
						}
						$focus->mode = 'edit';
						$focus->id  = $parameter[0]['record'];
						$_REQUEST['assigntype'] = 'U';
						$focus->save('Timecontrol');
					}
				}
				$parameter[1] = true; // processed
			}
		}
		return $parameter;
	}
}
