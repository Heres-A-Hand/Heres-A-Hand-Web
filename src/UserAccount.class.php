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

class UserAccount {

	private $id;
	private $display_name;
	private $password_crypted;
	private $password_salt;
	private $forgotten_password_code;
	private $forgotten_password_code_generated_at;
	private $year_of_birth;
	private $gender;
	private $avatar_key;
	private $system_admin;
	private $use_advanced_schedule;

	/** This refers to the group the user was loaded with **/
	private $is_admin;
	/** This refers to the group the user was loaded with **/
	private $can_make_requests;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['display_name'])) $this->display_name = $data['display_name'];
		if (isset($data['password_crypted'])) $this->password_crypted = $data['password_crypted'];
		if (isset($data['password_salt'])) $this->password_salt = $data['password_salt'];
		if (isset($data['is_admin'])) $this->is_admin = $data['is_admin'];
		if (isset($data['can_make_requests'])) $this->can_make_requests = $data['can_make_requests'];
		if (isset($data['forgotten_password_code'])) $this->forgotten_password_code = $data['forgotten_password_code'];
		if (isset($data['forgotten_password_code_generated_at'])) $this->forgotten_password_code_generated_at = $data['forgotten_password_code_generated_at'];
		if (isset($data['year_of_birth'])) $this->year_of_birth = $data['year_of_birth'];
		if (isset($data['gender'])) $this->gender = $data['gender'];
		if (isset($data['avatar_key'])) $this->avatar_key = $data['avatar_key'];
		if (isset($data['system_admin'])) $this->system_admin = $data['system_admin'];		
		if (isset($data['use_advanced_schedule'])) $this->use_advanced_schedule = $data['use_advanced_schedule'];		
	}




	public static function findByEmail($email) {
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account JOIN user_email ON user_email.user_account_id = user_account.id ".
				"WHERE lower(user_email.email)=lower(:email) AND user_email.deleted_at IS NULL");
		$s->execute(array('email'=>$email));
		if ($s->rowCount() == 1) {
			return new UserAccount($s->fetch());
		}
	}

	public static function findByEmailOrCreate($email) {
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account JOIN user_email ON user_email.user_account_id = user_account.id ".
				"WHERE lower(user_email.email)=lower(:email) AND user_email.deleted_at IS NULL");
		$s->execute(array('email'=>$email));
		if ($s->rowCount() == 1) {
			return new UserAccount($s->fetch());
		} else {

			$display_name = array_shift(explode('@', $email));

			$ps = getPheanstalk();
			try {
				$db->beginTransaction();
				
				$s1 = $db->prepare('INSERT INTO user_account (display_name, created_at) VALUES (:name, :created_at) RETURNING id');
				$s1->bindValue('name', $display_name);
				$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
				$s1->execute();
				$d = $s1->fetch();
				$id = $d['id'];

				$s1 = $db->prepare('INSERT INTO user_email (user_account_id,email,confirm_code, created_at) VALUES (:id,:email,:code, :created_at) RETURNING id');
				$s1->bindValue('id', $id);
				$s1->bindValue('email', $email);
				$s1->bindValue('code', getRandomString(50));
				$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
				$s1->execute();
				$d = $s1->fetch();
				$userEmailID = $d['id'];

				$db->commit();
			} catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}

			logInfo("Created User:".$id." with UserEmail:".$userEmailID);
			if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewUserEmail","userEmailID"=>$userEmailID)),1000,5);

			return new UserAccount(array('id'=>$id,'display_name'=>$display_name));
		}
	}


	public static function findByTelephoneOrCreate($countryID, $number) {
		// this code duplicated in addTelephone()
		$number = trim(str_replace(' ', '', $number));
		if (substr($number,0,1) == "0") $number = substr($number, 1);
		
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account JOIN user_telephone ON user_telephone.user_account_id = user_account.id ".
				"WHERE user_telephone.call_number=:num AND user_telephone.country_id=:cid  AND user_telephone.deleted_at IS NULL");
		$s->execute(array('cid'=>$countryID,'num'=>$number));
		if ($s->rowCount() == 1) {
			return new UserAccount($s->fetch());
		} else {

			$ps = getPheanstalk();
			try {
				$db->beginTransaction();

				$s1 = $db->prepare('INSERT INTO user_account (display_name, created_at) VALUES (:name, :created_at) RETURNING id');
				$s1->bindValue('name', $number);
				$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
				$s1->execute();
				$d = $s1->fetch();
				$id = $d['id'];

				$s1 = $db->prepare('INSERT INTO user_telephone (user_account_id,country_id,call_number,confirm_code, created_at) VALUES (:id,:cid,:num,:code, :created_at) RETURNING id');
				$s1->bindValue('id', $id);
				$s1->bindValue('cid', $countryID);
				$s1->bindValue('num', $number);
				$s1->bindValue('code', getRandomString(10));
				$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
				$s1->execute();
				$d = $s1->fetch();
				$userTelephoneID = $d['id'];

				$db->commit();
			} catch (Exception $e) {
				$db->rollBack();
				throw $e;
			}

			logInfo("Created User:".$id." with UserTelephone:".$userTelephoneID);
			if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewUserTelephone","userTelephoneID"=>$userTelephoneID)),1000,5);

			return new UserAccount(array('id'=>$id,'display_name'=>$number));
		}
	}

	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account WHERE id=:id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new UserAccount($s->fetch());
		}
	}

	public static function findByIDinWhiteLabel($id, WhiteLabel $whiteLabel) {
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* ".
				"FROM user_account ".
				"JOIN user_in_group ON user_in_group.user_account_id = user_account.id ".
				"JOIN support_group ON user_in_group.support_group_id = support_group.id ".
				"WHERE user_account.id=:id AND support_group.white_label_id = :wlid ");
		$s->execute(array('id'=>$id,'wlid'=>$whiteLabel->getId()));
		// Don't do "== 1" as the user may be in more than one group in this white label, thus you may get the user repeated in several rows.
		if ($s->rowCount() > 0) {
			return new UserAccount($s->fetch());
		}
	}

	public static function findByIDwithSessionID($id,$sessionID) {
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account JOIN user_session ON user_session.user_account_id = user_account.id ".
				"WHERE user_account.id=:uid AND user_session.id = :sid");
		$s->execute(array('uid'=>$id,'sid'=>$sessionID));
		if ($s->rowCount() == 1) {
			$stat = $db->prepare("UPDATE user_session SET last_used_at=:at WHERE id=:sid AND user_account_id=:uid");
			$stat->execute(array('at'=>date("Y-m-d H:i:s", getCurrentTime()),'uid'=>$id, 'sid'=>$sessionID));
			// TODO Write a crontab to delete any sessions not used for months
			return new UserAccount($s->fetch());
		}
	}

	/** @return UserAccount **/
	public static function findByCountryIDandTelphone($countryID, $number) {
		$number = trim(str_replace(' ', '', $number));
		$db = getDB();
		$s = $db->prepare("SELECT user_account.* FROM user_account ".
				"JOIN user_telephone ON user_telephone.user_account_id = user_account.id ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_telephone.call_number = :n AND user_telephone.country_id = :cid AND user_telephone.deleted_at IS NULL");
		$s->execute(array('n'=>$number,'cid'=>$countryID));
		if ($s->rowCount() == 1) {
			return new UserAccount($s->fetch());
		}
	}


	public function getId() { return $this->id; }
	public function getDisplayName() { return $this->display_name; }
	public function getYearOfBirth() { return $this->year_of_birth; }
	public function getGender() { return $this->gender; }
	public function getIsSystemAdmin() { return $this->system_admin; }
	public function getUseAdvancedSchedule() { return $this->use_advanced_schedule; }

	public function isAccountCreated() { return (bool)$this->password_crypted; }

	public function getEmails() { return UserEmail::findByUser($this); }
	public function getTelephones() { return UserTelephone::findByUser($this); }
	public function getTwitters() { return UserTwitter::findByUser($this); }

	public function isAdmin() { return $this->is_admin; }
	public function canMakeRequests() { return $this->can_make_requests; }

	public function getAvatarURL() {
		if (isset($this->avatar_key) && $this->avatar_key) {
			return "/avatars/".$this->avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}

	public function getForgottenPasswordCode() {
		// TODO should really use forgotten_password_code_generated_at here and in checkForgottenPasswordCode()
		if ($this->forgotten_password_code) return $this->forgotten_password_code;

		$code = getRandomString(20);

		$db = getDB();
		$s = $db->prepare("UPDATE user_account SET forgotten_password_code=:c, forgotten_password_code_generated_at=:at WHERE id=:id");
		$s->execute(array('c'=>$code, 'id'=>$this->id,'at'=>date("Y-m-d H:i:s", getCurrentTime())));
		logInfo("Forgotten Password code generated for User:".$this->id);

		$this->forgotten_password_code = $code;
		return $code;

	}

	public function checkForgottenPasswordCode($code) {
		return ($this->forgotten_password_code && ($this->forgotten_password_code == $code));
	}

	public function createAccount($name, $password) {
		// TODO: add time-date created to DB

		$this->display_name = $name;
		$this->password_salt = getRandomString(40); 
		$this->password_crypted = sha1($password.$this->password_salt);

		$db = getDB();
		$s = $db->prepare("UPDATE user_account SET display_name=:dn, password_salt=:ps, password_crypted=:pc WHERE id=:id");
		$s->execute(array(
				'id'=>$this->id,
				'dn'=>$this->display_name,
				'ps'=>$this->password_salt,
				'pc'=>$this->password_crypted
			));

		logInfo("Password set when creating User:".$this->id);
	}

	/**
	 * When you set password, its clear you have access to your account; so the forgotten password token 
	 * is cleared.
	 * 
	 * Also all Sessions are cleared as a security measure. One reason you change your password may be 
	 * because it was leaked, and if the attacker can log in and get a saved cookie/authenticated mobile app 
	 * they will still have access even after a password change!
	 * @param <type> $password
	 */
	public function setPassword($password) {
		$this->password_salt = getRandomString(40);
		$this->password_crypted = sha1($password.$this->password_salt);

		$db = getDB();
		$s = $db->prepare("UPDATE user_account SET password_salt=:ps, password_crypted=:pc, ".
				"forgotten_password_code=null, forgotten_password_code_generated_at=null WHERE id=:id");
		$s->execute(array(
				'id'=>$this->id,
				'ps'=>$this->password_salt,
				'pc'=>$this->password_crypted
			));
		logInfo("Password changed for User:".$this->id);
		
		# Not the best way of doing it, PHP sessions won't be deleted and if the user has a cookie that will be.
		# But this will render all browser cookies and mobile apps invalid.
		$s = $db->prepare("DELETE FROM  user_session WHERE user_account_id=:id");
		$s->execute(array('id'=>$this->id));		
	}

	public function checkPassword($password) {
		return $this->password_crypted == sha1($password.$this->password_salt);
	}

	/**
	 *
	 * @param type $title Depreceated. Stored but not shown in UI.
	 * @param type $email
	 * @throws UserEmailAlreadyExistsException If already exists
	 * @throws Exception 
	 */
	public function addEmail($title, $email) {
		// catch adding duplicate emails
		$db = getDB();
		$s = $db->prepare("SELECT user_email.* FROM user_email ".
				"WHERE lower(user_email.email)=lower(:email) AND user_email.deleted_at IS NULL");
		$s->execute(array('email'=>$email));
		if ($s->rowCount() > 0) throw new UserEmailAlreadyExistsException('Email already known!');
		
		
		$ps = getPheanstalk();

		try {
			$db->beginTransaction();

			$s1 = $db->prepare('INSERT INTO user_email (user_account_id,email,confirm_code,title,created_at) VALUES (:id,:email,:code,:title,:created_at) RETURNING id');
			$s1->bindValue('id', $this->id);
			$s1->bindValue('title', $title);
			$s1->bindValue('email', $email);
			$s1->bindValue('code', getRandomString(50));
			$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$s1->execute();
			$d = $s1->fetch();

			$db->commit();
		} catch (Exception $e) {			
			$db->rollBack();
			// TODO: Catch non-unique email
			throw $e;
		}

		logInfo("Add UserEmail:".$d['id']." for User:".$this->id);
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type" => "NewUserEmail", "userEmailID" => $d['id'])), 1000, 5);
	}

	
	/**
	 *
	 * @param type $title Depreceated. Stored but not shown in UI.
	 * @param type $country_id
	 * @param type $number
	 * @return boolean
	 * @throws UserTelephoneAlreadyExistsException If already exists
	 * @throws Exception
	 */
	public function addTelephone($title, $country_id, $number) {
		// this code duplicated in findByTelephoneOrCreate()
		$number = trim(str_replace(' ', '', $number));
		if (substr($number,0,1) == "0") $number = substr($number, 1);
		
		// catch adding duplicate telephones
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.* FROM user_telephone ".
				"WHERE user_telephone.country_id=:country_id AND user_telephone.call_number=:number AND user_telephone.deleted_at IS NULL");
		$s->execute(array('country_id'=>$country_id, 'number'=>$number));
		if ($s->rowCount() > 0) throw new UserTelephoneAlreadyExistsException('Telephone already known!');	
		
		$db = getDB();
		try {
			// $db->beginTransaction();

			$s1 = $db->prepare('INSERT INTO user_telephone (user_account_id,country_id,call_number,confirm_code,title,created_at) '.
					'VALUES (:id,:country_id,:call_number,:code,:title,:created_at) RETURNING id');
			$s1->bindValue('id', $this->id);
			$s1->bindValue('title', $title);
			$s1->bindValue('country_id', $country_id);
			$s1->bindValue('call_number', $number);
			$s1->bindValue('code', getRandomString(10));
			$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$s1->execute();
			$d = $s1->fetch();
			$userTelephoneID = $d['id'];
			logInfo("Add UserTelphone:".$userTelephoneID." for User:".$this->id);

			// $db->commit();
		} catch (Exception $e) {
			// TODO: Catch non-unique number
			// $db->rollBack();
			throw $e;
		}

		$ps = getPheanstalk();
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewUserTelephone","userTelephoneID"=>$userTelephoneID)),1000,5);
		return true;
		
	}
	
	/**
	*
	* @param type $title Depreceated. Stored but not shown in UI.
	* @param type $username
	* @return boolean
	* @throws UserTwitterAlreadyExistsException If already exists
	* @throws Exception 
	*/
	public function addTwitter($title, $username) {
		$db = getDB();
		if (substr($username,0,1) == "@") $username = substr($username,1);
		if (substr($username,0,8) == "twitter@") $username = substr ($username, 8);
		if (substr($username,0,8) == "Follow @") $username = substr ($username, 8);
		if (substr($username,0,22) == "http://twitter.com/#!/") $username = substr ($username, 22);
		if (substr($username,0,23) == "https://twitter.com/#!/") $username = substr ($username, 23);
		if (substr($username,0,19) == "http://twitter.com/") $username = substr ($username, 19);
		if (substr($username,0,20) == "https://twitter.com/") $username = substr ($username, 20);
		if (substr($username,0,23) == "http://www.twitter.com/") $username = substr ($username, 23);
		if (substr($username,0,12) == "twitter.com/") $username = substr ($username, 12);
		if (substr($username,0,16) == "www.twitter.com/") $username = substr ($username, 16);		
		
		// catch adding duplicate twitter
		$db = getDB();
		$s = $db->prepare("SELECT user_twitter.* FROM user_twitter ".
				"WHERE lower(user_twitter.username)=lower(:username) AND user_twitter.deleted_at IS NULL");
		$s->execute(array('username'=>$username));
		if ($s->rowCount() > 0) throw new UserTwitterAlreadyExistsException('Twitter already known!');
		
				
		try {
			// $db->beginTransaction();

			$s1 = $db->prepare('INSERT INTO user_twitter (user_account_id,username,title,created_at) '.
					'VALUES (:id,:username,:title,:created_at)');
			$s1->bindValue('id', $this->id);
			$s1->bindValue('title', $title);
			$s1->bindValue('username', $username);
			$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$s1->execute();
			logInfo("Add Twitter for User:".$this->id);
			return true;

			// $db->commit();
		} catch (Exception $e) {
			// TODO: Catch non-unique username
			// $db->rollBack();
			throw $e;
		}
	}

	public function getNewSessionID() {
		$db = getDB();
		$s1 = $db->prepare('INSERT INTO user_session (user_account_id,id,created_at,last_used_at) '.
					'VALUES (:uid,:id,:created_at,:last_used_at)');
		$s1->bindValue('uid', $this->id);
		$s1->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
		$s1->bindValue('last_used_at', date("Y-m-d H:i:s", getCurrentTime()));
		try {
			$id = getRandomString(mt_rand(10,100));
			$s1->bindValue('id', $id);
			$s1->execute();
			logInfo("New UserSession:".$id." for User:".$this->id);
			return $id;
		} catch (Exception $e) {
			// TODO: Catch non-unique id			
			throw $e;
		}
	}

	public function newScheduleRule($emails, $telephones, $twitters, $fromHours, $toHours, $days, $types) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');
		return ScheduleRule::create($this, $emails, $telephones, $twitters, $fromHours, $toHours, $days, $types);
	}

	public function getScheduleRules() {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT schedule_rule.* FROM schedule_rule WHERE schedule_rule.user_account_id = :uid ORDER BY sort_order ASC");
		$s->execute(array('uid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new ScheduleRule($d);
		return $out;
	}

	public function updateDetails($displayName) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->display_name = $displayName;
		
		$db = getDB();
		$s = $db->prepare("UPDATE  user_account SET display_name = :dn WHERE id = :id");
		$s->execute(array('id'=>$this->id,'dn'=>$this->display_name));

		logInfo("Update User Account details for User:".$this->id);
	}

	public function updateYearOfBirth($year) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->year_of_birth = $year;

		$db = getDB();
		$s = $db->prepare("UPDATE  user_account SET year_of_birth = :y WHERE id = :id");
		$s->execute(array('id'=>$this->id,'y'=>$this->year_of_birth));

		logInfo("Update User Account Year of Birth for User:".$this->id);
	}

	public function updateUseAdvancedSchedule($newValue) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->use_advanced_schedule = $newValue;

		$db = getDB();
		$s = $db->prepare("UPDATE user_account SET use_advanced_schedule = :u WHERE id = :id");
		$s->execute(array('id'=>$this->id,'u'=>($this->use_advanced_schedule?'t':'f')));

		logInfo("Update Advanced Schedule for User:".$this->id);
	}

	public function updateGender($gender) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->gender = $gender;

		$db = getDB();
		$s = $db->prepare("UPDATE  user_account SET gender = :g WHERE id = :id");
		$s->execute(array('id'=>$this->id,'g'=>$this->gender));

		logInfo("Update User Account Gender for User:".$this->id);
	}

	public function newHoliday($from, $to,$share_with_group=false) {
		if (is_null($this->id)) throw new Exception ('No Loaded');
		if ($from < 1) throw new Exception('Could not parse from date');
		if ($to < 1) throw new Exception('Could not parse to date');
		if ($from < getCurrentTime()) throw new Exception('From is in the past');
		if ($to < getCurrentTime()) throw new Exception('To is in the past');
		if ($from > $to) throw new Exception('From is after to!');

		$db = getDB();
		$s1 = $db->prepare('INSERT INTO user_on_holiday (user_account_id,holiday_from,holiday_to,share_with_group) '.
				'VALUES (:id,:holiday_from,:holiday_to,:share_with_group)');
		$s1->bindValue('id', $this->id);
		$s1->bindValue('holiday_from', date("Y-m-d H:i:s",$from));
		$s1->bindValue('holiday_to', date("Y-m-d H:i:s",$to));
		$s1->bindValue('share_with_group', $share_with_group?'t':'f');		
		$s1->execute();

		logInfo("Add UserOnHoliday for User:".$this->id);
		return true;

	}

	/** Code pretty much duplicated in SupportGroup for now **/
	public function setAvatar($fullFileName) {
		$existingSlug = $this->avatar_key;

		$image_info = getimagesize($fullFileName);
		if( $image_info[2] == IMAGETYPE_JPEG ) {
			$uploadedImage = imagecreatefromjpeg($fullFileName);
		} elseif( $image_info[2] == IMAGETYPE_GIF ) {
			$uploadedImage = imagecreatefromgif($fullFileName);
		} elseif( $image_info[2] == IMAGETYPE_PNG ) {
			$uploadedImage = imagecreatefrompng($fullFileName);
		} else {
			throw new Exception('That type is not recognised!');
		}

		$x = imagesx($uploadedImage);
		$y = imagesy($uploadedImage);

		$foundNewSlug = false;
		$len = 50;
		while(!$foundNewSlug) {
			$this->avatar_key = getRandomString($len);
			$newImagePath = dirname(__FILE__)."/../public_html/avatars/".$this->avatar_key.".jpg";
			if (!is_file($newImagePath)) $foundNewSlug = true;
			if ($len < 100) $len++;
		}
		
		$scale = max(1,max($x/70, $y/70));
		list($tX, $tY) = array(intval($x/$scale), intval($y/$scale));
		$image = imagecreatetruecolor($tX, $tY);
		imagecopyresampled($image, $uploadedImage, 0, 0, 0, 0, $tX, $tY, $x, $y);
		imagejpeg($image,$newImagePath,80);

		$db = getDB();
		$stat = $db->prepare('UPDATE user_account SET avatar_key=:avatar_key WHERE id=:id');
		$stat->execute(array('id'=>$this->id,'avatar_key'=>$this->avatar_key));

		unlink($fullFileName);

		if ($existingSlug) {
			$existingImagePath = dirname(__FILE__)."/../public_html/avatars/".$existingSlug.".jpg";
			if (is_file($existingImagePath)) unlink($existingImagePath);
		}
	}


	public function hasAnyPremiumGroups() {
		$db = getDB();
		$s = $db->prepare("SELECT support_group.id FROM support_group ".
				"JOIN user_in_group ON user_in_group.support_group_id = support_group.id ".
				"WHERE user_in_group.user_account_id = :id AND support_group.is_premium = true");
		$s->execute(array('id'=>$this->id));
		return $s->rowCount() > 0;
	}
	
	public function delete(UserAccount $deletedBy) {
		if ($this->isAccountCreated()) throw new UserAccountAlreadyCreatedException("You can't do this if account already created!");
		
		$db = getDB();
		$stat1 = $db->prepare("DELETE FROM user_email WHERE user_account_id=:id");
		$stat2 = $db->prepare("DELETE FROM user_twitter WHERE user_account_id=:id");
		$stat3 = $db->prepare("DELETE FROM user_telephone  WHERE user_account_id=:id");
		$stat4 = $db->prepare("DELETE FROM user_in_group WHERE user_account_id=:id");
		$stat5 = $db->prepare("DELETE FROM request_to_user WHERE  user_account_id=:id");
		$stat6 = $db->prepare("DELETE FROM user_account WHERE id=:id");
		try {
			$db->beginTransaction();
			$stat1->execute(array('id'=>$this->id));
			$stat2->execute(array('id'=>$this->id));
			$stat3->execute(array('id'=>$this->id));
			$stat4->execute(array('id'=>$this->id));
			$stat5->execute(array('id'=>$this->id));
			$stat6->execute(array('id'=>$this->id));
			$db->commit();
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}		
		
		logInfo("User:".$this->id." deleted by User:".$deletedBy->getId());
		
	}

}


