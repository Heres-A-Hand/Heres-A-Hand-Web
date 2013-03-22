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


class Request {


	private $id;
	private $summary;
	private $request;
	private $support_group_id;
	private $created_at;
	private $created_by_user_id;
	private $closed_at;
	private $closed_by_user_id;
	private $cancelled_at;
	private $cancelled_by_user_id;
	private $start_at;
	private $end_at;

	private $created_by_display_name;
	private $created_by_avatar_key;
	private $cancelled_by_display_name;
	private $cancelled_by_avatar_key;
	private $closed_by_avatar_key;
	
	private $to_all_members;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['summary'])) $this->summary = $data['summary'];
		if (isset($data['request'])) $this->request = $data['request'];
		if (isset($data['start_at'])) $this->start_at = $data['start_at'];
		if (isset($data['end_at'])) $this->end_at = $data['end_at'];
		if (isset($data['support_group_id'])) $this->support_group_id = $data['support_group_id'];
		if (isset($data['to_all_members'])) $this->to_all_members = $data['to_all_members'];

		if (isset($data['created_at'])) $this->created_at = $data['created_at'];
		if (isset($data['created_by_display_name'])) $this->created_by_display_name = $data['created_by_display_name'];
		if (isset($data['created_by_avatar_key'])) $this->created_by_avatar_key = $data['created_by_avatar_key'];
		if (isset($data['created_by_user_id'])) $this->created_by_user_id = $data['created_by_user_id'];


		if (isset($data['closed_at'])) $this->closed_at = $data['closed_at'];
		if (isset($data['closed_by_user_id'])) $this->closed_by_user_id = $data['closed_by_user_id'];
		if (isset($data['closed_by_display_name'])) $this->closed_by_display_name = $data['closed_by_display_name'];
		if (isset($data['closed_by_avatar_key'])) $this->closed_by_avatar_key = $data['closed_by_avatar_key'];

		
		if (isset($data['cancelled_at'])) $this->cancelled_at = $data['cancelled_at'];
		if (isset($data['cancelled_by_user_id'])) $this->cancelled_by_user_id = $data['cancelled_by_user_id'];
		if (isset($data['cancelled_by_display_name'])) $this->cancelled_by_display_name = $data['cancelled_by_display_name'];
		if (isset($data['cancelled_by_avatar_key'])) $this->cancelled_by_avatar_key = $data['cancelled_by_avatar_key'];
	}

	/** @return Request **/
	public static function findByIDForUser($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT request.*, ".
				"user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key,".
				"user_account_cancelled.display_name AS cancelled_by_display_name,  user_account_cancelled.avatar_key AS cancelled_by_avatar_key,".
				"user_account_closed.display_name AS closed_by_display_name,  user_account_closed.avatar_key AS closed_by_avatar_key   ".
				"FROM request ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"LEFT JOIN user_account AS user_account_closed ON user_account_closed.id = request.closed_by_user_id " .
				"LEFT JOIN user_account AS user_account_cancelled ON user_account_cancelled.id = request.cancelled_by_user_id " .
				"JOIN user_in_group ON user_in_group.support_group_id = request.support_group_id " .
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id " .
				"WHERE request.id = :id AND user_in_group.user_account_id = :uid AND (request_to_user.user_account_id = :uid OR request.to_all_members = TRUE OR request.created_by_user_id = :uid)");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() > 0) {
			return new Request($s->fetch());
		}
	}
	/** @return Request **/
	public static function findByIDWithinSupportGroup($id, SupportGroup $group) {
		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key, user_account_closed.display_name AS closed_by_display_name  FROM request ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"LEFT JOIN user_account AS user_account_closed ON user_account_closed.id = request.closed_by_user_id " .
				"WHERE request.id = :id AND support_group_id = :sid");
		$s->execute(array('id'=>$id,'sid'=>$group->getId()));
		if ($s->rowCount() == 1) {
			return new Request($s->fetch());
		}
	}

	/** @return Request **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key, user_account_closed.display_name AS closed_by_display_name FROM request ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"LEFT JOIN user_account AS user_account_closed ON user_account_closed.id = request.closed_by_user_id " .
				"WHERE request.id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new Request($s->fetch());
		}
	}

	public function getId  () { return $this->id; }
	public function getRequest  () { return $this->request; }
	public function getSummary() { return $this->summary; }
	public function getSupportGroupId  () { return $this->support_group_id; }

	public function getCreatedAt  () { return $this->created_at; }
	public function getCreatedAtInSeconds() { return strtotime($this->created_at); }
	public function getAgeFromOpenedInSeconds() { return getCurrentTime() - strtotime($this->created_at); }
	public function getCreatedByUserId  () { return $this->created_by_user_id; }
	public function getCreatedByDisplayName  () { return $this->created_by_display_name; }
	public function getCreatedByAvatarURL() {
		if (isset($this->created_by_avatar_key) && $this->created_by_avatar_key) {
			return "/avatars/".$this->created_by_avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}

	public function isOpen() { return !$this->isCancelled() && !$this->isClosed(); }
	
	public function isClosed() { return (Boolean)$this->closed_at; }
	public function getClosedAt () { return $this->closed_at; }
	public function getAgeFromClosedInSeconds() { return getCurrentTime() - strtotime($this->closed_at); }
	public function getClosedByUserId  () { return $this->closed_by_user_id; }
	public function getClosedByDisplayName  () { return $this->closed_by_display_name; }
	public function getClosedByAvatarURL() {
		if (isset($this->closed_by_avatar_key) && $this->closed_by_avatar_key) {
			return "/avatars/".$this->closed_by_avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}

	public function isOnCalendar() { return (Boolean)$this->start_at; }
	public function getStartAtInSeconds() { return strtotime($this->start_at); }
	public function getEndAtInSeconds() { return strtotime($this->end_at); }
	public function getFromDate() { return date("j M Y",  strtotime($this->start_at)); }
	public function getFromDay() { return date("j",  strtotime($this->start_at)); }
	public function getFromMonth() { return date("n",  strtotime($this->start_at)); }
	public function getFromYear() { return date("Y",  strtotime($this->start_at)); }
	public function getFromHour() { return date("G",  strtotime($this->start_at)); }
	public function getToDate() { return date("j M Y",  strtotime($this->holiday_to)); }
	public function getToDay() { return date("j",  strtotime($this->end_at)); }
	public function getToMonth() { return date("n",  strtotime($this->end_at)); }
	public function getToYear() { return date("Y",  strtotime($this->end_at)); }
	public function getToHour() { return date("G",  strtotime($this->end_at)); }
	
	
	/** returns the start date, or request created date if not specifically on calendar **/
	public function getCalendarDateInSeconds() {
		return $this->isOnCalendar() ? $this->getStartAtInSeconds() : $this->getCreatedAtInSeconds();
	}

	public function isCancelled() { return (Boolean)$this->cancelled_at; }
	public function getCancelledAt () { return $this->cancelled_at; }
	public function getAgeFromCancelledInSeconds() { return getCurrentTime() - strtotime($this->cancelled_at); }
	public function getCancelledByUserId  () { return $this->cancelled_by_user_id; }
	public function getCancelledByDisplayName  () { return $this->cancelled_by_display_name; }
	public function getCancelledByAvatarURL() {
		if (isset($this->cancelled_by_avatar_key) && $this->cancelled_by_avatar_key) {
			return "/avatars/".$this->cancelled_by_avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}

	

	public function getTypes() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type JOIN request_has_type ON request_has_type.request_type_id = request_type.id ".
				"WHERE request_type.support_group_id = :sgid AND request_has_type.request_id = :rid ORDER BY request_type.title ASC");
		$s->execute(array('sgid'=>$this->support_group_id, 'rid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new RequestType($d);
		return $out;
	}

	/**
	 * Does not include the user who sent the request.
	 */
	public function getToUsers() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$out = array();
		if ($this->to_all_members) {

			// This is duplicated (kinda) in SupportGroup->getMembers()
			$s = $db->prepare("SELECT user_account.* FROM  user_account ".
					"JOIN user_in_group ON user_in_group.user_account_id = user_account.id ".
					"WHERE user_in_group.support_group_id = :sgid AND user_account.id != :uid ORDER BY user_account.display_name");
			$s->execute(array('sgid'=>$this->support_group_id,'uid'=>$this->created_by_user_id));

		} else {
			
			// TODO add in user_in_group table to ensure the user hasn't been kicked out of the group after the request was sent
			$s = $db->prepare("SELECT user_account.* FROM user_account ".
					"JOIN request_to_user ON request_to_user.user_account_id = user_account.id ".
					"WHERE request_to_user.request_id = :rid ORDER BY user_account.display_name ASC");
			$s->execute(array('rid'=>$this->id));
		}	
		$out = array();
		while($d = $s->fetch()) $out[] = new UserAccount ($d);
		return $out;
			
	}

	public function close(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if ($this->closed_at) throw new Exception('already closed');

		$db = getDB();
		$stat = $db->prepare("UPDATE request SET closed_at=:closed_at, closed_by_user_id=:closed_by_user_id WHERE id=:id");
		$stat->bindValue('id', $this->id);
		$stat->bindValue('closed_at', date("Y-m-d H:i:s", getCurrentTime()));
		$stat->bindValue('closed_by_user_id', $user->getId());
		$stat->execute();
		logInfo("Closing Request:".$this->id." by User:".$user->getId());
	}

	public function cancel(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if ($this->closed_at) throw new Exception('already closed');

		$db = getDB();
		$stat = $db->prepare("UPDATE request SET cancelled_at=:cancelled_at, cancelled_by_user_id=:cancelled_by_user_id WHERE id=:id");
		$stat->bindValue('id', $this->id);
		$stat->bindValue('cancelled_at', date("Y-m-d H:i:s", getCurrentTime()));
		$stat->bindValue('cancelled_by_user_id', $user->getId());
		$stat->execute();
		logInfo("Cancelling Request:".$this->id." by User:".$user->getId());
	}
	
	protected function getNotifyData() {
		// for efficiency cache this data outside the loop.
		$types = $this->getTypes();
	
		$notifyData = array();
		foreach($this->getToUsers() as $member) {
			if ($member->getId() != $this->created_by_user_id) {
				$d = buildNotifyData($member,$types);
				if ($d) $notifyData[$member->getId()] = $d;
			}
		}
		
		return $notifyData;
	
	}
	
	public function notifyPeopleAfterRequestMade() {
		$notifyData = $this->getNotifyData();

		$supportGroup = SupportGroup::findByID($this->support_group_id);

		$db = getDB();
		$statEmail = $db->prepare("INSERT INTO request_sent_to_user_email (request_id,user_email_id,sent_at) VALUES (:rid,:ueid, :at) ");
		$statTelephone = $db->prepare("INSERT INTO request_sent_to_user_telephone (request_id,user_telephone_id,sent_at) VALUES (:rid,:utid, :at) ");
		$statTwitter = $db->prepare("INSERT INTO request_sent_to_user_twitter (request_id,user_twitter_id,sent_at) VALUES (:rid,:utid, :at) ");

		foreach($notifyData as $data) {

			foreach($data['sendToEmails'] as $id=>$flag) {
				if ($flag) {

					$tpl = getEmailSmarty();
					$tpl->assign('member',$data['member']);
					$tpl->assign('email',$data['emails'][$id]);
					$tpl->assign('supportGroup',$supportGroup);
					$tpl->assign('request',$this);
					$body = null;
					if ($data['emails'][$id]->isConfirmed()) { 
						$body = $tpl->fetch('notifyNewRequest.email.txt');
					} else if (!$data['emails'][$id]->isConfirmed() && $data['emails'][$id]->getSendBeforeConfirmation()) {
						$body = $tpl->fetch('notifyNewRequest.notConfirmed.email.txt');
					}
					//print $body;

					if ($body) {
						mail($data['emails'][$id]->getEmail(), "New Request [REQ#".$this->id."] ".$this->summary, $body, "From: ".EMAILS_FROM."\r\nReply-To: ".EMAIL_IN);
						logInfo("Notifying User:".$data['member']->getId()." about Request:".$this->id." by UserEmail:".$id);
						$statEmail->execute(array('rid'=>$this->id,'ueid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
					}
				}
			}
			foreach($data['sendToTwitters'] as $id=>$flag) {
				if ($flag && defined('TWITTER_APP_KEY') && TWITTER_APP_KEY) {

					if (strlen($this->request) > 70) {
						$msg = 'https://'.HTTPS_HOST.'/request.php?id='.$this->id." ".  substr($this->summary, 0, 70). " ...";
					} else {
						$msg = 'https://'.HTTPS_HOST.'/request.php?id='.$this->id." ".  $this->summary;
					}

					$t = new TwitterOAuth(TWITTER_APP_KEY, TWITTER_APP_SECRET, TWITTER_USER_KEY, TWITTER_USER_SECRET);
					
					logInfo("Notifying User:".$data['member']->getId()." about Request:".$this->id." by UserTwitter:".$id);
					
					$r = $t->post('direct_messages/new',array(
							'screen_name'=>$data['twitters'][$id]->getUserName(),
							'text'=>$msg,
							'wrap_links '=>1
						));
					
					logDebug("Return from Twitter API for UserTwitter:".$id." is ".var_export($r,true));
					if (property_exists($r,"error")) {
						logInfo("Notifying UserTwitter:".$id." got error: ".$r->error);
					} else {
						$statTwitter->execute(array('rid'=>$this->id,'utid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));	
					}
					
				}
			}

			if ($supportGroup->isPremium()) {
				foreach($data['sendToTelephones'] as $id=>$flag) {
					if ($flag && defined('TWILIO_ID') && TWILIO_ID) {
						$msg = "@".$this->id ." ".$this->created_by_display_name.": ".$this->summary;
						//print $msg;
						$client = new Services_Twilio(TWILIO_ID, TWILIO_TOKEN);
						
						logInfo("Notifying User:".$data['member']->getId()." about Request:".$this->id." by UserTelephone:".$id);

						try {
							$r = $client->account->sms_messages->create(
									TWILIO_NUMBER, // From a valid Twilio number
									$data['telephones'][$id]->getNumberIncInternationalDialingCode(), // Text this number
									substr($msg,0,160)
								);
							logDebug("Return from TWILIO API for UserTelephone:".$id." is ".$r->sid);
							$statTelephone->execute(array('rid'=>$this->id,'utid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
						} catch (Exception $e) {
							logWarning("ERROR Return from TWILIO API for UserTelephone:".$id." msg ".$e->getMessage());
						}
						
					}
				}
			}

		}

	}
	
	
	/** Note you can respond to closed requests (emails or texts may have been working their way in) but we don't encourage it. **/
	public function newResponse($response, UserAccount $from) {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$stat = $db->prepare("INSERT INTO request_response (request_id,user_account_id,created_at,response) VALUES (:request_id,:user_account_id,:created_at,:response) RETURNING id");
		$stat->bindValue('request_id', $this->id);
		$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
		$stat->bindValue('user_account_id', $from->getId());
		$stat->bindValue('response', $response);
		$stat->execute();
		$d = $stat->fetch();
		
		logInfo("New RequestResponse:".$d['id']." to Request:".$this->id." from User:".$from->getId());
		
		$ps = getPheanstalk();
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewRequestResponse","requestResponseID"=>$d['id'])),1000,5);
		
		return $d['id'];
	}

	public function getResponses() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request_response.*, user_account.display_name , user_account.avatar_key FROM request_response ".
				"JOIN user_account ON user_account.id = request_response.user_account_id ".
				"WHERE request_response.request_id = :rid ORDER BY request_response.created_at ASC");
		$s->execute(array('rid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new RequestResponse($d);
		return $out;
	}

	public function addToCalendar($start,$end,UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('Not Loaded');
		if ($this->start_at)  throw new Exception ('Already on Calender');
		if ($end <= $start) throw new Exception ('End is not after start');

		$db = getDB();
		$stat = $db->prepare("UPDATE request SET start_at=:start_at, end_at=:end_at WHERE id=:id");
		$stat->bindValue('id', $this->id);
		$stat->bindValue('start_at', date("Y-m-d H:i:s",$start));
		$stat->bindValue('end_at', date("Y-m-d H:i:s",$end));
		$stat->execute();
		logInfo("Add calendar to Request:".$this->id." by User:".$user->getId());
	}

}

