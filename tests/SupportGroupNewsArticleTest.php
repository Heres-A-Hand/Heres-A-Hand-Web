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

class SupportGroupNewsArticleTest extends AbstractTest {

	function test1() {
		$group = SupportGroup::create('test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$group->addUser($user1,true,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$group->addUser($user2,true,true);

		$supportGroupNewsArticleID = $group->newNewsArticle($user1, "Headline", "Details");
		
		# test toFunction
		$supportGroupNewsArticle = SupportGroupNewsArticle::findByID($supportGroupNewsArticleID);		
		$toUsers = $supportGroupNewsArticle->getToUsers();
		$this->assertEquals(1, count($toUsers));
		$this->assertEquals($user2->getId(), $toUsers[0]->getId());
		
		# test notifications
		$notifications = $group->getNotificationsVisibleToUser($user1);
		$this->assertEquals(1, count($notifications));
		$this->assertEquals("SupportGroupNewsArticle", get_class($notifications[0]));
		$this->assertEquals($supportGroupNewsArticleID, $notifications[0]->getId());
		
		# test user who can and can't see news article
		$this->assertNotNull(SupportGroupNewsArticle::findByIDForUser($supportGroupNewsArticleID, $user1));
		$this->assertNotNull(SupportGroupNewsArticle::findByIDForUser($supportGroupNewsArticleID, $user2));
		$user3 = UserAccount::findByEmailOrCreate('test3@test.com');
		$this->assertNull(SupportGroupNewsArticle::findByIDForUser($supportGroupNewsArticleID, $user3));
		
	}
	
	function testGetUsersInvolved() {
	$group = SupportGroup::create('test');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$group->addUser($user1,true,true);
		
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');
		$group->addUser($user2,true,true);

		$supportGroupNewsArticleID = $group->newNewsArticle($user1, "Headline", "Details");
		
		# 1 - test getUsersInvolved() before a reply
		$supportGroupNewsArticle = SupportGroupNewsArticle::findByID($supportGroupNewsArticleID);		
		$involvedUsers = $supportGroupNewsArticle->getUsersInvolved();
		$this->assertEquals(1, count($involvedUsers));
		$this->assertEquals($user1->getId(), $involvedUsers[0]->getId());	
			
		# reply
		$supportGroupNewsArticle->newResponse("Test", $user2);
		
		# 2 - test getUsersInvolved() after a reply
		$supportGroupNewsArticle = SupportGroupNewsArticle::findByID($supportGroupNewsArticleID);		
		$involvedUsers = $supportGroupNewsArticle->getUsersInvolved();
		$this->assertEquals(2, count($involvedUsers));
		if ($involvedUsers[0]->getId() == $user2->getId()) { 
			$this->assertEquals($user1->getId(), $involvedUsers[1]->getId());			
		} else {
			$this->assertEquals($user2->getId(), $involvedUsers[1]->getId());						
		}
		
	}
	
}