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

class RequestResponseTest extends AbstractTest {

	/** Test vars are set and loaded properly **/
	function test1() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user,true);

		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();


		$requestID = $supportGroup->newRequest($user, 'Summary', 'Message', array(), array());
		$request = Request::findByID($requestID);

		$this->assertEquals('Message',$request->getRequest());
		$this->assertEquals('Summary',$request->getSummary());
		$this->assertEquals($user->getId(), $request->getCreatedByUserId());
		$this->assertEquals('test', $request->getCreatedByDisplayName());
		

		$responseID = $request->newResponse('I Will',$user);
		$response = RequestResponse::findById($responseID);

		$this->assertEquals('I Will',$response->getResponse());
		$this->assertEquals($user->getId(), $response->getUserAccountID());
		$this->assertEquals('test', $response->getDisplayName());

	}

	/** normal data **/
	function testNotifyData1() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test1@test.com');
		$user1email = UserEmail::findByEmail('test1@test.com');		
		$user1->addTelephone("1", 1, "1111");
		$user1telephone = UserTelephone::findByCountryIDandTelphone(1, "1111");
		$supportGroup->addUser($user1,true);
		$user1email->markConfirmed();
		$user1telephone->markConfirmed();
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$user2email = UserEmail::findByEmail('test2@test.com');		
		$user2->addTelephone("1", 1, "2222");
		$user2telephone = UserTelephone::findByCountryIDandTelphone(1, "2222");
		$supportGroup->addUser($user2,true);
		$user2email->markConfirmed();
		$user2telephone->markConfirmed();
		
		
		#1 Request; test notify data
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array());
		$request = RequestTestObject::findByID($requestID);
		
		$notifyData = $request->callGetNotifyData();
		$this->assertEquals(false, isset($notifyData[$user1->getId()]));
		$this->assertEquals(true, isset($notifyData[$user2->getId()]));
		
		
		#2 Reply; test notify data
		
		$responseID = $request->newResponse('I Will',$user2);
		$response = RequestResponseTestObject::findById($responseID);
		
		$notifyData = $response->callGetNotifyData();
		$this->assertEquals(true, isset($notifyData[$user1->getId()]));
		$this->assertEquals(true, $notifyData[$user1->getId()]['sendToTelephones'][$user1telephone->getId()]);
		$this->assertEquals(true, $notifyData[$user1->getId()]['sendToEmails'][$user1email->getId()]);

		$this->assertEquals(false, isset($notifyData[$user2->getId()]));
		
	}
	
	/** user responds to own request; don't tell them! **/
	function testNotifyData2() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test1@test.com');
		$user1email = UserEmail::findByEmail('test1@test.com');		
		$supportGroup->addUser($user1,true);
		$user1email->markConfirmed();
		
		
		#1 Request; test notify data
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array());
		$request = RequestTestObject::findByID($requestID);
		
		$notifyData = $request->callGetNotifyData();
		$this->assertEquals(false, isset($notifyData[$user1->getId()]));
		
		
		#2 Reply to own requst!; test notify data
		
		$responseID = $request->newResponse('I Will',$user1);
		$response = RequestResponseTestObject::findById($responseID);
		
		$notifyData = $response->callGetNotifyData();
		$this->assertEquals(false, isset($notifyData[$user1->getId()]));
		
	}

	/** Leah saw a bug in Request#70 when she was sent a text but no email. This replicates that bug. **/
	function testRequest70Bug() {
		global $MOCK_CURRENT_TIME ;
		$MOCK_CURRENT_TIME = mktime(9,00,00,1,1,2012);
		
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$types = $supportGroup->getRequestTypes();
		
		$user1 = UserAccount::findByEmailOrCreate('test1@test.com');
		$supportGroup->addUser($user1,true);
		
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$user2email = UserEmail::findByEmail('test2@test.com');		
		$user2->addTelephone("1", 1, "2222");
		$user2telephone = UserTelephone::findByCountryIDandTelphone(1, "2222");
		$supportGroup->addUser($user2,true);
		$user2email->markConfirmed();
		$user2telephone->markConfirmed();
		
		
		$user2->newScheduleRule(array($user2email->getId()), array(), array(), 0, 7, array("mon","tue","wed","thu","fri"), array($types[0]->getId()));
		
		
		
		#1 Request; test notify data
		$MOCK_CURRENT_TIME = mktime(7,55,00,27,6,2012);
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array());
		$request = RequestTestObject::findByID($requestID);
		
		$notifyData = $request->callGetNotifyData();
		$this->assertEquals(false, isset($notifyData[$user1->getId()]));
		$this->assertEquals(true, isset($notifyData[$user2->getId()]));
		$this->assertEquals(true, $notifyData[$user2->getId()]['sendToTelephones'][$user2telephone->getId()]);
		$this->assertEquals(true, $notifyData[$user2->getId()]['sendToEmails'][$user2email->getId()]);		
		

		
	}	
}



