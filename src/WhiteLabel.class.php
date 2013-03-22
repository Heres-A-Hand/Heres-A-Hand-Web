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




class WhiteLabel {

	private $id;
	private $title;
	
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['title'])) $this->title = $data['title'];
	}


	public static function create($title) {
		$db = getDB();
		$s = $db->prepare("INSERT INTO white_label (title) VALUES(:t) RETURNING id");
		$s->execute(array('t'=>$title));
		$d = $s->fetch();
		logInfo("Creating  WhiteLabel:".$d['id']);
		return new WhiteLabel(array('id'=>$d['id'],'title'=>$title));
	}

	/** @return WhiteLabel **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT white_label.* FROM white_label WHERE id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new WhiteLabel($s->fetch());
		}
	}
	
	public function getId() { return $this->id; }
	public function getTitle() { return $this->title; }
	

	/** @return Boolean whether added or not; true is yes, false if no. Only reason for false is they are already here **/
	public function addAdmin(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No W L Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT * FROM user_admins_white_label WHERE user_account_id=:ua AND white_label_id=:wl");
		$s->execute(array(
				'ua'=>$ua->getId(),
				'wl'=>$this->id,
			));
		if ($s->rowCount() == 0) {
			$s = $db->prepare("INSERT INTO user_admins_white_label (user_account_id,white_label_id,created_at) ".
				"VALUES(:ua,:wl,:created_at)");
			$s->execute(array(
					'ua'=>$ua->getId(),
					'wl'=>$this->id,
					'created_at'=>date("Y-m-d H:i:s", getCurrentTime()),
				));
			logInfo("Adding Admin User:".$ua->getId()." to WhiteLabel:".$this->id);
			return true;
		} else {
			return false;
		}
	}

	public function removeAdmin(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No W L Loaded');

		// TODO should me be setting some column rather than deleteing so we have more history in the DB?

		$db = getDB();
		$s = $db->prepare("DELETE FROM user_admins_white_label WHERE user_account_id=:ua AND white_label_id=:wl");
		$s->execute(array(
				'ua'=>$ua->getId(),
				'wl'=>$this->id
			));
		logInfo("Removing Admin User:".$ua->getId()." from WhiteLabel:".$this->id);
	}
	
	
}

