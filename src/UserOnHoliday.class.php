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

class UserOnHoliday {

	private $id;
	private $user_account_id;
	private $holiday_from;
	private $holiday_to;
	private $description;
	private $share_with_group;
	
	/** some loading methods will add the users current display name,
	 * 
	 * @see  SupportGroup::getHolidaysVisibleToUserOnCalendar()
	 * @var String
	 */
	private $display_name;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['user_account_id'])) $this->user_account_id = $data['user_account_id'];
		if (isset($data['holiday_from'])) $this->holiday_from = $data['holiday_from'];
		if (isset($data['holiday_to'])) $this->holiday_to = $data['holiday_to'];
		if (isset($data['description'])) $this->description = $data['description'];
		if (isset($data['display_name'])) $this->display_name = $data['display_name'];
		if (isset($data['share_with_group'])) $this->share_with_group = $data['share_with_group'];
	}


	public static function findByIDForUserAccount($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_on_holiday.* FROM user_on_holiday WHERE id = :id AND user_account_id = :uid");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			return new UserOnHoliday($s->fetch());
		}
	}

	public static function findByUser(UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_on_holiday.* FROM user_on_holiday WHERE user_account_id = :id ORDER BY holiday_from");
		$s->execute(array('id'=>$user->getId()));
		$out = array();
		while($d = $s->fetch()) $out[] = new UserOnHoliday($d);
		return $out;
	}

	public static function findByUserForDate(UserAccount $user, $date) {
		$db = getDB();
		$s = $db->prepare("SELECT user_on_holiday.* FROM user_on_holiday WHERE user_account_id = :id AND holiday_from < :date AND holiday_to > :date ");
		$s->execute(array('id'=>$user->getId(),'date'=>date("Y-m-d H:i:s",$date)));
		if ($s->rowCount() > 0) return new UserOnHoliday($s->fetch());
	}

	public function getId() { return $this->id; }
	public function getDisplayName() { return $this->display_name; }
	public function getUserAccountId() { return $this->user_account_id; }
	public function getShareWithGroup() { return $this->share_with_group; }
	public function getFromDate() { return date("j M Y",  strtotime($this->holiday_from)); }
	public function getFromDay() { return date("j",  strtotime($this->holiday_from)); }
	public function getFromMonth() { return date("n",  strtotime($this->holiday_from)); }
	public function getFromYear() { return date("Y",  strtotime($this->holiday_from)); }
	public function getFromHour() { return date("G",  strtotime($this->holiday_from)); }
	public function getFromTimeStamp() { return strtotime($this->holiday_from); }
	public function getToDate() { return date("j M Y",  strtotime($this->holiday_to)); }
	public function getToDay() { return date("j",  strtotime($this->holiday_to)); }
	public function getToMonth() { return date("n",  strtotime($this->holiday_to)); }
	public function getToYear() { return date("Y",  strtotime($this->holiday_to)); }
	public function getToHour() { return date("G",  strtotime($this->holiday_to)); }
	public function getToTimeStamp() { return strtotime($this->holiday_to); }

	public function edit($from,$to,$share_with_group=false) {
		if ($from < 1) throw new Exception('Could not parse from date');
		if ($to < 1) throw new Exception('Could not parse to date');
		if ($from < getCurrentTime()) throw new Exception('From is in the past');
		if ($to < getCurrentTime()) throw new Exception('To is in the past');
		if ($from > $to) throw new Exception('From is after to!');

		$db = getDB();
		$s1 = $db->prepare('UPDATE user_on_holiday SET holiday_from=:holiday_from,holiday_to=:holiday_to,share_with_group=:share_with_group WHERE id = :id');
		$s1->bindValue('id', $this->id);
		$s1->bindValue('holiday_from', date("Y-m-d H:i:s",$from));
		$s1->bindValue('holiday_to', date("Y-m-d H:i:s",$to));
		$s1->bindValue('share_with_group', $share_with_group?'t':'f');
		$s1->execute();

		logInfo("Edited UserOnHoliday:".$this->id." for User:".$this->user_account_id);
		return true;

	}

	public function delete() {
		$db = getDB();

		$stat = $db->prepare("DELETE FROM user_on_holiday WHERE id=:id");
		$stat->execute(array('id'=>$this->id));

		logInfo("Deleted UserOnHoliday:".$this->id);
	}

}

