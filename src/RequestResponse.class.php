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


class RequestResponse {


	private $id;
	private $request_id;
	private $user_account_id;
	private $created_at;
	private $response;
	private $display_name;
	private $avatar_key;

	/** @return RequestResponse **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT request_response.*, user_account.display_name , user_account.avatar_key".
			" FROM request_response ".
			" JOIN user_account ON user_account.id = request_response.user_account_id  ".
			" WHERE request_response.id = :id ");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() > 0) {
			return new RequestResponse($s->fetch());
		}
	}
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['request_id'])) $this->request_id = $data['request_id'];
		if (isset($data['user_account_id'])) $this->user_account_id = $data['user_account_id'];
		if (isset($data['created_at'])) $this->created_at = $data['created_at'];
		if (isset($data['response'])) $this->response = $data['response'];
		if (isset($data['display_name'])) $this->display_name = $data['display_name'];
		if (isset($data['avatar_key'])) $this->avatar_key = $data['avatar_key'];
	}

	public function getId() { return $this->id; }
	public function getRequestId() { return $this->request_id; }
	public function getResponse() { return $this->response; }
	public function getCreatedAt() { return $this->created_at; }
	public function getCreatedAtInSeconds() { return strtotime($this->created_at); }
	public function getAgeInSeconds() { return getCurrentTime() - strtotime($this->created_at); }
	public function getDisplayName() { return $this->display_name; }
	public function getUserAccountID  () { return $this->user_account_id; }
	

	public function getAvatarURL() {
		if (isset($this->avatar_key) && $this->avatar_key) {
			return "/avatars/".$this->avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}
	
	protected function getNotifyData() {
		$request = Request::findByID($this->request_id);
		$requestBy = UserAccount::findByID($request->getCreatedByUserId());
		$types = $request->getTypes();

		$notifyData = array();

		// At moment we only notify person who made request (but not if its the same person who commented!)
		if ($request->getCreatedByUserId() != $this->user_account_id) {
			$d = buildNotifyData($requestBy,$types);
			if ($d) $notifyData[$request->getCreatedByUserId()] = $d;
		}
		
		return $notifyData;
	}
	
	public function notifyPeopleAfterRequestResponseMade() {
		// for efficiency cache this data outside the loop.
		$request = Request::findByID($this->request_id);
		$requestBy = UserAccount::findByID($request->getCreatedByUserId());
		$thisResponseBy = UserAccount::findByID($this->user_account_id);

		$notifyData = $this->getNotifyData();
		

		
		$supportGroup = SupportGroup::findByID($request->getSupportGroupId());

		$db = getDB();
		$statEmail = $db->prepare("INSERT INTO request_response_sent_to_user_email (request_response_id,user_email_id,sent_at) VALUES (:rid,:ueid, :at) ");
		$statTelephone = $db->prepare("INSERT INTO request_response_sent_to_user_telephone (request_response_id,user_telephone_id,sent_at) VALUES (:rid,:utid, :at) ");
		$statTwitter = $db->prepare("INSERT INTO request_response_sent_to_user_twitter (request_response_id,user_twitter_id,sent_at) VALUES (:rid,:utid, :at) ");

		foreach($notifyData as $data) {

			foreach($data['sendToEmails'] as $id=>$flag) {
				if (true) {  // We don't check $flag! We ignore the users preferences and always send them replies by email!

					$tpl = getEmailSmarty();
					$tpl->assign('member',$data['member']);
					$tpl->assign('email',$data['emails'][$id]);
					$tpl->assign('supportGroup',$supportGroup);
					$tpl->assign('request',$request);
					$tpl->assign('requestResponse',$this);
					$body = null;
					if ($data['emails'][$id]->isConfirmed()) {
						$body = $tpl->fetch('notifyNewRequestResponse.email.txt');
					} else if (!$data['emails'][$id]->isConfirmed() && $data['emails'][$id]->getSendBeforeConfirmation()) {
						// This is impossible, as we only send to person makeing the request. May become possible in future.
					}
					//print $body;

					if ($body) {
						mail($data['emails'][$id]->getEmail(), "New Response to Request [REQ#".$this->request_id."]", $body, "From: ".EMAILS_FROM."\r\nReply-To: ".EMAIL_IN);
						logInfo("Notifying User:".$data['member']->getId()." about RequestResponse:".$this->id." by UserEmail:".$id);
						$statEmail->execute(array('rid'=>$this->id,'ueid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
					}
				}
			}
			foreach($data['sendToTwitters'] as $id=>$flag) {
				if ($flag && defined('TWITTER_APP_KEY') && TWITTER_APP_KEY) {

					if (strlen($this->response) > 70) {
						$msg = 'https://'.HTTPS_HOST.'/request.php?id='.$this->request_id." ".  substr($this->response, 0, 70). " ...";
					} else {
						$msg = 'https://'.HTTPS_HOST.'/request.php?id='.$this->request_id." ".  $this->response;
					}

					$t = new TwitterOAuth(TWITTER_APP_KEY, TWITTER_APP_SECRET, TWITTER_USER_KEY, TWITTER_USER_SECRET);
					
					logInfo("Notifying User:".$data['member']->getId()." about RequestResponse:".$this->id." by UserTwitter:".$id);
					
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
						$msg = "@".$this->request_id." ".$thisResponseBy->getDisplayName().": ".$this->response;
						//print $msg;
						$client = new Services_Twilio(TWILIO_ID, TWILIO_TOKEN);
						
						logInfo("Notifying User:".$data['member']->getId()." about RequestResponse:".$this->id." by UserTelephone:".$id);
						
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
	
}
