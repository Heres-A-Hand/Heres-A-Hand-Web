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


class RequestType {

	private $id;
	private $title;
	private $is_active;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['title'])) $this->title = $data['title'];
		if (isset($data['is_active'])) $this->is_active = $data['is_active'];
	}

	/** @return RequestType **/
	public static function findByIDwithinSupportGroup($id,SupportGroup $supportGroup) {
		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type WHERE id = :id AND support_group_id = :sid");
		$s->execute(array('id'=>$id,'sid'=>$supportGroup->getId()));
		if ($s->rowCount() == 1) {
			return new RequestType($s->fetch());
		}
	}



	/** @return RequestType **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type WHERE id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new RequestType($s->fetch());
		}
	}


	public function getId() { return $this->id; }
	public function getTitle() { return $this->title; }
	public function isActive() { return $this->is_active; }


	public function enable() {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->is_active = true;

		$db = getDB();
		$s = $db->prepare("UPDATE request_type SET is_active = true WHERE id = :id");
		$s->execute(array('id'=>$this->id));
		logInfo("Enabling RequestType:".$this->id);
	}

	public function disable() {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->is_active = false;

		$db = getDB();
		$s = $db->prepare("UPDATE request_type SET is_active = false WHERE id = :id");
		$s->execute(array('id'=>$this->id));
		logInfo("Disabling RequestType:".$this->id);
	}
	
	public function getSimpleScheduleRuleForUser(UserAccount $userAccount) {
		if (is_null($this->id)) throw new Exception ('No Loaded');
		$db = getDB();
		$s = $db->prepare("SELECT send FROM simple_schedule_request_type_rule WHERE user_account_id=:uid AND request_type_id=:rtid");
		$s->execute(array('uid'=>$userAccount->getId(),'rtid'=>$this->getId()));
		if ($s->rowCount() == 1) {
			$d = $s->fetch();
			return $d['send'];
		} else {
			return true;
		}
	}
	
	public function setSimpleScheduleRuleForUser(UserAccount $userAccount, $newValue) {
		if (is_null($this->id)) throw new Exception ('No Loaded');
		$db = getDB();
		$s = $db->prepare("SELECT send FROM simple_schedule_request_type_rule WHERE user_account_id=:uid AND request_type_id=:rtid");
		$s->execute(array('uid'=>$userAccount->getId(),'rtid'=>$this->getId()));
		if ($s->rowCount() == 1) {
			$d = $s->fetch();
			$s = $db->prepare("UPDATE simple_schedule_request_type_rule SET send=:s WHERE user_account_id=:uid AND request_type_id=:rtid");
			$s->execute(array('uid'=>$userAccount->getId(),'rtid'=>$this->getId(),'s'=>($newValue?'t':'f')));
		} else {
			$s = $db->prepare("INSERT INTO simple_schedule_request_type_rule (send, user_account_id, request_type_id) VALUES (:s, :uid, :rtid)");
			$s->execute(array('uid'=>$userAccount->getId(),'rtid'=>$this->getId(),'s'=>($newValue?'t':'f')));
		}		
	}

}
