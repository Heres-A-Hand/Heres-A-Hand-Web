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

class UserOnHolidayTest extends AbstractTest {

	function test1() {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = mktime(17, 00, 00, 1, 1, 2012);
		
		$this->setupDB();

		$user = UserAccount::findByEmailOrCreate('test@test.com');

		$holidays = UserOnHoliday::findByUser($user);
		$this->assertEquals(0, count($holidays));

		
		$user->newHoliday(mktime(17, 00, 00, 11, 1, 2012), mktime(9, 00, 00, 11, 30, 2012));

		$holidays = UserOnHoliday::findByUser($user);
		$this->assertEquals(1, count($holidays));

		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 11, 20, 2012));
		$this->assertNotNull($holiday);

		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 10, 20, 2012));
		$this->assertNull($holiday);
		
	}
	
	function testShare1() {
		global $MOCK_CURRENT_TIME;
		$MOCK_CURRENT_TIME = mktime(17, 00, 00, 1, 1, 2012);
		
		$this->setupDB();

		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$group = SupportGroup::create('test');
		$group->addUser($user);
		
		# 1 test creating a holiday private
		$user->newHoliday(mktime(17, 00, 00, 11, 1, 2012), mktime(9, 00, 00, 11, 30, 2012),false);
		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 11, 20, 2012));
		$this->assertEquals(false, $holiday->getShareWithGroup());

		# 2 test creating a holiday public
		$user->newHoliday(mktime(17, 00, 00, 11, 1, 2013), mktime(9, 00, 00, 11, 30, 2013),true);
		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 11, 20, 2013));
		$this->assertEquals(true, $holiday->getShareWithGroup());
		
		#3 turn holiday private
		$holiday->edit(mktime(17, 00, 00, 11, 1, 2013), mktime(9, 00, 00, 11, 30, 2013),false);
		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 11, 20, 2013));
		$this->assertEquals(false, $holiday->getShareWithGroup());		
		
		#4 turn holiday public
		$holiday->edit(mktime(17, 00, 00, 11, 1, 2013), mktime(9, 00, 00, 11, 30, 2013),true);
		$holiday = UserOnHoliday::findByUserForDate($user, mktime(9, 00, 00, 11, 20, 2013));
		$this->assertEquals(true, $holiday->getShareWithGroup());		
		
		# add public holiday by a third user not in group - this shouldn't appear in next test.
		$user3 = UserAccount::findByEmailOrCreate('test3@test.com');
		$user3->newHoliday(mktime(17, 00, 00, 11, 15, 2013), mktime(9, 00, 00, 11, 19, 2013),true);		
		
		#5 test other user can see public holidays from the same group
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$group->addUser($user2);
		
		$holidays = $group->getHolidaysVisibleToUserOnCalendar($user2, mktime(17, 00, 00, 1, 1, 2013), mktime(9, 00, 00, 12, 30, 2013));
		$this->assertEquals(1,count($holidays));
		$this->assertEquals('test',$holidays[0]->getDisplayName());
		
		#6 test other user cant see private holiday
		$user2 = UserAccount::findByEmail('test2@test.com');
		$group->addUser($user2);
		
		$holidays = $group->getHolidaysVisibleToUserOnCalendar($user2, mktime(17, 00, 00, 1, 1, 2012), mktime(9, 00, 00, 12, 30, 2012));
		$this->assertEquals(0,count($holidays));

	}

}
