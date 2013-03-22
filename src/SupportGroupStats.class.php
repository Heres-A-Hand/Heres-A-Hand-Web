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



class SupportGroupStats {

	private $supportGroup;
	private $start;
	private $end;
	
	public function __construct(SupportGroup $supportGroup, $start, $end) {
		$this->supportGroup = $supportGroup;
		$this->start = $start;
		$this->end = $end;
	}
	
	public function getStart() { return $this->start; }
	public function getEnd() { return $this->end; }

	public function getNumberRequestsCreated() {
		$db = getDB();
		$stat = $db->prepare("SELECT count(*) AS c FROM request WHERE support_group_id=:id AND created_at >= :start AND created_at < :end");
		$stat->execute(array(
				'id'=>$this->supportGroup->getId(), 
				'start'=>date("Y-m-d H:i:s",$this->start) , 
				'end'=>date("Y-m-d H:i:s",$this->end)
			));
		$d = $stat->fetch();
		return $d['c'];
	}
	

	public function getNumberRequestsClosed() {
		$db = getDB();
		$stat = $db->prepare("SELECT count(*) AS c FROM request WHERE support_group_id=:id AND closed_at >= :start AND closed_at < :end");
		$stat->execute(array(
				'id'=>$this->supportGroup->getId(), 
				'start'=>date("Y-m-d H:i:s",$this->start) , 
				'end'=>date("Y-m-d H:i:s",$this->end)
			));
		$d = $stat->fetch();
		return $d['c'];
	}

	public function getNumberRequestsCancelled() {
		$db = getDB();
		$stat = $db->prepare("SELECT count(*) AS c FROM request WHERE support_group_id=:id AND cancelled_at >= :start AND cancelled_at < :end");
		$stat->execute(array(
				'id'=>$this->supportGroup->getId(), 
				'start'=>date("Y-m-d H:i:s",$this->start) , 
				'end'=>date("Y-m-d H:i:s",$this->end)
			));
		$d = $stat->fetch();
		return $d['c'];
	}
	
}



