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



class SupportGroup {

	private $id;
	private $white_label_id;
	private $title;
	private $description;
	private $is_premium;
	private $avatar_key;
	private $created_at;
	private $deleted_at;
	private $sys_admin_label;
	
	/** This refers to the user the group was loaded with **/
	private $is_admin;
	/** This refers to the user the group was loaded with **/
	private $can_make_requests = null;
	
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['white_label_id'])) $this->white_label_id = $data['white_label_id'];
		if (isset($data['title'])) $this->title = $data['title'];
		if (isset($data['description'])) $this->description = $data['description'];
		if (isset($data['is_admin'])) $this->is_admin = $data['is_admin'];
		if (isset($data['can_make_requests'])) $this->can_make_requests = $data['can_make_requests'];
		if (isset($data['is_premium'])) $this->is_premium = $data['is_premium'];
		if (isset($data['avatar_key'])) $this->avatar_key = $data['avatar_key'];
		if (isset($data['created_at'])) $this->created_at = strtotime($data['created_at']);
		if (isset($data['deleted_at'])) $this->deleted_at = strtotime($data['deleted_at']);
		if (isset($data['sys_admin_label'])) $this->sys_admin_label = $data['sys_admin_label'];
	}


	public static function create($title) {
		$db = getDB();
		$s = $db->prepare("INSERT INTO support_group (title, created_at) VALUES(:t, :created_at) RETURNING id");
		$s->execute(array('t'=>$title,'created_at'=>date("Y-m-d H:i:s", getCurrentTime())));
		$d = $s->fetch();
		logInfo("Creating  SupportGroup:".$d['id']);
		$x =  new SupportGroup(array('id'=>$d['id'],'title'=>$title));
		$x->addRequestType('Shopping');
		$x->addRequestType('Household Tasks');
		return $x;
	}

	public static function findForUser(UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group.*, user_in_group.is_admin, user_in_group.can_make_requests FROM support_group ".
				"JOIN user_in_group ON user_in_group.support_group_id = support_group.id ".
				"WHERE user_in_group.user_account_id = :id AND support_group.deleted_at IS NULL ORDER BY support_group.title");
		$s->execute(array('id'=>$user->getId()));
		$out = array();
		while($d = $s->fetch()) $out[] = new SupportGroup($d);
		return $out;
	}

	/** @return SupportGroup **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group.* FROM support_group WHERE id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new SupportGroup($s->fetch());
		}
	}

	/** @return SupportGroup **/
	public static function findByIDForWhiteLabel($id, WhiteLabel $whiteLabel) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group.* ".
				"FROM support_group ".
				"WHERE id = :id AND white_label_id=:wlid");
		$s->execute(array('id'=>$id,'wlid'=>$whiteLabel->getId()));
		if ($s->rowCount() == 1) {
			return new SupportGroup($s->fetch());
		}
	}

	/** @return SupportGroup **/
	public static function findByIDForUser($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group.* , user_in_group.is_admin, user_in_group.can_make_requests FROM support_group ".
			"JOIN user_in_group ON user_in_group.support_group_id = support_group.id ".
			"WHERE user_in_group.user_account_id = :uid AND support_group.deleted_at IS NULL AND  support_group.id = :id");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			$data = $s->fetch();
			return new SupportGroup($data);
		}
	}

	/** @return Boolean whether added or not; true is yes, false if no. Only reason for false is they are already here **/
	public function addUser(UserAccount $ua, $isAdmin = false, $canMakeRequests=true, UserAccount $invitedBy=null) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');
		if ($isAdmin) $canMakeRequests = true;

		$db = getDB();
		$s = $db->prepare("SELECT * FROM user_in_group WHERE user_account_id=:ua AND support_group_id=:sg");
		$s->execute(array(
				'ua'=>$ua->getId(),
				'sg'=>$this->id,
			));
		if ($s->rowCount() == 0) {
			$s = $db->prepare("INSERT INTO user_in_group (user_account_id,support_group_id,is_admin,can_make_requests,".
				"created_at,invited_by_user_account_id) ".
				"VALUES(:ua,:sg,:f1,:f2,:created_at,:invite)");
			$s->execute(array(
					'ua'=>$ua->getId(),
					'sg'=>$this->id,
					'f1'=>($isAdmin?'true':'false'),
					'f2'=>($canMakeRequests?'true':'false'),
					'created_at'=>date("Y-m-d H:i:s", getCurrentTime()),
					'invite'=>($invitedBy ? $invitedBy->getId() : null)
				));
			logInfo("Adding User:".$ua->getId()." to SupportGroup:".$this->id." ".($isAdmin?"Admin":"NotAdmin")." ".($canMakeRequests?"CanMakeRequests":"NoMakeRequests"));
			return true;
		} else {
			$d = $s->fetch();
			if ($isAdmin && !$d['is_admin']) {
				$s = $db->prepare("UPDATE user_in_group SET is_admin=true, can_make_requests=true WHERE user_account_id=:ua AND support_group_id=:sg");
				$s->execute(array(
						'ua'=>$ua->getId(),
						'sg'=>$this->id,
					));
				logInfo("Upgrading Admin and CanMakeRequests via addUser method for User:".$ua->getId()." to SupportGroup:".$this->id);
			} else if ($canMakeRequests  && !$d['can_make_requests']) {
				$s = $db->prepare("UPDATE user_in_group SET can_make_requests=true WHERE user_account_id=:ua AND support_group_id=:sg");
				$s->execute(array(
						'ua'=>$ua->getId(),
						'sg'=>$this->id,
					));
				logInfo("Upgrading CanMakeRequests via addUser method for User:".$ua->getId()." to SupportGroup:".$this->id);
			// else if ($isAdmin && !$d['is_admin']) {   <-- this should never happen due to above line: if ($isAdmin) $canMakeRequests = true;
			} else {
				logInfo("Wanted to add User:".$ua->getId()." to SupportGroup:".$this->id." with ".($isAdmin?"Admin":"NotAdmin")." ".($canMakeRequests?"CanMakeRequests":"NoMakeRequests")." but already done");
			}
			return false;
		}
	}

	public function removeUser(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		// TODO should me be setting some column rather than deleteing so we have more history in the DB?

		$db = getDB();
		$s = $db->prepare("DELETE FROM user_in_group WHERE user_account_id=:ua AND support_group_id=:sg");
		$s->execute(array(
				'ua'=>$ua->getId(),
				'sg'=>$this->id
			));
		logInfo("Removing User:".$ua->getId()." from SupportGroup:".$this->id);
	}

	/** User passed must be in group already or this has no effect.
	 */
	public function allowAdmin(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_in_group SET can_make_requests=true, is_admin=true WHERE user_account_id = :ua AND support_group_id = :sg");
		$s->execute(array('ua'=>$ua->getId(),'sg'=>$this->id));
		logInfo("Allowing Admin access to SupportGroup:".$this->id." for User:".$ua->getId());
	}
	/** User passed must be in group already or this has no effect.
	 */
	public function revokeAdmin(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_in_group SET is_admin=false WHERE user_account_id = :ua AND support_group_id = :sg");
		$s->execute(array('ua'=>$ua->getId(),'sg'=>$this->id));
		logInfo("Revoking Admin access to SupportGroup:".$this->id." for User:".$ua->getId());
	}
	/** User passed must be in group already or this has no effect.
	 */
	public function allowMakeRequests(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_in_group SET can_make_requests=true WHERE user_account_id = :ua AND support_group_id = :sg");
		$s->execute(array('ua'=>$ua->getId(),'sg'=>$this->id));
		logInfo("Allow making requests in SupportGroup:".$this->id." for User:".$ua->getId());
	}
	/** User passed must be in group already or this has no effect.
	 * If user is admin this has no effect.
	 */
	public function revokeMakeRequests(UserAccount $ua) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("UPDATE user_in_group SET can_make_requests=false WHERE user_account_id = :ua AND support_group_id = :sg AND is_admin = 'f'");
		$s->execute(array('ua'=>$ua->getId(),'sg'=>$this->id));
		logInfo("Revoke making requests in SupportGroup:".$this->id." for User:".$ua->getId());
	}
	
	public function getId() { return $this->id; }
	public function getWhiteLabelId() { return $this->white_label_id; }
	public function getWhiteLabel() { return WhiteLabel::findByID($this->white_label_id); }
	public function getTitle() { return $this->title; }
	public function getDescription() { return $this->description; }
	public function getCreatedAt() { return $this->created_at; }
	public function getDeletedAt() { return $this->deleted_at; }
	public function getSysAdminLabel() { return $this->sys_admin_label; }
	public function isDeleted() { return (Boolean)$this->deleted_at; }
	public function isPremium() { return $this->is_premium; }
	public function isAdmin() { return $this->is_admin; }
	public function canMakeRequests() { return $this->can_make_requests; }


	public function addRequestType($type) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("INSERT INTO request_type (title,support_group_id,created_at) VALUES(:title,:sg,:created_at)");
		$s->execute(array('title'=>$type,'sg'=>$this->id,'created_at'=>date("Y-m-d H:i:s", getCurrentTime())));
		logInfo("Adding to SupportGroup:".$this->id." new RequestType:".$type);
	}

	public function getMembers() {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		// This is duplicated (kinda) in Request->getToUsers()

		$db = getDB();
		$s = $db->prepare("SELECT user_account.*, user_in_group.is_admin, user_in_group.can_make_requests FROM  user_account ".
				"JOIN user_in_group ON user_in_group.user_account_id = user_account.id WHERE user_in_group.support_group_id = :id ORDER BY user_account.display_name");
		$s->execute(array('id'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new UserAccount ($d);
		return $out;
	}

	public function getActiveRequestTypes() {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type WHERE request_type.support_group_id = :id AND request_type.is_active = true ORDER BY request_type.title ASC");
		$s->execute(array('id'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new RequestType($d);
		return $out;
	}

	public function getRequestTypes() {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request_type.* FROM request_type WHERE request_type.support_group_id = :id ORDER BY request_type.title ASC");
		$s->execute(array('id'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new RequestType($d);
		return $out;
	}

	/** 
	 * If User is only admin in a group, then they aren't joining it, they are setting in up. 
	 * Function used on confirmEmail pages to decide what message to show to users. 
	 * **/
	public function isUserSettingUp(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT COUNT(user_account.*) AS c FROM  user_account ".
				"JOIN user_in_group ON user_in_group.user_account_id = user_account.id ".
				"WHERE user_in_group.support_group_id = :id AND user_account.id != :uid AND user_in_group.is_admin = 't'");
		$s->execute(array('id'=>$this->id,'uid'=>$user->getId()));
		$d = $s->fetch();
		return ($d['c'] == 0);		
	}
	
	public function newRequest(UserAccount $user, $summary, $message, $typeIDS, $userIDS=null) {
		// TODO duplicate posting detection!
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');
		// we are not always loaded with permissions! Only when loaded from users
		if (!is_null($this->can_make_requests) && !$this->canMakeRequests()) throw new Exception ('No Permissions');

		$db = getDB();
		$ps = getPheanstalk();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("INSERT INTO request (summary,request,support_group_id,created_at,created_by_user_id) ".
					"VALUES (:summary, :request,:support_group_id,:created_at,:created_by_user_id) RETURNING id");
			$stat->bindValue('request', $message);
			$stat->bindValue('summary', substr($summary,0,140));
			$stat->bindValue('support_group_id', $this->id);
			$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$stat->bindValue('created_by_user_id', $user->getId());
			$stat->execute();
			$d = $stat->fetch();
			$id = $d['id'];

			// Ensure these categories are actually in this group
			$stat = $db->prepare("INSERT INTO request_has_type (request_id,request_type_id) ".
					"SELECT ".$id.", request_type.id FROM  request_type WHERE request_type.id = :id AND request_type.support_group_id = :sid");
			foreach($typeIDS as $tid) {
				$stat->execute(array('id'=>$tid,'sid'=>$this->id));
			}


			if (is_array($userIDS) && count($userIDS) > 0 ) {
				// Only some users ... ensure these users are in this group as we add them.
				$stat = $db->prepare("INSERT INTO request_to_user (request_id,user_account_id) ".
						"SELECT ".$id.", user_in_group.user_account_id FROM  user_in_group WHERE user_in_group.user_account_id = :id AND user_in_group.support_group_id = :sid");
				foreach($userIDS as $uid) {
					$stat->execute(array('id'=>$uid,'sid'=>$this->id));
				}
			} else {
				// it's to all users!
				$stat = $db->prepare("UPDATE request SET to_all_members=TRUE WHERE id=:id");
				$stat->execute(array('id'=>$id));
			}
			
			$db->commit();			
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}

		logInfo("In SupportGroup:".$this->id." new Request:".$id);
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewRequest","requestID"=>$id)),1000,5);
		return $id;
	}
	
	public function newRequestFromSavedRequest(SavedRequest $savedRequest, UserAccount $user) {
		// TODO duplicate posting detection!
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');
		// we are not always loaded with permissions! Only when loaded from users
		if (!is_null($this->can_make_requests) && !$this->canMakeRequests()) throw new Exception ('No Permissions');

		$db = getDB();
		$ps = getPheanstalk();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("INSERT INTO request (summary,request,support_group_id,created_at,created_by_user_id,from_saved_request_id) ".
					"VALUES (:summary, :request,:support_group_id,:created_at,:created_by_user_id,:from_saved_request_id) RETURNING id");
			$stat->bindValue('from_saved_request_id', $savedRequest->getId());
			$stat->bindValue('request', $savedRequest->getRequest());
			$stat->bindValue('summary', substr($savedRequest->getSummary(),0,140));
			$stat->bindValue('support_group_id', $this->id);
			$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$stat->bindValue('created_by_user_id', $user->getId());
			$stat->execute();
			$d = $stat->fetch();
			$id = $d['id'];

			$stat = $db->prepare("UPDATE request SET to_all_members=TRUE WHERE id=:id");
			$stat->execute(array('id'=>$id));
			
			$db->commit();			
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}

		logInfo("In SupportGroup:".$this->id." new Request:".$id." from SavedRequest:".$savedRequest->getId());
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewRequest","requestID"=>$id)),1000,5);
		return $id;
	}
	
	
	public function newSavedRequest(UserAccount $user, $summary, $message) {
		// TODO duplicate posting detection!
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');
		// we are not always loaded with permissions! Only when loaded from users
		if (!is_null($this->can_make_requests) && !$this->canMakeRequests()) throw new Exception ('No Permissions');

		$db = getDB();
		try {
			$db->beginTransaction();

			$stat = $db->prepare("INSERT INTO saved_request (summary,request,support_group_id,created_at,created_by_user_id,updated_at,updated_by_user_id) ".
					"VALUES (:summary, :request,:support_group_id,:created_at,:created_by_user_id,:created_at,:created_by_user_id) RETURNING id");
			$stat->bindValue('request', $message);
			$stat->bindValue('summary', substr($summary,0,140));
			$stat->bindValue('support_group_id', $this->id);
			$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$stat->bindValue('created_by_user_id', $user->getId());
			$stat->execute();
			$d = $stat->fetch();
			$id = $d['id'];

			$db->commit();			
		} catch (Exception $e) {
			$db->rollBack();
			throw $e;
		}

		logInfo("In SupportGroup:".$this->id." new SavedRequest:".$id);
		return $id;
	}
	
	
	public function newNewsArticle(UserAccount $user, $summary, $body) {
		// TODO duplicate posting detection!
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		// TODO if no summary put in first 140 chars of body ....
		
		$db = getDB();
		$ps = getPheanstalk();
		try {
			//$db->beginTransaction();

			$stat = $db->prepare("INSERT INTO support_group_news_article (summary,body,support_group_id,created_at,created_by_user_id) ".
					"VALUES (:summary, :body,:support_group_id,:created_at,:created_by_user_id) RETURNING id");
			$stat->bindValue('body', $body);
			$stat->bindValue('summary', substr($summary,0,140));
			$stat->bindValue('support_group_id', $this->id);
			$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
			$stat->bindValue('created_by_user_id', $user->getId());
			$stat->execute();
			$d = $stat->fetch();
			$id = $d['id'];

			
			//$db->commit();			
		} catch (Exception $e) {
			//$db->rollBack();
			throw $e;
		}

		logInfo("In SupportGroup:".$this->id." new SupportGroupNewsArticle:".$id);
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewSupportGroupNewsArticle","supportGroupNewsArticleID"=>$id)),1000,5);
		return $id;
	}


	public function getNotificationsVisibleToUser(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid ORDER BY request.created_at DESC LIMIT 10");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new Request($d);

		$s = $db->prepare("SELECT request_response.*, user_account_created.display_name AS display_name, user_account_created.avatar_key AS avatar_key FROM request_response ".
				" JOIN request ON request_response.request_id = request.id ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request_response.user_account_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid ORDER BY request_response.created_at DESC LIMIT 10");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		while($d = $s->fetch()) $out[] = new RequestResponse($d);
		
		$s = $db->prepare("SELECT support_group_news_article.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key FROM support_group_news_article ".
				"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article.created_by_user_id " .
				"WHERE support_group_news_article.support_group_id = :sgid ORDER BY support_group_news_article.created_at DESC LIMIT 10");
		$s->execute(array('sgid'=>$this->id));
		while($d = $s->fetch()) $out[] = new SupportGroupNewsArticle($d);

		$s = $db->prepare("SELECT support_group_news_article_response.*, user_account_created.display_name AS display_name, user_account_created.avatar_key AS avatar_key FROM support_group_news_article_response ".
				" JOIN support_group_news_article ON support_group_news_article.id = support_group_news_article_response.support_group_news_article_id ".
				"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article_response.user_account_id " .
				"WHERE support_group_news_article.support_group_id = :sgid ORDER BY support_group_news_article_response.created_at DESC LIMIT 10");
		$s->execute(array('sgid'=>$this->id));
		while($d = $s->fetch()) $out[] = new SupportGroupNewsArticleResponse($d);

		$cmp = function ($a, $b) {
			$aTime = $a->getCreatedAtInSeconds();
			$bTime = $b->getCreatedAtInSeconds();
			if ($aTime == $bTime) return 0;
    		return ($aTime > $bTime) ? -1 : 1;
		};

		usort($out, $cmp);

		return array_slice($out,0,10);
	}
	
	
	public function getRequestsVisibleToUser(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key,".
				"user_account_closed.display_name AS closed_by_display_name,  user_account_cancelled.display_name AS cancelled_by_display_name FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"LEFT JOIN user_account AS user_account_closed ON user_account_closed.id = request.closed_by_user_id " .
				"LEFT JOIN user_account AS user_account_cancelled ON user_account_cancelled.id = request.cancelled_by_user_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid ORDER BY created_at DESC");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new Request($d);
		return $out;
	}

	public function getOpenRequestsVisibleToUser(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid AND request.closed_at IS NULL AND request.cancelled_at IS NULL ORDER BY created_at DESC");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new Request($d);
		return $out;
	}
	
	

	/** This sorts open requests at top, then sorts by date **/
	public function getHomePageRequestsVisibleToUser(UserAccount $user, $limit=30) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid ".
				"ORDER BY (CASE WHEN request.closed_at IS NOT NULL THEN 1 WHEN request.cancelled_at IS NOT NULL THEN 1 ELSE 0 END) ASC, created_at DESC LIMIT ".$limit);
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new Request($d);
		return $out;
	}
	
	public function getSavedRequestsVisibleToUser(UserAccount $user, $limit=50) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT saved_request.* FROM saved_request ".
				"WHERE saved_request.created_by_user_id = :uid AND saved_request.support_group_id = :sgid ".
				"ORDER BY saved_request.created_at ASC LIMIT ".$limit);
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new SavedRequest($d);
		return $out;
	}

	public function getOpenRequestsVisibleToUserCount(UserAccount $user) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT COUNT(request.id) AS c FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) AND request.support_group_id = :sgid AND request.closed_at IS NULL AND request.cancelled_at IS NULL");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id));
		$d = $s->fetch();
		return $d['c'];
	}

	/** actually all requests are on calendar - if no special date just use creation date **/
	public function getRequestsVisibleToUserOnCalendar(UserAccount $user, $start, $end) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name, user_account_created.avatar_key AS created_by_avatar_key FROM request ".
				"LEFT JOIN request_to_user ON request_to_user.request_id = request.id AND request_to_user.user_account_id = :uid ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"WHERE (request.created_by_user_id = :uid OR request.to_all_members = TRUE OR request_to_user.user_account_id = :uid) ".
				"AND (CASE WHEN request.start_at IS NOT NULL THEN request.start_at ELSE request.created_at END) >= :start ".
				"AND (CASE WHEN request.start_at IS NOT NULL THEN request.start_at ELSE request.created_at END) <= :end ".
				"AND request.support_group_id = :sgid ".
				"ORDER BY start_at ASC");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id,'start'=>date("Y-m-d H:i:s",$start),'end'=>date("Y-m-d H:i:s",$end)));
		$out = array();
		while($d = $s->fetch()) $out[] = new Request($d);
		return $out;
	}

	/**   **/
	public function getHolidaysVisibleToUserOnCalendar(UserAccount $user, $start, $end) {
		if (is_null($this->id)) throw new Exception ('No Support Group Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT user_on_holiday.*, user_account.display_name FROM user_on_holiday ".
				"JOIN user_account ON user_account.id = user_on_holiday.user_account_id ".
				"JOIN user_in_group ON  user_in_group.user_account_id = user_on_holiday.user_account_id ".
				"WHERE user_in_group.support_group_id= :sgid ".
				"AND (user_on_holiday.share_with_group = 't' OR user_on_holiday.user_account_id = :uid) ".
				"AND user_on_holiday.holiday_to >= :start ".
				"AND user_on_holiday.holiday_from <= :end ".
				"ORDER BY user_on_holiday.holiday_from ASC");
		$s->execute(array('uid'=>$user->getId(),'sgid'=>$this->id,'start'=>date("Y-m-d H:i:s",$start),'end'=>date("Y-m-d H:i:s",$end)));
		$out = array();
		while($d = $s->fetch()) $out[] = new UserOnHoliday($d);
		return $out;
	}

	public function getAvatarURL() {
		if (isset($this->avatar_key) && $this->avatar_key) {
			return "/avatars/".$this->avatar_key.".jpg";
		} else {
			return "/images/groupAvatar.png";
		}
	}

	public function updateDetails($title,$description='') {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->title = $title;
		$this->description = $description;

		$db = getDB();
		$s = $db->prepare("UPDATE support_group SET title = :t, description =:d WHERE id = :id");
		$s->execute(array('id'=>$this->id,'t'=>$this->title,'d'=>$this->description));
		logInfo("Updating details of SuppportGroup:".$this->id);
	}

	public function delete(UserAccount $userBy = null) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->deleted_at = getCurrentTime();

		$db = getDB();
		$s = $db->prepare("UPDATE support_group SET deleted_at =:d WHERE id = :id");
		$s->execute(array('id'=>$this->id,'d'=>date('Y-m-d H:i:s', getCurrentTime())));
		if ($userBy) {
			logInfo("DELETING SuppportGroup:".$this->id. " by User:".$userBy->getId());
		} else {
			logInfo("DELETING SuppportGroup:".$this->id. " by unknown user");			
		}
	}

	public function makePremium() {
		if (is_null($this->id)) throw new Exception ('No Loaded');
		$db = getDB();
		$s = $db->prepare("UPDATE support_group SET is_premium = true WHERE id = :id");
		$s->execute(array('id'=>$this->id));
		logInfo("Making SuppportGroup:".$this->id." premium");
		$this->is_premium = true;
	}

	/** Code pretty much duplicated in USerAccount for now **/
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

		$scale = max(1,max($x/60, $y/60));
		list($tX, $tY) = array(intval($x/$scale), intval($y/$scale));
		$image = imagecreatetruecolor($tX, $tY);
		imagecopyresampled($image, $uploadedImage, 0, 0, 0, 0, $tX, $tY, $x, $y);
		imagejpeg($image,$newImagePath,80);

		$db = getDB();
		$stat = $db->prepare('UPDATE support_group SET avatar_key=:avatar_key WHERE id=:id');
		$stat->execute(array('id'=>$this->id,'avatar_key'=>$this->avatar_key));

		unlink($fullFileName);

		if ($existingSlug) {
			$existingImagePath = dirname(__FILE__)."/../public_html/avatars/".$existingSlug.".jpg";
			if (is_file($existingImagePath)) unlink($existingImagePath);
		}
	}

	public function updateSysAdminLabel($label) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->sys_admin_label = $label;

		$db = getDB();
		$s = $db->prepare("UPDATE support_group SET sys_admin_label =:s WHERE id = :id");
		$s->execute(array('id'=>$this->id,'s'=>$this->sys_admin_label));
		logInfo("Updating sys admin label of SuppportGroup:".$this->id);
	}

	public function setWhiteLabel(WhiteLabel $whiteLabel) {
		if (is_null($this->id)) throw new Exception ('No Loaded');

		$this->white_label_id = $whiteLabel->getId();

		$db = getDB();
		$s = $db->prepare("UPDATE support_group SET white_label_id =:wlid WHERE id = :id");
		$s->execute(array('id'=>$this->id,'wlid'=>$this->white_label_id));
		logInfo("Updating whitelabel of SuppportGroup:".$this->id);
	}
}



