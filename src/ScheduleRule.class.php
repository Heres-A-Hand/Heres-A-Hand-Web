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

class ScheduleRule {

	
	private $id;
	private $user_account_id;
	private $sort_order;
	private $from_time;
	private $to_time;
	private $days = array();

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['user_account_id'])) $this->user_account_id = $data['user_account_id'];
		if (isset($data['sort_order'])) $this->sort_order = $data['sort_order'];
		if (isset($data['from_time'])) $this->from_time = $data['from_time'];
		if (isset($data['to_time'])) $this->to_time = $data['to_time'];
		if (isset($data['days'])) $this->days = explode (",",$data['days']);
	}



	public static function findByIDForUserAccount($id, UserAccount $userAccount) {
		$db = getDB();
		$s = $db->prepare("SELECT schedule_rule.* FROM schedule_rule WHERE schedule_rule.user_account_id=:uid AND schedule_rule.id=:id");
		$s->execute(array('id'=>$id,'uid'=>$userAccount->getId()));
		if ($s->rowCount() == 1) {
			return new ScheduleRule($s->fetch());
		}
	}

	public static function create(UserAccount $user, $emails, $telephones, $twitters, $fromHours, $toHours, $days, $types) {
		// TODO check hours

		$db = getDB();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("SELECT max(sort_order) AS s FROM schedule_rule WHERE user_account_id=:id");
			$stat->execute(array('id'=>$user->getId()));
			$d = $stat->fetch();
			$sortOrder = $d['s'] + 1;

			$stat = $db->prepare("INSERT INTO schedule_rule (user_account_id,sort_order,from_time,to_time,days,created_at) ".
					"VALUES (:user_account_id,:sort_order,:from_time,:to_time,:days,:created_at) RETURNING id");
			$stat->bindValue('user_account_id', $user->getId());
			$stat->bindValue('sort_order', $sortOrder);
			$stat->bindValue('from_time', $fromHours.":00");
			$stat->bindValue('to_time', $toHours.":00");
			$stat->bindValue('days', implode(",", $days));
			$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$stat->execute();
			$d = $stat->fetch();
			$id = $d['id'];

			// Ensure these emails are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_email (schedule_rule_id,user_email_id) ".
					"SELECT ".$id.", user_email.id FROM user_email WHERE user_email.id = :id AND user_email.user_account_id = :uid AND user_email.deleted_at IS NULL");
			foreach($emails as $eid) {
				$stat->execute(array('id'=>$eid,'uid'=>$user->getId()));
			}

			// Ensure these telephones are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_telephone (schedule_rule_id,user_telephone_id) ".
					"SELECT ".$id.", user_telephone.id FROM user_telephone WHERE user_telephone.id = :id AND user_telephone.user_account_id = :uid AND user_telephone.deleted_at IS NULL");
			foreach($telephones as $tid) {
				$stat->execute(array('id'=>$tid,'uid'=>$user->getId()));
			}

			// Ensure these twitters are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_twitter (schedule_rule_id,user_twitter_id) ".
					"SELECT ".$id.", user_twitter.id FROM user_twitter WHERE user_twitter.id = :id AND user_twitter.user_account_id = :uid AND user_twitter.deleted_at IS NULL");
			foreach($twitters as $tid) {
				$stat->execute(array('id'=>$tid,'uid'=>$user->getId()));
			}


			$stat = $db->prepare("INSERT INTO schedule_rule_for_request_type (schedule_rule_id,request_type_id) VALUES (:sid,:rtid)"); // TODO ensure we can add these types.
			foreach($types as $tid) {
				$stat->execute(array('sid'=>$id,'rtid'=>$tid));
			}

			$db->commit();
			logInfo("New ScheduleRule:".$id." for User:".$user->getId());
			return $id;
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}

	}

	public function getId() { return $this->id; }

	public function getFromHour() { return substr($this->from_time,0,2); }
	public function getToHour() { return substr($this->to_time,0,2); }

	public function getDays() { return $this->days; }
	public function hasDay($day) { return in_array($day, $this->days); }

	protected $emails = null;

	public function loadEmails() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!is_null($this->emails)) return;

		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email JOIN schedule_rule_for_user_email ON schedule_rule_for_user_email.user_email_id = user_email.id ".
				"WHERE schedule_rule_for_user_email.schedule_rule_id = :id");
		$s->execute(array('id'=>$this->id));
		$this->emails = array();
		while($d = $s->fetch()) $this->emails[$d['id']] = new UserEmail($d);
	}


	public function hasEmail(UserEmail $ue) {
		$this->loadEmails();
		return in_array($ue->getId(), array_keys($this->emails));
	}

	public function getEmails() {
		$this->loadEmails();
		return array_values($this->emails);
	}

	protected $telephones = null;

	public function loadTelephones() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!is_null($this->telephones)) return;

		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code ".
				"FROM user_telephone   ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"JOIN schedule_rule_for_user_telephone ON schedule_rule_for_user_telephone.user_telephone_id = user_telephone.id ".
				"WHERE schedule_rule_for_user_telephone.schedule_rule_id = :id");
		$s->execute(array('id'=>$this->id));
		$this->telephones = array();
		while($d = $s->fetch()) $this->telephones[$d['id']] = new UserTelephone($d);
	}


	public function hasTelephone(UserTelephone $ut) {
		$this->loadTelephones();
		return in_array($ut->getId(), array_keys($this->telephones));
	}


	public function getTelephones() {
		$this->loadTelephones();
		return array_values($this->telephones);
	}

	protected $twitters = null;

	public function loadTwitters() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!is_null($this->twitters)) return;

		$db = getDB();
		$s = $db->prepare("SELECT user_twitter.* FROM user_twitter JOIN schedule_rule_for_user_twitter ON schedule_rule_for_user_twitter.user_twitter_id = user_twitter.id ".
				"WHERE schedule_rule_for_user_twitter.schedule_rule_id = :id");
		$s->execute(array('id'=>$this->id));
		$this->twitters = array();
		while($d = $s->fetch()) $this->twitters[$d['id']] = new UserTwitter($d);
	}


	public function hasTwitter(UserTwitter $ut) {
		$this->loadTwitters();
		return in_array($ut->getId(), array_keys($this->twitters));
	}


	public function getTwitters() {
		$this->loadTwitters();
		return array_values($this->twitters);
	}

	protected $requestTypes = null;

	public function loadRequestTypes() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!is_null($this->requestTypes)) return;

		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type JOIN schedule_rule_for_request_type ON schedule_rule_for_request_type.request_type_id = request_type.id ".
				"WHERE schedule_rule_for_request_type.schedule_rule_id = :id");
		$s->execute(array('id'=>$this->id));
		$this->requestTypes = array();
		while($d = $s->fetch()) $this->requestTypes[$d['id']] = new RequestType($d);
	}


	public function hasRequestType(RequestType $rt) {
		$this->loadRequestTypes();
		return in_array($rt->getId(), array_keys($this->requestTypes));
	}

	public function hasRequestTypeID($rtID) {
		$this->loadRequestTypes();
		return in_array($rtID, array_keys($this->requestTypes));
	}
	
	public function getRequestTypes() {
		$this->loadRequestTypes();
		return array_values($this->requestTypes);
	}
	
	public function edit($emails, $telephones, $twitters, $fromHours, $toHours, $days, $types) {
		// TODO check hours

		$db = getDB();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("UPDATE schedule_rule SET from_time=:from_time,to_time=:to_time,days=:days WHERE id=:id");
			$stat->bindValue('id', $this->id);
			$stat->bindValue('from_time', $fromHours.":00");
			$stat->bindValue('to_time', $toHours.":00");
			$stat->bindValue('days', implode(",", $days));
			$stat->execute();
			$d = $stat->fetch();

			$db->prepare("DELETE FROM schedule_rule_for_user_email WHERE schedule_rule_id=:id")->execute(array('id'=>$this->id));
			// Ensure these emails are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_email (schedule_rule_id,user_email_id) ".
					"SELECT ".$this->id.", user_email.id FROM user_email WHERE user_email.id = :id AND user_email.user_account_id = :uid AND user_email.deleted_at IS NULL");
			foreach($emails as $eid) {
				$stat->execute(array('id'=>$eid,'uid'=>$this->user_account_id));
			}

			$db->prepare("DELETE FROM schedule_rule_for_user_telephone WHERE schedule_rule_id=:id")->execute(array('id'=>$this->id));
			// Ensure these telephones are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_telephone (schedule_rule_id,user_telephone_id) ".
					"SELECT ".$this->id.", user_telephone.id FROM user_telephone WHERE user_telephone.id = :id AND user_telephone.user_account_id = :uid AND user_telephone.deleted_at IS NULL");
			foreach($telephones as $tid) {
				$stat->execute(array('id'=>$tid,'uid'=>$this->user_account_id));
			}

			$db->prepare("DELETE FROM schedule_rule_for_user_twitter WHERE schedule_rule_id=:id")->execute(array('id'=>$this->id));
			// Ensure these twitters are actually mine and not deleted
			$stat = $db->prepare("INSERT INTO schedule_rule_for_user_twitter (schedule_rule_id,user_twitter_id) ".
					"SELECT ".$this->id.", user_twitter.id FROM user_twitter WHERE user_twitter.id = :id AND user_twitter.user_account_id = :uid AND user_twitter.deleted_at IS NULL");
			foreach($twitters as $tid) {
				$stat->execute(array('id'=>$tid,'uid'=>$this->user_account_id));
			}


			$db->prepare("DELETE FROM schedule_rule_for_request_type WHERE schedule_rule_id=:id")->execute(array('id'=>$this->id));
			// TODO ensure we can add these types.
			$stat = $db->prepare("INSERT INTO schedule_rule_for_request_type (schedule_rule_id,request_type_id) VALUES (:sid,:rtid)"); 
			foreach($types as $tid) {
				$stat->execute(array('sid'=>$this->id,'rtid'=>$tid));
			}

			$db->commit();
			logInfo("Edit ScheduleRule:".$id." for User:".$this->user_account_id);
			return $id;
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}

	}
	
	
	public function delete() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("DELETE FROM schedule_rule_for_request_type WHERE schedule_rule_id=:id");
			$stat->execute(array('id'=>$this->id));

			$stat = $db->prepare("DELETE FROM schedule_rule_for_user_telephone WHERE schedule_rule_id=:id");
			$stat->execute(array('id'=>$this->id));

			$stat = $db->prepare("DELETE FROM schedule_rule_for_user_email WHERE schedule_rule_id=:id");
			$stat->execute(array('id'=>$this->id));

			$stat = $db->prepare("DELETE FROM schedule_rule_for_user_twitter WHERE schedule_rule_id=:id");
			$stat->execute(array('id'=>$this->id));

			$stat = $db->prepare("DELETE FROM schedule_rule WHERE id=:id");
			$stat->execute(array('id'=>$this->id));

			$db->commit();
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}
		logInfo("Deleting ScheduleRule:".$this->id);
	}

}




