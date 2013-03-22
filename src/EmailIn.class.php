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

class EmailIn {


	protected $subject;
	protected $from;
	protected $body;

	protected $fromEmailAddressOnly;
	/** @var UserAccount **/
	protected $userAccount;

	protected $reply;

	protected $requestID;
	/** @var Request **/
	protected $request;	
	
	protected $supportGroupNewsArticleID;
	/** @var SupportGroupNewsArticle **/
	protected $supportGroupNewsArticle;	
	
	public function  __construct($from, $subject, $body) {
		$this->from = $from;
		$this->subject = $subject;
		$this->body = $body;
	}


	public function parse() {

		$this->findObjectID();
		$this->parseEmailAddress();
		$this->parseReply();

		if ($this->fromEmailAddressOnly) $this->userAccount = UserAccount::findByEmail($this->fromEmailAddressOnly);
		if ($this->userAccount && $this->requestID)	$this->request = Request::findByIDForUser($this->requestID, $this->userAccount);
		if ($this->userAccount && $this->supportGroupNewsArticleID)	$this->supportGroupNewsArticle = SupportGroupNewsArticle::findByIDForUser($this->supportGroupNewsArticleID, $this->userAccount);

	}

	protected function findObjectID() {
		
		// Look for link to Request in subject, new way of [REQ#2]
		$r = array();
		preg_match("/\[REQ\#(\d+)\]/", $this->subject, $r);
		if (count($r) == 2 && $r[1]) {
			$this->requestID =  $r[1];
			return;
		}
		
		// Look for link to Support Group News Article in subject, new way of [NEWS#2]
		$r = array();
		preg_match("/\[NEWS\#(\d+)\]/", $this->subject, $r);
		if (count($r) == 2 && $r[1]) {
			$this->supportGroupNewsArticleID =  $r[1];
			return;
		}
		
		// Look for link to Request in subject. old way [#2]  --- depreceated, will remove soon.
		$r = array();
		preg_match("/\[\#(\d+)\]/", $this->subject, $r);
		if (count($r) == 2 && $r[1]) {
			$this->requestID =  $r[1];
			return;
		}
		
		// should we be looking for any details in email body or is that to much of a minefield? We'll try for now.
		
		// Look for link to Request in body text.
		$r = array();
		preg_match("/\/request\.php\?id\=(\d+)/", $this->body, $r);
		if (count($r) == 2 && $r[1]) {
			$this->requestID = $r[1];
			return;
		}
		
		// Look for link to Support Group News Article in body text.
		$r = array();
		preg_match("/\/supportGroupNewsArticle\.php\?id\=(\d+)/", $this->body, $r);
		if (count($r) == 2 && $r[1]) {
			$this->supportGroupNewsArticleID = $r[1];
			return;
		}

		return null;
	}

	protected function parseEmailAddress() {

		if (strpos($this->from, "<") === false) {
			$this->fromEmailAddressOnly = $this->from;
		} else {
			$bits1 = explode("<", $this->from);
			$bits2 = explode(">", $bits1[1]);
			$this->fromEmailAddressOnly = $bits2[0];
		}
	}

	protected function parseReply() {

		$lines = explode("\n",  str_replace("\r", "", $this->body));
		$out = '';
		foreach ($lines as $line) {
			if (substr($line, 0,1) != ">") {
				$out .= rtrim($line)."\n";
			}
		}
		$this->reply = trim($out);

	}

	public function getReply() { return $this->reply; }
	/** @return Request **/
	public function getRequest() { return $this->request; }
	/** @return SupportGroupNewsArticle **/
	public function getSupportGroupNewsArticle() { return $this->supportGroupNewsArticle; }
	/** @return UserAccount **/
	public function getUserAccount() { return $this->userAccount; }
	

}