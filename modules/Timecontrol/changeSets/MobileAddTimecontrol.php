<?php
/*************************************************************************************************
 * Copyright 2016 JPL TSolucio, S.L. -- This file is a part of TSOLUCIO coreBOS Customizations.
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
*************************************************************************************************/

class MobileAddTimecontrol extends cbupdaterWorker {

	public function applyChange() {
		if ($this->hasError()) {
			$this->sendError();
		}
		if ($this->isApplied()) {
			$this->sendMsg('Changeset '.get_class($this).' already applied!');
		} else {
			global $adb;

			$result = $adb->pquery("SELECT crmtogo_module FROM berli_crmtogo_modules WHERE crmtogo_module = 'Timecontrol'", array());

			if ($adb->num_rows($result) == 0) {
				$modulename='Timecontrol';
				$crmtogo_active = 1;
				if (!vtlib_isModuleActive($modulename)) {
					$crmtogo_active = 0;
				}
				$result = $adb->pquery('SELECT id FROM vtiger_users', array());
				while ($row = $adb->fetch_array($result)) {
					$res_seq = $adb->pquery('SELECT coalesce(MAX(order_num), 0) as seq FROM berli_crmtogo_modules WHERE crmtogo_user=?', array($row['id']));
					$seq = (int)$adb->query_result($res_seq, 0, 'seq') + 1;
					if ($seq > 1) {
						$this->ExecuteQuery(
							'INSERT INTO `berli_crmtogo_modules` (`crmtogo_user`, `crmtogo_module`, `crmtogo_active`, `order_num`) VALUES (?, ?, ?, ?)',
							array($row['id'], $modulename, $crmtogo_active, $seq)
						);
					}
				}
			}
			$this->sendMsg('Changeset '.get_class($this).' applied!');
			$this->markApplied(false);
		}
		$this->finishExecution();
	}
}
