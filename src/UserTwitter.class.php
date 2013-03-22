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

class UserTwitter  extends BaseUserContactMethod {

	protected $username;

	public function  __construct($data) {
		parent::__construct($data);
		$this->tableName = 'user_twitter';
		if (isset($data['username'])) $this->username = $data['username'];
	}

	public static function findByIDForUserAccount($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_twitter.* FROM user_twitter WHERE id = :id AND user_account_id = :uid AND deleted_at IS NULL");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			return new UserTwitter($s->fetch());
		}
	}

	public static function findByUser(UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_twitter.* FROM user_twitter WHERE user_account_id = :id AND deleted_at IS NULL");
		$s->execute(array('id'=>$user->getId()));
		$out = array();
		while($d = $s->fetch()) $out[] = new UserTwitter($d);
		return $out;
	}

	public function getUserName() { return $this->username; }
	

	public function delete() {
		$db = getDB();
		$s = $db->prepare("UPDATE user_twitter SET deleted_at = :at WHERE id = :id");
		$s->execute(array('id'=>$this->id,'at'=>date('Y-m-d H:i:s', getCurrentTime())));
		logInfo("Deleted UserTwitter:".$this->id);
	}

	public function updateDetails($title) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->title = $title;

		$db = getDB();
		$s = $db->prepare("UPDATE user_twitter SET title = :t WHERE id = :id");
		$s->execute(array('id'=>$this->id,'t'=>$this->title));

		logInfo("Update details for UserTwitter:".$this->id);
	}

}


