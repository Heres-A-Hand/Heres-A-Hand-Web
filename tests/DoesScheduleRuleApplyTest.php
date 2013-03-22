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


class DoesScheduleRuleApplyTest extends AbstractTest {


	function testApplies() {
		global $MOCK_CURRENT_TIME;
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$ruleID = $user->newScheduleRule(array(1), array(), array(),9, 17, array('mon'), array($types[0]->getId()));
		$rule = ScheduleRule::findByIDForUserAccount($ruleID, $user);

		$MOCK_CURRENT_TIME = mktime(12,00,00,11,7,2011); // Mon
		$this->assertEquals(true, doesScheduleRuleApply($rule, array($types[0])));

	}

	function testDayWrong() {
		global $MOCK_CURRENT_TIME;
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$ruleID = $user->newScheduleRule(array(1), array(), array(),9, 17, array('mon'), array($types[0]->getId()));
		$rule = ScheduleRule::findByIDForUserAccount($ruleID, $user);

		$MOCK_CURRENT_TIME = mktime(12,00,00,11,8,2011); // Tue
		$this->assertEquals(false, doesScheduleRuleApply($rule, array($types[0])));

	}

	function testNormalTimeWrong() {
		global $MOCK_CURRENT_TIME;
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$ruleID = $user->newScheduleRule(array(1), array(), array(),9, 17, array('mon'), array($types[0]->getId()));
		$rule = ScheduleRule::findByIDForUserAccount($ruleID, $user);

		$MOCK_CURRENT_TIME = mktime(22,00,00,11,7,2011); // Mon 10PM
		$this->assertEquals(false, doesScheduleRuleApply($rule, array($types[0])));

	}

	function testLoopingTimeWrong() {
		global $MOCK_CURRENT_TIME;
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$ruleID = $user->newScheduleRule(array(1), array(), array(),21, 8, array('mon'), array($types[0]->getId()));
		$rule = ScheduleRule::findByIDForUserAccount($ruleID, $user);

		$MOCK_CURRENT_TIME = mktime(12,00,00,11,7,2011); // Mon
		$this->assertEquals(false, doesScheduleRuleApply($rule, array($types[0])));

	}

	function testTypeWrong() {
		global $MOCK_CURRENT_TIME;
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$supportGroup->addUser($user,true);
		$users = $supportGroup->getMembers();
		$user = $users[0];
		$types = $supportGroup->getActiveRequestTypes();

		$ruleID = $user->newScheduleRule(array(1), array(),array(), 9, 17, array('mon'), array($types[1]->getId()));
		$rule = ScheduleRule::findByIDForUserAccount($ruleID, $user);

		$MOCK_CURRENT_TIME = mktime(12,00,00,11,7,2011); // Mon
		$this->assertEquals(false, doesScheduleRuleApply($rule, array($types[0])));

	}



}


