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


class SupportGroupNewsArticle {

	private $id;
	private $summary;
	private $body;
	private $created_at;
	private $support_group_id;
	private $created_by_display_name;
	private $created_by_avatar_key;
	private $created_by_user_id;
	
	
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['summary'])) $this->summary = $data['summary'];
		if (isset($data['body'])) $this->body = $data['body'];
		if (isset($data['created_at'])) $this->created_at = strtotime($data['created_at']);
		if (isset($data['support_group_id'])) $this->support_group_id = $data['support_group_id'];		
		if (isset($data['created_by_display_name'])) $this->created_by_display_name = $data['created_by_display_name'];
		if (isset($data['created_by_avatar_key'])) $this->created_by_avatar_key = $data['created_by_avatar_key'];
		if (isset($data['created_by_user_id'])) $this->created_by_user_id = $data['created_by_user_id'];
	}	
	
	/** @return SupportGroupNewsArticle **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group_news_article.*,  ".
				"user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key ".
				"FROM support_group_news_article ".
				"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article.created_by_user_id " .
				"WHERE support_group_news_article.id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new SupportGroupNewsArticle($s->fetch());
		}
	}

	/** @return SupportGroupNewsArticle **/
	public static function findByIDForUser($id, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group_news_article.*,  ".
				"user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key ".
				"FROM support_group_news_article ".
				"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article.created_by_user_id " .
				"JOIN user_in_group ON user_in_group.support_group_id = support_group_news_article.support_group_id ".
				"WHERE user_in_group.user_account_id = :uid AND support_group_news_article.id = :id");
		$s->execute(array('id'=>$id,'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			$data = $s->fetch();
			return new SupportGroupNewsArticle($data);
		}
	}
	
	
	
	public function getId() { return $this->id; }
	public function getSummary() { return $this->summary; }
	public function getBody() { return $this->body; }
	
	public function getCreatedAt  () { return $this->created_at; }
	public function getCreatedAtInSeconds() { return $this->created_at; }
	public function getAgeFromCreatedAtInSeconds() { return getCurrentTime() - $this->created_at; }	
	
	public function getSupportGroupId  () { return $this->support_group_id; }
	public function getCreatedByUserId  () { return $this->created_by_user_id; }	
	public function getCreatedByDisplayName  () { return $this->created_by_display_name; }
	public function getCreatedByAvatarURL() {
		if (isset($this->created_by_avatar_key) && $this->created_by_avatar_key) {
			return "/avatars/".$this->created_by_avatar_key.".jpg";
		} else {
			return "/images/avatar.png";
		}
	}
	
	public function newResponse($response, UserAccount $from) {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$stat = $db->prepare("INSERT INTO support_group_news_article_response (support_group_news_article_id,user_account_id,created_at,response) ".
				"VALUES (:support_group_news_article_id,:user_account_id,:created_at,:response) RETURNING id");
		$stat->bindValue('support_group_news_article_id', $this->id);
		$stat->bindValue('created_at', date("Y-m-d H:i:s", getCurrentTime()));
		$stat->bindValue('user_account_id', $from->getId());
		$stat->bindValue('response', $response);
		$stat->execute();
		$d = $stat->fetch();
		
		logInfo("New SupportGroupNewsArticleResponse:".$d['id']." to SupportGroupNewsArticle:".$this->id." from User:".$from->getId());
		
		$ps = getPheanstalk();
		if ($ps) $ps->useTube(BEANSTALKD_QUE)->put(json_encode(array("type"=>"NewSupportGroupNewsArticleResponse","supportGroupNewsArticleResponseID"=>$d['id'])),1000,5);
		
		return $d['id'];
	}	
	
	public function getResponses() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$db = getDB();
		$s = $db->prepare("SELECT support_group_news_article_response.*, user_account.display_name , user_account.avatar_key FROM support_group_news_article_response ".
				"JOIN user_account ON user_account.id = support_group_news_article_response.user_account_id ".
				"WHERE support_group_news_article_response.support_group_news_article_id  = :sgnaid ORDER BY support_group_news_article_response.created_at ASC");
		$s->execute(array('sgnaid'=>$this->id));
		$out = array();
		while($d = $s->fetch()) $out[] = new SupportGroupNewsArticleResponse($d);
		return $out;
	}
	
	/**
	 * At moment news items are to all users in group; 
	 * but may add news to only some users later so put this in seperate function for modularity and testing.
	 * Does not include the user who sent the request.
	 */
	public function getToUsers() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');

		$supportGroup = SupportGroup::findByID($this->support_group_id);
		
		$out = array();
		foreach ($supportGroup->getMembers() as $user) {
			if ($user->getId() != $this->created_by_user_id) {
				$out[] = $user;
			}
		}
		return $out;
			
	}	
	
	/** Gets user whe posted news and users who posted any responses **/
	public function getUsersInvolved() {
		if (is_null($this->id)) throw new Exception ('Not Loaded');


		$out = array();
		$out[$this->created_by_user_id] = UserAccount::findByID($this->created_by_user_id);
		
		// TODO could do this more efficiently by not loading responses and not loading same user more than once.
		foreach($this->getResponses() as $response) {
			$out[$response->getCreatedByUserId()] = UserAccount::findByID($response->getCreatedByUserId());
		}

		return array_values($out);
			
	}		
	
	protected function getNotifyData() {
		
		$notifyData = array();
		foreach($this->getToUsers() as $member) {
			if ($member->getId() != $this->created_by_user_id) {
				$d = buildNotifyDataForSupportGroupNewsArticle($member);
				if ($d) $notifyData[$member->getId()] = $d;
			}
		}
		return $notifyData;
		
	}
	
	
	public function notifyPeopleAfterCreation() {

		
		$notifyData = $this->getNotifyData();

		$supportGroup = SupportGroup::findByID($this->support_group_id);

		$db = getDB();
		$statEmail = $db->prepare("INSERT INTO support_group_news_article_sent_to_user_email (support_group_news_article_id,user_email_id,sent_at) VALUES (:sgnaid,:ueid, :at) ");



		foreach($notifyData as $data) {

			foreach($data['sendToEmails'] as $id=>$flag) {
				if ($flag) {

					$tpl = getEmailSmarty();
					$tpl->assign('member',$data['member']);
					$tpl->assign('email',$data['emails'][$id]);
					$tpl->assign('supportGroup',$supportGroup);
					$tpl->assign('supportGroupNewsArticle',$this);
					$body = null;
					if ($data['emails'][$id]->isConfirmed()) { 
						$body = $tpl->fetch('notifyNewSupportGroupNewsArticle.email.txt');
					} else if (!$data['emails'][$id]->isConfirmed() && $data['emails'][$id]->getSendBeforeConfirmation()) {
						$body = $tpl->fetch('notifyNewSupportGroupNewsArticle.notConfirmed.email.txt');
					}
					
					//print $body;
					if ($body) {
						mail($data['emails'][$id]->getEmail(), "News [NEWS#".$this->id."] ".$this->summary, $body, "From: ".EMAILS_FROM."\r\nReply-To: ".EMAIL_IN);
						logInfo("Notifying User:".$data['member']->getId()." about Request:".$this->id." by UserEmail:".$id);
						$statEmail->execute(array('sgnaid'=>$this->id,'ueid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
					}
				}
			}

		}

	}
	
}

