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

class SupportGroupNewsArticleResponse  {
	

	private $id;
	private $support_group_news_article_id;
	private $user_account_id;
	private $created_at;
	private $response;
	private $display_name;
	private $avatar_key;

	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['support_group_news_article_id'])) $this->support_group_news_article_id = $data['support_group_news_article_id'];
		if (isset($data['user_account_id'])) $this->user_account_id = $data['user_account_id'];
		if (isset($data['created_at'])) $this->created_at = $data['created_at'];
		if (isset($data['response'])) $this->response = $data['response'];
		if (isset($data['display_name'])) $this->display_name = $data['display_name'];
		if (isset($data['avatar_key'])) $this->avatar_key = $data['avatar_key'];
	}
	
	/** @return SupportGroupNewsArticleResponse **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT support_group_news_article_response.*,  ".
				"user_account_created.display_name AS display_name,  user_account_created.avatar_key AS avatar_key ".
				"FROM support_group_news_article_response ".
				"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article_response.user_account_id " .
				"WHERE support_group_news_article_response.id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new SupportGroupNewsArticleResponse($s->fetch());
		}
	}

	public function getId() { return $this->id; }
	public function getSupportGroupNewsArticleId() { return $this->support_group_news_article_id; }
	public function getResponse() { return $this->response; }
	public function getCreatedAt() { return $this->created_at; }
	public function getCreatedAtInSeconds() { return strtotime($this->created_at); }
	public function getAgeInSeconds() { return getCurrentTime() - strtotime($this->created_at); }
	public function getCreatedByUserId  () { return $this->user_account_id; }	
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
		// for efficiency cache this data outside the loop.
		$supportGroupNewsArticle = SupportGroupNewsArticle::findByID($this->support_group_news_article_id);
		$supportGroup = SupportGroup::findByID($supportGroupNewsArticle->getSupportGroupId());
		$notifyData = array();

		// Notify everyone involved (but not if its the same person who commented!)
		foreach($supportGroupNewsArticle->getUsersInvolved() as $user) {
			if ($user->getId() != $this->user_account_id) {
				$d = buildNotifyDataForSupportGroupNewsArticle($user);
				if ($d) $notifyData[$user->getId()] = $d;
			}
		}
		
		return $notifyData;
	}
	
	public function notifyPeopleAfterCreation() {
		$supportGroupNewsArticle = SupportGroupNewsArticle::findByID($this->support_group_news_article_id);
		$supportGroup = SupportGroup::findByID($supportGroupNewsArticle->getSupportGroupId());
		$notifyData = $this->getNotifyData();

		$db = getDB();
		$statEmail = $db->prepare("INSERT INTO support_group_news_article_response_sent_to_user_email (support_group_news_article_response_id,user_email_id,sent_at) VALUES (:sgnarid,:ueid, :at) ");

		foreach($notifyData as $data) {

			foreach($data['sendToEmails'] as $id=>$flag) {
				if (true) {  // We don't check $flag! We ignore the users preferences and always send them replies by email!

					$tpl = getEmailSmarty();
					$tpl->assign('member',$data['member']);
					$tpl->assign('email',$data['emails'][$id]);
					$tpl->assign('supportGroup',$supportGroup);
					$tpl->assign('supportGroupNewsArticle',$supportGroupNewsArticle);
					$tpl->assign('supportGroupNewsArticleResponse',$this);
					$body = null;
					if ($data['emails'][$id]->isConfirmed()) { 
						$body = $tpl->fetch('notifyNewSupportGroupNewsArticleResponse.email.txt');
					} else if (!$data['emails'][$id]->isConfirmed() && $data['emails'][$id]->getSendBeforeConfirmation()) {
						// this can't happen at the moment, we only notify ppl involved. May happen in future.
					}
					//print $body;
					if ($body) {
						mail($data['emails'][$id]->getEmail(), "New Response to News [NEWS#".$this->support_group_news_article_id."]", $body, "From: ".EMAILS_FROM."\r\nReply-To: ".EMAIL_IN);
						logInfo("Notifying User:".$data['member']->getId()." about SupportGroupNewsArticleResponse:".$this->id." by UserEmail:".$id);
						$statEmail->execute(array('sgnarid'=>$this->id,'ueid'=>$id, 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
					}
				}
			}
		}
	}
			
	
}



