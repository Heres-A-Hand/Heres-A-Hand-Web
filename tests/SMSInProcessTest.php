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

class SMSInProcessTest  extends AbstractTest {
	
	function testResponse() {
		$this->setupDB();

		$user = UserAccount::findByTelephoneOrCreate(1, "7764970336");
		$userTelephone = UserTelephone::findByCountryIDandTelphone(1, "7764970336");
		$this->assertNotNull($userTelephone);
		
		$supportGroup = SupportGroup::create("Test");
		$supportGroup->addUser($user);
		$requestID = $supportGroup->newRequest($user, "oeeou", "eouoeu", array(), array($user->getId()));
		
		// now some fakery; pretend we sent texted this to user (yes, texting to yourself is odd)
		$db = getDB();
		$statTelephone = $db->prepare("INSERT INTO request_sent_to_user_telephone (request_id,user_telephone_id,sent_at) VALUES (:rid,:utid, :at) ");
		$statTelephone->execute(array('rid'=>$requestID,'utid'=>$userTelephone->getId(), 'at'=>date("Y-m-d H:i:s", getCurrentTime())));

		
		$smsID = SMSIn::create("+447764970336", "TEST");
		$smsIn = SMSIn::findByID($smsID);
		
		// now finally the real test
		$smsIn->process();		
		
		// check the results; do we have an response saved!
		$request = Request::findByID($requestID);
		$this->assertNotNull($request);
		$requestResponses = $request->getResponses();
		
		$this->assertEquals(1, count($requestResponses));
		
		// check the results; does SMS table have details saved!
		$db = getDB();
		$s = $db->prepare("SELECT sms_in.* FROM sms_in ");
		$s->execute();
		$d = $s->fetch();
		
		$this->assertEquals($userTelephone->getId(), $d['user_telephone_id']);
		$this->assertEquals($requestResponses[0]->getId(), $d['request_response_id']);
		$this->assertEquals(null, $d['request_id']);
		
		
	}
	
	
	function testSpecificResponse() {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = mktime(17, 00, 00, 1, 1, 2012);
		
		$this->setupDB();

		$user = UserAccount::findByTelephoneOrCreate(1, "7764970336");
		$userTelephone = UserTelephone::findByCountryIDandTelphone(1, "7764970336");
		$this->assertNotNull($userTelephone);
		
		$supportGroup = SupportGroup::create("Test");
		$supportGroup->addUser($user);
		$requestID = $supportGroup->newRequest($user, "oeeou", "eouoeu", array(), array($user->getId()));
		
		$requestID2 = $supportGroup->newRequest($user, "cats", "dogs", array(), array($user->getId()));
		
		// now some fakery; pretend we sent texted this to user (yes, texting to yourself is odd)
		$db = getDB();
		$statTelephone = $db->prepare("INSERT INTO request_sent_to_user_telephone (request_id,user_telephone_id,sent_at) VALUES (:rid,:utid, :at) ");
		$MOCK_CURRENT_TIME = mktime(17, 00, 00, 1, 1, 2012);
		$statTelephone->execute(array('rid'=>$requestID,'utid'=>$userTelephone->getId(), 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
		$MOCK_CURRENT_TIME = mktime(17, 02, 00, 1, 1, 2012);
		$statTelephone->execute(array('rid'=>$requestID2,'utid'=>$userTelephone->getId(), 'at'=>date("Y-m-d H:i:s", getCurrentTime())));
		$MOCK_CURRENT_TIME = mktime(17, 04, 00, 1, 1, 2012);

		// This SMS is specifically to Request1! Without the @ sign stuff it would go to request2 as that was the last one sent.
		$smsID = SMSIn::create("+447764970336", "@".$requestID." TEST");
		$smsIn = SMSIn::findByID($smsID);
		
		// now finally the real test
		$smsIn->process();		
		
		// check the results; do we have an response saved!
		$request = Request::findByID($requestID);
		$this->assertNotNull($request);
		$requestResponses = $request->getResponses();
		
		$this->assertEquals(1, count($requestResponses));
		$this->assertEquals($requestResponses[0]->getResponse(), "TEST");
		
		// check the results; does SMS table have details saved!
		$db = getDB();
		$s = $db->prepare("SELECT sms_in.* FROM sms_in ");
		$s->execute();
		$d = $s->fetch();
		
		$this->assertEquals($userTelephone->getId(), $d['user_telephone_id']);
		$this->assertEquals($requestResponses[0]->getId(), $d['request_response_id']);
		
		$this->assertEquals(null, $d['request_id']);
	}
	


	function dataForTestNew() {
		return array(
				array("new hello","hello"),
				array("@new hello","hello"),
				array("newhello","hello"),
				array("@newhello","hello"),
			);
	}
	
	/**
     * @dataProvider dataForTestNew
     *  **/	
	function testNew($txt,$summary) {
		$this->setupDB();

		$user = UserAccount::findByTelephoneOrCreate(1, "7764970336");
		$userTelephone = UserTelephone::findByCountryIDandTelphone(1, "7764970336");
		$this->assertNotNull($userTelephone);
		
		$supportGroup = SupportGroup::create("Test");
		$supportGroup->addUser($user);
		$requestID = $supportGroup->newRequest($user, "oeeou", "eouoeu", array(), array($user->getId()));
		
		// now some fakery; pretend we sent texted this to user (yes, texting to yourself is odd)
		$db = getDB();
		$statTelephone = $db->prepare("INSERT INTO request_sent_to_user_telephone (request_id,user_telephone_id,sent_at) VALUES (:rid,:utid, :at) ");
		$statTelephone->execute(array('rid'=>$requestID,'utid'=>$userTelephone->getId(), 'at'=>date("Y-m-d H:i:s", getCurrentTime())));

		
		$smsID = SMSIn::create("+447764970336", $txt);
		$smsIn = SMSIn::findByID($smsID);
		
		// now finally the real test
		$smsIn->process();		
		
		// check the results; do we have an response saved on the old request!
		$request = Request::findByID($requestID);
		$this->assertNotNull($request);
		$requestResponses = $request->getResponses();
		$this->assertEquals(0, count($requestResponses));
		
		// a new request
		$request = Request::findByID($requestID+1);
		$this->assertNotNull($request);
		$this->assertEquals($summary,$request->getSummary());
		$requestResponses = $request->getResponses();
		$this->assertEquals(0, count($requestResponses));

		// check the results; does SMS table have details saved!
		$db = getDB();
		$s = $db->prepare("SELECT sms_in.* FROM sms_in ");
		$s->execute();
		$d = $s->fetch();
		
		$this->assertEquals($userTelephone->getId(), $d['user_telephone_id']);
		$this->assertEquals(null, $d['request_response_id']);
		$this->assertEquals($request->getId(), $d['request_id']);
		
		
	}

	function dataForTestGetSpecifiedRequestIDFromText() {
		return array(
				array("@12345 hello",true,12345,"hello"),
				array("@12345hello",true,12345,"hello"),
				array("@12345",false,null,null),
				array("@12345 ",false,null,null),						
				array("@1234 hello",true,1234,"hello"),
				array("@1234hello",true,1234,"hello"),
				array("@1234",false,null,null),
				array("@1234 ",false,null,null),			
				array("@123 hello",true,123,"hello"),
				array("@123hello",true,123,"hello"),
				array("@123",false,null,null),
				array("@123 ",false,null,null),			
				array("@12 hello",true,12,"hello"),
				array("@12hello",true,12,"hello"),
				array("@12",false,null,null),
				array("@12 ",false,null,null),
				array("@1 hello",true,1,"hello"),
				array("@1hello",true,1,"hello"),
				array("@1",false,null,null),
				array("@1 ",false,null,null),
				array("cat",false,null,null),
				array("  ",false,null,null),
				array(" @1 hello",false,null,null),
				array(" @cat hello",false,null,null),
				array("@cat hello",false,null,null),
				array("@dog hello",false,null,null),
				array("@",false,null,null),
			);
	}
	
	/**
     * @dataProvider dataForTestGetSpecifiedRequestIDFromText
     *  **/
	function testGetSpecifiedRequestIDFromText($body, $result, $id, $msg) {		
		$smsIn = new SMSIn(array('body'=>$body));
		$data = $smsIn->getSpecifiedRequestIDFromText();
		if ($result) {
			$this->assertEquals($id, $data[0]);
			$this->assertEquals($msg, $data[1]);
		} else {
			$this->assertNull($data);
		}
	}
} 
