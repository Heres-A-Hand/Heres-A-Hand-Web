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

class WhiteLabelTest extends AbstractTest {

	function testGroupInWhiteLabel() {
		$db = $this->setupDB();
		
		$group = SupportGroup::create('test');
		
		$whiteLabel1 = WhiteLabel::create('test1');
		$whiteLabel2 = WhiteLabel::create('test2');
		
		# in no whitelabel
		$this->assertEquals(null, $group->getWhiteLabel());
		$this->assertEquals(null, $group->getWhiteLabelId());
		
		$sg1s = new SupportGroupSearch();
		$sg1s->inWhiteLabel($whiteLabel1);
		$this->assertEquals(0, $sg1s->num());
		
		$sg2s = new SupportGroupSearch();
		$sg2s->inWhiteLabel($whiteLabel2);
		$this->assertEquals(0, $sg1s->num());
		
		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel1);
		$this->assertNull($sg);
		
		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel2);
		$this->assertNull($sg);
		
		# in whitelabel1
		$group->setWhiteLabel($whiteLabel1);
		
		$this->assertEquals($whiteLabel1->getId(), $group->getWhiteLabel()->getID());
		$this->assertEquals($whiteLabel1->getId(), $group->getWhiteLabelId());
		
		$sg1s = new SupportGroupSearch();
		$sg1s->inWhiteLabel($whiteLabel1);
		$this->assertEquals(1, $sg1s->num());
		
		$sg2s = new SupportGroupSearch();
		$sg2s->inWhiteLabel($whiteLabel2);
		$this->assertEquals(0, $sg2s->num());

		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel1);
		$this->assertEquals($group->getId(), $sg->getId());
		
		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel2);
		$this->assertNull($sg);		
		
		# in whitelabel2
		$group->setWhiteLabel($whiteLabel2);
		
		$this->assertEquals($whiteLabel2->getId(), $group->getWhiteLabel()->getID());
		$this->assertEquals($whiteLabel2->getId(), $group->getWhiteLabelId());
		
		$sg1s = new SupportGroupSearch();
		$sg1s->inWhiteLabel($whiteLabel1);
		$this->assertEquals(0, $sg1s->num());
		
		$sg2s = new SupportGroupSearch();
		$sg2s->inWhiteLabel($whiteLabel2);
		$this->assertEquals(1, $sg2s->num());
				
		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel1);
		$this->assertNull($sg);
		
		$sg = SupportGroup::findByIDForWhiteLabel($group->getId(), $whiteLabel2);
		$this->assertEquals($group->getId(), $sg->getId());
				
	}

	function testAdminWhiteLabelSysAdmin() {
		$db = $this->setupDB();
		
		$whiteLabel1 = WhiteLabel::create('test1');
		$whiteLabel2 = WhiteLabel::create('test2');
		
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		$db->exec("UPDATE user_account SET system_admin='t'");
		$user1 = UserAccount::findByEmail('test@test.com');
		
		# searching for all white labels ...
		$wlS1 = new WhiteLabelSearch();
		$this->assertEquals(2, $wlS1->num());
				
		# ... should be the same as searching for all white labels this user can access.
		$wlS2 = new WhiteLabelSearch();
		$wlS2->userCanAdmin($user1);
		$this->assertEquals(2, $wlS2->num());
		
	}
	

	function testAdminWhiteLabelNonSysAdmin() {
		$db = $this->setupDB();
		
		$whiteLabel1 = WhiteLabel::create('test1');
		$whiteLabel2 = WhiteLabel::create('test2');
		$user1 = UserAccount::findByEmailOrCreate('test@test.com');
		
		# searching for all white labels
		$wlS1 = new WhiteLabelSearch();
		$this->assertEquals(2, $wlS1->num());
				
		# our user has none
		$whiteLabelSearch = new WhiteLabelSearch();
		$whiteLabelSearch->userCanAdmin($user1);
		$this->assertEquals(0, $whiteLabelSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel1);
		$this->assertEquals(0, $userSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel2);
		$this->assertEquals(0, $userSearch->num());
		
		# now they have 1
		$whiteLabel1->addAdmin($user1);
		
		$whiteLabelSearch = new WhiteLabelSearch();
		$whiteLabelSearch->userCanAdmin($user1);
		$this->assertEquals(1, $whiteLabelSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel1);
		$this->assertEquals(1, $userSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel2);
		$this->assertEquals(0, $userSearch->num());		
		
		# now they have 2
		$whiteLabel2->addAdmin($user1);
		
		$whiteLabelSearch = new WhiteLabelSearch();
		$whiteLabelSearch->userCanAdmin($user1);
		$this->assertEquals(2, $whiteLabelSearch->num());
				
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel1);
		$this->assertEquals(1, $userSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel2);
		$this->assertEquals(1, $userSearch->num());		
		
		# back down to 1
		$whiteLabel1->removeAdmin($user1);
		
		$whiteLabelSearch = new WhiteLabelSearch();
		$whiteLabelSearch->userCanAdmin($user1);
		$this->assertEquals(1, $whiteLabelSearch->num());		
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel1);
		$this->assertEquals(0, $userSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel2);
		$this->assertEquals(1, $userSearch->num());		
		
		# back to zero
		$whiteLabel2->removeAdmin($user1);
		$whiteLabelSearch = new WhiteLabelSearch();
		$whiteLabelSearch->userCanAdmin($user1);
		
		$this->assertEquals(0, $whiteLabelSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel1);
		$this->assertEquals(0, $userSearch->num());
		
		$userSearch = new UserAccountSearch();
		$userSearch->canAdminWhiteLabel($whiteLabel2);
		$this->assertEquals(0, $userSearch->num());		
		
	}

	function testUserInWhiteLabel() {
		$db = $this->setupDB();
		
		$group = SupportGroup::create('test');
		
		$user = UserAccount::findByEmailOrCreate('test@test.com');
		$group->addUser($user, true);

		$whiteLabel = WhiteLabel::create('test1');
		
		# user not in whitelabel
		$userSearch = new UserAccountSearch();
		$userSearch->inWhiteLabel($whiteLabel);
		$this->assertEquals(0, $userSearch->num());
		
		$u = UserAccount::findByIDinWhiteLabel($user->getId(), $whiteLabel);
		$this->assertNull($u);
		
		# user in whitelabel
		$group->setWhiteLabel($whiteLabel);
		
		$userSearch = new UserAccountSearch();
		$userSearch->inWhiteLabel($whiteLabel);
		$this->assertEquals(1, $userSearch->num());

		$u = UserAccount::findByIDinWhiteLabel($user->getId(), $whiteLabel);
		$this->assertEquals($user->getId(), $u->getId());
		
	}
}


