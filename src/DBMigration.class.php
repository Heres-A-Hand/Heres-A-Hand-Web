<?php
/**
 *  Copyright 2011-2013 Here's A Hand Limited
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 * 
 * @copyright 2011-2013 Here's A Hand Limited
 * @license Apache License, Version 2.0
**/


class DBMigration {
	

	private $id;
	private $sql;
	private $applied = false;


	public function __construct($id=null, $sql=null) {
		$this->id = $id;
		$this->sql = $sql;
	}
	
	public function getId() { return $this->id; }
	public function getApplied() { return $this->applied; }
	public function setIsApplied() { $this->applied = true; }

	public  function performMigration(PDO $db) {
		foreach(explode(";", $this->sql) as $line) {
			if (trim($line)) {
				$db->query($line.';');
			}
		}
	}
	
	public function getIdAsUnixTimeStamp() {
		$year = substr($this->id, 0, 4);
		$month = substr($this->id, 4, 2);
		$day = substr($this->id, 6, 2);
		$hour = substr($this->id, 8, 2);
		$min = substr($this->id, 10, 2);
		$sec = substr($this->id, 12, 2);
		return mktime($hour,$min,$sec,$month,$day,$year);
	}
	
}
