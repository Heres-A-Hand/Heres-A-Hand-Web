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

abstract class BaseUserContactMethod {
	
	protected $id;
	protected $title;
	protected $user_account_id;	
	protected $created_at;
	protected $simple_schedule_on_days;
	protected $simple_schedule_from_time;
	protected $simple_schedule_to_time;	

	protected $tableName;
	
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['title'])) $this->title = $data['title'];
		if (isset($data['user_account_id'])) $this->user_account_id = $data['user_account_id'];
		if (isset($data['created_at'])) $this->created_at = strtotime($data['created_at']);
		if (isset($data['simple_schedule_from_time'])) $this->simple_schedule_from_time = $data['simple_schedule_from_time'];
		if (isset($data['simple_schedule_to_time'])) $this->simple_schedule_to_time = $data['simple_schedule_to_time'];
		if (isset($data['simple_schedule_on_days'])) $this->simple_schedule_on_days = explode(",",$data['simple_schedule_on_days']);		
	}

	public function getId() { return $this->id; }
	public function getTitle() { return $this->title; }
	public function getCreatedAt() { return $this->created_at; }

	public function getUserAccount() {
		return UserAccount::findByID($this->user_account_id);
	}
	
	public function getSimpleScheduleDays() { return $this->simple_schedule_on_days; }
	public function hasSimpleScheduleDay($day) { return in_array($day, $this->simple_schedule_on_days); }
	public function getSimpleScheduleFrom() { return $this->simple_schedule_from_time; }
	public function getSimpleScheduleTo() { return $this->simple_schedule_to_time; }
	
	public function getSimpleScheduleFromHour() { return substr($this->simple_schedule_from_time,0,2); }
	public function getSimpleScheduleToHour() { return substr($this->simple_schedule_to_time,0,2); }
	public function getSimpleScheduleFromMin() { return substr($this->simple_schedule_from_time,3); }
	public function getSimpleScheduleToMin() { return substr($this->simple_schedule_to_time,3); }
	
	public function updateSimpleSchedule($days, $fromHours, $fromMins, $toHours, $toMins) {
		$from = $fromHours.":".$fromMins;
		$to = $toHours.":".$toMins;
		if (is_array($days)) $days = implode(",", $days);
		$db = getDB();
		$s = $db->prepare("UPDATE ".$this->tableName." SET simple_schedule_from_time=:ft, simple_schedule_to_time=:tt, simple_schedule_on_days=:d WHERE id=:id ");
		$s->execute(array('ft'=>$from, 'tt'=>$to, 'd'=>$days, 'id'=>$this->id));
	}
	
	public function doesSimpleScheduleRuleMatch($timestamp) {
		$day = strtolower(date("D",getCurrentTime()));
		if (!$this->hasSimpleScheduleDay($day)) return false;
		
		$hour = date("G",  getCurrentTime());
		$mins = date("i",  getCurrentTime());
		
		$start = $this->getSimpleScheduleFromHour()*60+$this->getSimpleScheduleFromMin();
		$end = $this->getSimpleScheduleToHour()*60+$this->getSimpleScheduleToMin();
		$now = $hour*60+$mins;
		//print "From ".$this->getSimpleScheduleFromHour().":".$this->getSimpleScheduleFromMin()." To ".$this->getSimpleScheduleToHour().":".$this->getSimpleScheduleToMin()." Start=".$start." Now=".$now." End=".$end."\n";
		return $start <= $now && $now <= $end;
	}
	
}


