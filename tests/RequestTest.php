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

class RequestTest extends AbstractTest {

	function testOtherUserCanSeeAsForAllUsers() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user3,true);	
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), null);

		# 1 
		$request = Request::findByIDForUser($requestID, $user2);
		$this->assertNotNull($request);
		
		# 2 
		$d = $supportGroup->getNotificationsVisibleToUser($user2);
		$this->assertEquals(1, count($d));
		
		# 3
		$d = $supportGroup->getRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		
		
		# 4
		$d = $supportGroup->getOpenRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		

		# 5
		$d = $supportGroup->getHomePageRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		
		
		# 6
		$d = $supportGroup->getOpenRequestsVisibleToUserCount($user2);
		$this->assertEquals(1, $d);		

		# 7
		$start = mktime(12,0,0,1,1,2010);
		$end = mktime(12,0,0,1,1,2040);
		$d = $supportGroup->getRequestsVisibleToUserOnCalendar($user2, $start, $end);
		$this->assertEquals(1, count($d));				
		
	}

	function testOtherUserCanSeeAsForThem() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user3,true);	
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), array($user2->getId()));

		# 1 
		$request = Request::findByIDForUser($requestID, $user2);
		$this->assertNotNull($request);
		
		# 2 
		$d = $supportGroup->getNotificationsVisibleToUser($user2);
		$this->assertEquals(1, count($d));
		
		# 3
		$d = $supportGroup->getRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		
		
		# 4
		$d = $supportGroup->getOpenRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		

		# 5
		$d = $supportGroup->getHomePageRequestsVisibleToUser($user2);
		$this->assertEquals(1, count($d));		
		
		# 6
		$d = $supportGroup->getOpenRequestsVisibleToUserCount($user2);
		$this->assertEquals(1, $d);		

		# 7
		$start = mktime(12,0,0,1,1,2010);
		$end = mktime(12,0,0,1,1,2040);
		$d = $supportGroup->getRequestsVisibleToUserOnCalendar($user2, $start, $end);
		$this->assertEquals(1, count($d));				
		
	}

	function testUserCanSeeRequestTheySent() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user3,true);			
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), array($user2->getId()));

		# 1 
		$request = Request::findByIDForUser($requestID, $user1);
		$this->assertNotNull($request);
		
		# 2 
		$d = $supportGroup->getNotificationsVisibleToUser($user1);
		$this->assertEquals(1, count($d));
		
		# 3
		$d = $supportGroup->getRequestsVisibleToUser($user1);
		$this->assertEquals(1, count($d));		
		
		# 4
		$d = $supportGroup->getOpenRequestsVisibleToUser($user1);
		$this->assertEquals(1, count($d));		

		# 5
		$d = $supportGroup->getHomePageRequestsVisibleToUser($user1);
		$this->assertEquals(1, count($d));		
		
		# 6
		$d = $supportGroup->getOpenRequestsVisibleToUserCount($user1);
		$this->assertEquals(1, $d);		

		# 7
		$start = mktime(12,0,0,1,1,2010);
		$end = mktime(12,0,0,1,1,2040);
		$d = $supportGroup->getRequestsVisibleToUserOnCalendar($user1, $start, $end);
		$this->assertEquals(1, count($d));				
		
	}

	function testUser3CantSee() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test3@test.com');
		$supportGroup->addUser($user3,true);
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), array($user2->getId()));

		# 1 
		$request = Request::findByIDForUser($requestID, $user3);
		$this->assertNull($request);
		
		# 2 
		$d = $supportGroup->getNotificationsVisibleToUser($user3);
		$this->assertEquals(0, count($d));
		
		# 3
		$d = $supportGroup->getRequestsVisibleToUser($user3);
		$this->assertEquals(0, count($d));		
		
		# 4
		$d = $supportGroup->getOpenRequestsVisibleToUser($user3);
		$this->assertEquals(0, count($d));		

		# 5
		$d = $supportGroup->getHomePageRequestsVisibleToUser($user3);
		$this->assertEquals(0, count($d));		
		
		# 6
		$d = $supportGroup->getOpenRequestsVisibleToUserCount($user3);
		$this->assertEquals(0, $d);		

		# 7
		$start = mktime(12,0,0,1,1,2010);
		$end = mktime(12,0,0,1,1,2040);
		$d = $supportGroup->getRequestsVisibleToUserOnCalendar($user3, $start, $end);
		$this->assertEquals(0, count($d));				
		
	}

	function testToUsersFunctionWhenToLimitedUsers() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test3@test.com');
		$supportGroup->addUser($user3,true);
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), array($user2->getId()));

		$request = Request::findByID($requestID);
		$usersTo = $request->getToUsers();
		
		$this->assertEquals(1, count($usersTo));
	}
	
	function testToUsersFunctionWhenAllUsers() {
		$this->setupDB();

		$supportGroup = SupportGroup::create('Test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$supportGroup->addUser($user1,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$supportGroup->addUser($user2,true);
		
		$user3 = UserAccount::findByEmailOrCreate('test3@test.com');
		$supportGroup->addUser($user3,true);
		
		$requestID = $supportGroup->newRequest($user1, 'Summary', 'Message', array(), null);

		$request = Request::findByID($requestID);
		$usersTo = $request->getToUsers();
		
		$this->assertEquals(2, count($usersTo));
	}
	
	
}



