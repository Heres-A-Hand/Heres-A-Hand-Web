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

class UserTelephone  extends BaseUserContactMethod {

	protected $country_id;
	protected $call_number;
	protected $confirm_code;
	protected $international_dailing_code;

	public function  __construct($data) {
		parent::__construct($data);
		$this->tableName = 'user_telephone';
		if (isset($data['call_number'])) $this->call_number = $data['call_number'];
		if (isset($data['country_id'])) $this->country_id = $data['country_id'];
		if (isset($data['confirm_code'])) $this->confirm_code = $data['confirm_code'];
		if (isset($data['international_dailing_code'])) $this->international_dailing_code = $data['international_dailing_code'];
	}

	/** @return UserTelephone **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code FROM user_telephone ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_telephone.id = :id AND user_telephone.deleted_at IS NULL");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new UserTelephone($s->fetch());
		}
	}

	public static function findByUser(UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code FROM user_telephone ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_account_id = :id AND user_telephone.deleted_at IS NULL");
		$s->execute(array('id'=>$user->getId()));
		$out = array();
		
		while($d = $s->fetch()) $out[] = new UserTelephone ($d);
		return $out;
	}

	/** @return UserTelephone **/
	public static function findByIDForUserAccount($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code FROM user_telephone   ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_telephone.id = :id AND user_telephone.user_account_id = :uid AND user_telephone.deleted_at IS NULL");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			return new UserTelephone($s->fetch());
		}
	}

	/** @return UserTelephone **/
	public static function findByCountryIDandTelphone($countryID, $number) {
		if (substr($number,0,1) == "0") $number = substr($number, 1);
		$number = trim(str_replace(' ', '', $number));
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code FROM user_telephone ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_telephone.call_number = :n AND user_telephone.country_id = :cid AND user_telephone.deleted_at IS NULL");
		$s->execute(array('n'=>$number,'cid'=>$countryID));
		if ($s->rowCount() == 1) {
			return new UserTelephone($s->fetch());
		}
	}

	
	public function getCountryID() { return $this->country_id; }
	public function getNumber() { return $this->call_number; }
	public function getInternationalDialingCode() { return $this->international_dailing_code; }
	public function getNumberIncInternationalDialingCode() { return strval($this->international_dailing_code) . strval($this->call_number); }
	public function isConfirmed() {  return ($this->confirm_code == '' || is_null($this->confirm_code)); }


	public function checkConfirmCode($code) { return ($this->confirm_code == $code); }
	public function getConfirmCode() { return $this->confirm_code; }

	/** The text message should change based on whether the account is already created or not. Seperate for testing **/
	protected function getConfirmCodeMessage() {
		if (!$this->confirm_code) throw new Exception ('No confirm code, already confirmed!');
		if (!$this->user_account_id) throw new Exception('This should be impossible');
		if (UserAccount::findByID($this->user_account_id)->isAccountCreated()) {
			return "To confirm this phone for your Here's a Hand account, please enter this confirmation code: ".$this->confirm_code;
		} else {
			return "You have been invited to Here's a Hand! Please visit http://".HTTP_HOST."/ & login with this password: ".$this->confirm_code;
		}
	}
	
	public function sendConfirmCode() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!$this->confirm_code) throw new Exception ('No confirm code, already confirmed!');
		if (!defined('TWILIO_ID')) return;

		//print $this->getConfirmCodeMessage();
		
		$client = new Services_Twilio(TWILIO_ID, TWILIO_TOKEN);
		
		$r = $client->account->sms_messages->create(
		  TWILIO_NUMBER, // From a valid Twilio number
		  $this->getNumberIncInternationalDialingCode(), // Text this number
		  $this->getConfirmCodeMessage()
		);
						
		logInfo("Sent text to confirm UserTelephone:".$this->id);
		logDebug("Return from TWILIO API for UserTelephone:".$this->id." is ".$r->sid);

	}

	public function markConfirmed() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_telephone SET confirm_code = NULL WHERE id = :id");
		// TODO: add time-date verified to DB
		$s->execute(array('id'=>$this->id));

		logInfo("Confirmed UserTelephone:".$this->id);
	}

	public function delete() {
		$db = getDB();
		$s = $db->prepare("UPDATE user_telephone SET deleted_at = :at WHERE id = :id");
		$s->execute(array('id'=>$this->id,'at'=>date('Y-m-d H:i:s', getCurrentTime())));
		logInfo("Deleted UserTelephone:".$this->id);
	}


	public function updateDetails($title) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->title = $title;

		$db = getDB();
		$s = $db->prepare("UPDATE user_telephone SET title = :t WHERE id = :id");
		$s->execute(array('id'=>$this->id,'t'=>$this->title));

		logInfo("Update details for UserTelephone:".$this->id);
	}


}


