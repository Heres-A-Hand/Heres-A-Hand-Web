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

class SupportGroupTest extends AbstractTest {

	function testDelete() {
		$db = $this->setupDB();
		
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$group = SupportGroup::create('test');
		$group->addUser($user);
		
		# 1 SupportGroup::findForUser()
		$this->assertEquals(1,  count(SupportGroup::findForUser($user)));
		
		#2 methods on Support Group
		$this->assertEquals(false, $group->isDeleted());
		$this->assertNull($group->getDeletedAt());
		
		######### NOW DELETE
		$group->delete();
		
		# 1 SupportGroup::findForUser()
		$this->assertEquals(0,  count(SupportGroup::findForUser($user)));
			
		#2 methods on Support Group
		$this->assertEquals(true, $group->isDeleted());
		// TODO check getDeletedAt() date is not null
		
	}

	function testIsUserSettingUp1() {
		$db = $this->setupDB();
		
		$group = SupportGroup::create('test');
		
		# first user is setting up
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$group->addUser($user1,true,true);
		
		$this->assertEquals(true,  $group->isUserSettingUp($user1));
		

		# second user isn't
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$group->addUser($user2,true,true);
		
		$this->assertEquals(false,  $group->isUserSettingUp($user2));
				
		
		
	}
				
}

