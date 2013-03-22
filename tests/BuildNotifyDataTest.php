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


class BuildNotifyDataTest extends AbstractTest {

	function testAdvanced1() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$user->updateUseAdvancedSchedule(true);

		$emails = $user->getEmails();
		$emails[0]->markConfirmed();

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$data = buildNotifyData($user, $types);
		
		$this->assertEquals(true, is_array($data));
		
		//var_dump($data);
		
		$this->assertEquals($user->getId(), $data['member']->getId());
		$this->assertEquals(1, count($data['emails']));
		$this->assertEquals(1, count($data['sendToEmails']));
		$this->assertEquals(0, count($data['telephones']));
		$this->assertEquals(0, count($data['sendToTelephones']));
		$this->assertEquals(0, count($data['twitters']));
		$this->assertEquals(0, count($data['sendToTwitters']));

	}

	
	function testSimple1() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$user->updateUseAdvancedSchedule(false);

		$emails = $user->getEmails();
		$emails[0]->markConfirmed();

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$data = buildNotifyData($user, $types);
		
		$this->assertEquals(true, is_array($data));

		$this->assertEquals($user->getId(), $data['member']->getId());
		$this->assertEquals(1, count($data['emails']));
		$this->assertEquals(1, count($data['sendToEmails']));
		$this->assertEquals(true, $data['sendToEmails'][$emails[0]->getId()]);
		$this->assertEquals(0, count($data['telephones']));
		$this->assertEquals(0, count($data['sendToTelephones']));
		$this->assertEquals(0, count($data['twitters']));
		$this->assertEquals(0, count($data['sendToTwitters']));

	}	
	
	/** Wrong type **/
	function testSimple2() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$user->updateUseAdvancedSchedule(false);

		$emails = $user->getEmails();
		$emails[0]->markConfirmed();

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();
		$types[0]->setSimpleScheduleRuleForUser($user, false);

		$data = buildNotifyData($user, array($types[0]));
		
		$this->assertEquals(true, is_array($data));
		
		//var_dump($data);
		
		$this->assertEquals($user->getId(), $data['member']->getId());
		$this->assertEquals(0, count($data['emails']));
		$this->assertEquals(0, count($data['sendToEmails']));
		$this->assertEquals(0, count($data['telephones']));
		$this->assertEquals(0, count($data['sendToTelephones']));
		$this->assertEquals(0, count($data['twitters']));
		$this->assertEquals(0, count($data['sendToTwitters']));

	}	
	
	
	function dataForTestSimple3() {
		return array(
				array(7,0,0),
				array(8,0,0),
				array(8,59,0),
				array(17,1,0),
				array(18,0,0),
			);
	}
	
	/**
	 * Wrong time	
     * @dataProvider dataForTestSimple3
     *  **/
	function testSimple3($h, $m, $s) {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = mktime($h, $m, $s, 1, 1, 2012);
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$user->updateUseAdvancedSchedule(false);

		$emails = $user->getEmails();
		$emails[0]->markConfirmed();
		$emails[0]->updateSimpleSchedule(array('mon','tue','wed','thu','fri','sat','sun'),9,0,17,0);
		
		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$data = buildNotifyData($user, array($types[0]));
		
		$this->assertEquals(true, is_array($data));
		
		//var_dump($data);
		//die();
		
		$this->assertEquals($user->getId(), $data['member']->getId());
		$this->assertEquals(1, count($data['emails']));
		$this->assertEquals(1, count($data['sendToEmails']));
		$this->assertEquals(false, $data['sendToEmails'][$emails[0]->getId()]);
		$this->assertEquals(0, count($data['telephones']));
		$this->assertEquals(0, count($data['sendToTelephones']));
		$this->assertEquals(0, count($data['twitters']));
		$this->assertEquals(0, count($data['sendToTwitters']));

	}	
	
	/**
	 * Wrong day
     *  **/
	function testSimple4() {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = mktime(13, 0, 0, 11, 11, 2012); // a sunday
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$user->updateUseAdvancedSchedule(false);

		$emails = $user->getEmails();
		$emails[0]->markConfirmed();
		$emails[0]->updateSimpleSchedule(array('mon','tue','wed','thu','fri','sat'),9,0,17,0);
		
		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$data = buildNotifyData($user, array($types[0]));
		
		$this->assertEquals(true, is_array($data));
		
		$this->assertEquals($user->getId(), $data['member']->getId());
		$this->assertEquals(1, count($data['emails']));
		$this->assertEquals(1, count($data['sendToEmails']));
		$this->assertEquals(false, $data['sendToEmails'][$emails[0]->getId()]);
		$this->assertEquals(0, count($data['telephones']));
		$this->assertEquals(0, count($data['sendToTelephones']));
		$this->assertEquals(0, count($data['twitters']));
		$this->assertEquals(0, count($data['sendToTwitters']));

	}	
}

