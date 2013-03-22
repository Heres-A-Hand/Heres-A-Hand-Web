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
 * @package Test
 */

require dirname(__FILE__).'/../configTest.php';
if (!defined('DEFAULT_TIME_ZONE')) define('DEFAULT_TIME_ZONE', 'Europe/London');

require dirname(__FILE__).'/../src/globalFuncs.php';



class AbstractTest extends PHPUnit_Framework_TestCase {

	function setUp() {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = null;
	}
	
	function setupDB() {
		$db = getDB();
		$db->exec(file_get_contents(dirname(__FILE__).'/../sql/destroy.sql'));
		$db->exec(file_get_contents(dirname(__FILE__).'/../sql/create.sql'));
		return $db;
	}

}


class EmailInTestObject extends EmailIn {
	public function getRequestID() { return $this->requestID; }
	public function getSupportGroupNewsArticleID() { return $this->supportGroupNewsArticleID; }	
	public function getEmailAdrressOnly() { return $this->fromEmailAddressOnly; }
	
	public function testParse() {
		$this->findObjectID();
		$this->parseEmailAddress();
		$this->parseReply();
	}
}

class RequestTestObject extends Request {
	
	/** @return RequestTestObject **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT request.*, user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key, user_account_closed.display_name AS closed_by_display_name FROM request ".
				"JOIN user_account AS user_account_created ON user_account_created.id = request.created_by_user_id " .
				"LEFT JOIN user_account AS user_account_closed ON user_account_closed.id = request.closed_by_user_id " .
				"WHERE request.id = :id");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new RequestTestObject($s->fetch());
		}
	}
	
	public function callGetNotifyData() { return $this->getNotifyData(); }
	
}

class RequestResponseTestObject extends RequestResponse {
	
	/** @return RequestResponseTestObject **/
	public static function findByID($id) {
		$db = getDB();
		$s = $db->prepare("SELECT request_response.*, user_account.display_name , user_account.avatar_key".
			" FROM request_response ".
			" JOIN user_account ON user_account.id = request_response.user_account_id  ".
			" WHERE request_response.id = :id ");
		$s->execute(array('id'=>$id));
		if ($s->rowCount() == 1) {
			return new RequestResponseTestObject($s->fetch());
		}
	}
	
	public function callGetNotifyData() { return $this->getNotifyData(); }
	
}




