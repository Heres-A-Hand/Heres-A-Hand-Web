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

class SMSIn {
	
	private $id;
	private $twilio_msg_id;
	private $from_number;
	private $body;
	private $user_telephone_id;
	private $request_response_id;
	private $request_id;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['twilio_msg_id'])) $this->twilio_msg_id = $data['twilio_msg_id'];
		if (isset($data['from_number'])) $this->from_number = $data['from_number'];
		if (isset($data['body'])) $this->body = $data['body'];
		if (isset($data['user_telephone_id'])) $this->user_telephone_id = $data['user_telephone_id'];
		if (isset($data['request_response_id'])) $this->request_response_id = $data['request_response_id'];
		if (isset($data['request_id'])) $this->request_id = $data['request_id'];
	}
	
	

	public static function create($from, $body, $twilioID=null) {
		$db = getDB();
		
		$stat = $db->prepare("INSERT INTO sms_in (from_number, body, twilio_msg_id, created_at) ".
				"VALUES (:from_number, :body, :twilio_msg_id,:created_at) RETURNING id");
				
		$stat->bindValue('from_number', $from);
		$stat->bindValue('body', $body);
		$stat->bindValue('twilio_msg_id', $twilioID);
		$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
		$stat->execute();
		$d = $stat->fetch();
		$id =  $d['id'];
		logNotice("Created SMSIn:".$id);
		
		$ps = getPheanstalk();
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewSMSIn","SMSInID"=>$id)),1000,5);
		
		return $id;			
	}
	
	/** @return SMSIn **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT sms_in.* FROM sms_in ".
				"WHERE sms_in.id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new SMSIn($s->fetch());
		}
	}
	
	public function process() {
		if ($this->user_telephone_id || $this->request_response_id || $this->request_id) return false;  // already done?
		
		if (substr($this->from_number, 0, 3) != '+44') return false; // an international number
		
		$userTelephone = UserTelephone::findByCountryIDandTelphone(1, substr($this->from_number, 3));
		if (!$userTelephone) return false; // no UT
		$userAccount = $userTelephone->getUserAccount();
		
		// ######### New request?
		if (strtolower(substr($this->body,0,3)) == 'new') { 
			return $this->makeNewRequest($userAccount,$userTelephone,trim(substr($this->body,3)));
		}
		if (strtolower(substr($this->body,0,4)) == '@new') { 
			return $this->makeNewRequest($userAccount,$userTelephone,trim(substr($this->body,4)));
		}
		// ########## User has specifically said where to send to
		$data = $this->getSpecifiedRequestIDFromText();
		if (is_array($data) && $data[0]) {
			$request = Request::findByIDForUser($data[0], $userAccount);
			if ($request) {
				return $this->makeReplyToRequest($userAccount,$userTelephone,$request,$data[1]);
			}
		}
		
		// ######### try to find last text sent to user and assume text is in response to that.
		// TODO this should really have sent_at < this->created_at. For now assume we are checking asap after text received.
		$db=getDB();
		$stat = $db->prepare("SELECT request_id FROM request_sent_to_user_telephone WHERE user_telephone_id=:utid ORDER BY sent_at DESC");
		$stat->execute(array('utid'=>$userTelephone->getId()));
		if ($stat->rowCount() > 0) {
			$data = $stat->fetch();

			$request = Request::findByIDForUser($data['request_id'], $userAccount);
			if ($request) {
				return $this->makeReplyToRequest($userAccount,$userTelephone,$request,$this->body);
			} else {
				throw new Exception("This should be impossible if data integrety has held!"); 
			}
		} else {
			// What if user has never been texted before?
			// Make a new request
			return $this->makeNewRequest($userAccount,$userTelephone);
		} 
		
		return false;
	}
	
	protected function makeReplyToRequest(UserAccount $userAccount, UserTelephone $userTelephone, Request $request, $responseBody) {
		$requestResponseID = $request->newResponse($responseBody, $userAccount);
		$db=getDB();
		$stat = $db->prepare("UPDATE sms_in SET user_telephone_id=:utid, request_response_id=:rrid WHERE id=:id");
		$stat->bindValue('utid',$userTelephone->getId());
		$stat->bindValue('rrid',$requestResponseID);
		$stat->bindValue('id',$this->id);
		$stat->execute();
		return true;
	}

	protected function makeNewRequest(UserAccount $userAccount, UserTelephone $userTelephone, $summary) {
		// TODO Need to check permissions; does user have permission to make a new request?

		$supportGroups = SupportGroup::findForUser($userAccount);
		if (count($supportGroups) > 0) {
			$userIDS = array();
			foreach($supportGroups[0]->getMembers() as $u) {
				if ($u->getId() != $userAccount->getId()) $userIDS[] = $u->getId();
			}

			if (!$summary) return false;

			$requestID = $supportGroups[0]->newRequest($userAccount, $summary, "", array(), $userIDS);
			
			$db=getDB();
			$stat = $db->prepare("UPDATE sms_in SET user_telephone_id=:utid, request_id=:rid WHERE id=:id");
			$stat->bindValue('utid',$userTelephone->getId());
			$stat->bindValue('rid',$requestID);
			$stat->bindValue('id',$this->id);
			$stat->execute();
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Public for testing purposes.
	 */
	public function getSpecifiedRequestIDFromText() {
		if (substr($this->body,0,1) == '@') {
			$len=0;
			while(ctype_digit(substr($this->body,1+$len,1))) {
				$len += 1;
			}
			if ($len > 0) {
				$id = intval(substr($this->body,1,$len));
				$body = trim(substr($this->body,1+$len));
				return $id && $body ? array($id, $body ) : null;
			}
		}
		return null;
	}
	
	
}


