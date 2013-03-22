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

class UserEmail extends BaseUserContactMethod {

	protected $email;
	protected $confirm_code;
	protected $send_before_confirmation;
	protected $stop_send_before_confirmation_code;
	
	public function  __construct($data) {
		parent::__construct($data);
		$this->tableName = 'user_email';
		if (isset($data['email'])) $this->email = $data['email'];
		if (isset($data['confirm_code'])) $this->confirm_code = $data['confirm_code'];
		if (isset($data['stop_send_before_confirmation_code'])) $this->stop_send_before_confirmation_code = $data['stop_send_before_confirmation_code'];
		if (isset($data['send_before_confirmation'])) $this->send_before_confirmation = $data['send_before_confirmation'];
	}

	/** @return UserEmail **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email WHERE id = :id AND deleted_at IS NULL");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new UserEmail($s->fetch());
		}
	}

	/** @return UserEmail **/
	public static function findByEmail($email) {
		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email WHERE email = :e AND deleted_at IS NULL");
		$s->execute(array('e'=>$email));
		if ($s->rowCount() == 1) {
			return new UserEmail($s->fetch());
		}
	}

	public static function findByIDForUserAccount($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email WHERE id = :id AND user_account_id = :uid AND deleted_at IS NULL");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			return new UserEmail($s->fetch());
		}
	}

	public static function findByUser(UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email WHERE user_account_id = :id AND deleted_at IS NULL");
		$s->execute(array('id'=>$user->getId()));
		$out = array();
		while($d = $s->fetch()) $out[] = new UserEmail($d);
		return $out;
	}

	public function sendConfirmCode() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if (!$this->confirm_code) throw new Exception ('No confirm code, already confirmed!');

		$tpl = getEmailSmarty();
		$tpl->assign('id',$this->id);
		$tpl->assign('confirm_code',$this->confirm_code);
		$user = $this->getUserAccount();
		$tpl->assign('user',$user);
		$tpl->assign('groups',  SupportGroup::findForUser($user));
		$body = $tpl->fetch('confirm.email.txt');

		//print $body; die();
		
		mail($this->email, "Please confirm your account on Here's a Hand", $body, "From: ".EMAILS_FROM);
		logInfo("Sent email to confirm UserEmail:".$this->id);

	}

	public function sendForgottenPassword() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$user = $this->getUserAccount();

		$tpl = getEmailSmarty();
		$tpl->assign('emailid',$this->id);
		$tpl->assign('userid',$this->user_account_id);
		$tpl->assign('user',$user);
		$tpl->assign('code',$user->getForgottenPasswordCode());
		$body = $tpl->fetch('forgottenpassword.email.txt');

		mail($this->email, "You wanted to reset your password?", $body, "From: ".EMAILS_FROM);
		logInfo("Sent forgotten password email to UserEmail:".$this->id);

	}


	public function markConfirmed() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_email SET confirm_code = NULL WHERE id = :id");
		// TODO: add time-date verified to DB
		$s->execute(array('id'=>$this->id));

		logInfo("Confirmed UserEmail:".$this->id);
	}


	
	public function getEmail() { return $this->email; }
	public function isConfirmed() {  return ($this->confirm_code == '' || is_null($this->confirm_code)); }


	public function checkConfirmCode($code) { return ($this->confirm_code == $code); }
	public function getConfirmCode() { return $this->confirm_code; }

	public function getSendBeforeConfirmation() { 
		return $this->send_before_confirmation; 
	}
	public function checkStopSendBeforeConfirmationCode($code) { 
		return $this->stop_send_before_confirmation_code && ($this->stop_send_before_confirmation_code == $code); 
	}
	public function getStopSendBeforeConfirmationCode() { 
		if (!$this->stop_send_before_confirmation_code) {
			$this->stop_send_before_confirmation_code = getRandomStringVarLength(2,50);
			$db = getDB();
			$s = $db->prepare("UPDATE user_email SET stop_send_before_confirmation_code = :code WHERE id = :id");
			$s->execute(array('id'=>$this->id,'code'=>$this->stop_send_before_confirmation_code));
		}
		return $this->stop_send_before_confirmation_code; 
	}
	

	public function delete() {
		$db = getDB();
		$s = $db->prepare("UPDATE user_email SET deleted_at = :at WHERE id = :id");
		$s->execute(array('id'=>$this->id,'at'=>date('Y-m-d H:i:s', getCurrentTime())));
		logInfo("Deleted UserEmail:".$this->id);
	}


	public function updateDetails($title) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->title = $title;

		$db = getDB();
		$s = $db->prepare("UPDATE user_email SET title = :t WHERE id = :id");
		$s->execute(array('id'=>$this->id,'t'=>$this->title));

		logInfo("Update details for UserEmail:".$this->id);
	}


	public function updateStopSendBeforeConfirmationCode($value) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->send_before_confirmation = $value;

		$db = getDB();
		$s = $db->prepare("UPDATE user_email SET send_before_confirmation = :v, stop_send_before_confirmation_code= null WHERE id = :id");
		$s->execute(array('id'=>$this->id,'v'=>($this->send_before_confirmation?'t':'f')));

		logInfo("Update send_before_confirmation for UserEmail:".$this->id);
	}


}


