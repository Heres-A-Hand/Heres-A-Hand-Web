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

class UserAccountTests extends AbstractTest {

	function testIsAccountCreated() {
		$this->setupDB();

		UserAccount::findByEmailOrCreate('test@test.com');
		$user = UserAccount::findByEmail('test@test.com');
		$this->assertEquals(false,$user->isAccountCreated());

		$user = UserAccount::findByEmail('test@test.com');
		$user->createAccount('Bobby', 'Cats');

		$user = UserAccount::findByEmail('test@test.com');
		$this->assertEquals(true,$user->isAccountCreated());
	}


	function testHasAnyPremiumGroups() {
		$this->setupDB();

		UserAccount::findByEmailOrCreate('test@test.com');
		$user = UserAccount::findByEmail('test@test.com');
		$group = SupportGroup::create('title');
		$group->addUser($user);
		
		$this->assertEquals(false,$user->hasAnyPremiumGroups());
		
		$group->makePremium();
		
		$this->assertEquals(true,$user->hasAnyPremiumGroups());
		
	}

	function testAddToGroupsTest1() {
		$this->setupDB();

		UserAccount::findByEmailOrCreate('test@test.com');
		$user = UserAccount::findByEmail('test@test.com');
		$group = SupportGroup::create('title');
		
		# 1 - add no permissions
		$this->assertEquals(true,$group->addUser($user,false,false));
		
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(false, $groups[0]->isAdmin());
		$this->assertEquals(false, $groups[0]->canMakeRequests());
		
		# 2 - add with create
		$this->assertEquals(false,$group->addUser($user,false,true));
		
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(false, $groups[0]->isAdmin());
		$this->assertEquals(true, $groups[0]->canMakeRequests());
		
		# 3 - add with admin
		$this->assertEquals(false,$group->addUser($user,true,true));
		
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(true, $groups[0]->isAdmin());
		$this->assertEquals(true, $groups[0]->canMakeRequests());
	}

	function testAddToGroupsTest2() {
		$this->setupDB();

		UserAccount::findByEmailOrCreate('test@test.com');
		$user = UserAccount::findByEmail('test@test.com');
		$group = SupportGroup::create('title');
		
		# 1 - add no permissions
		$this->assertEquals(true,$group->addUser($user,false,false));
		
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(false, $groups[0]->isAdmin());
		$this->assertEquals(false, $groups[0]->canMakeRequests());
		
		# 2 - add with admin
		$this->assertEquals(false,$group->addUser($user,true,true));
		
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(true, $groups[0]->isAdmin());
		$this->assertEquals(true, $groups[0]->canMakeRequests());
	}

	function testAddToGroupsTest3() {
		$this->setupDB();

		UserAccount::findByEmailOrCreate('test@test.com');
		$user = UserAccount::findByEmail('test@test.com');
		$group = SupportGroup::create('title');
		
		#  add with admin
		$this->assertEquals(true,$group->addUser($user,true,true));
		
		# can't revoke make requests from a manager, you can try, nothing happens.
		$group->revokeMakeRequests($user);
		
		# See! User still has all permissions
		$groups = SupportGroup::findForUser($user);
		$this->assertEquals(true, $groups[0]->isAdmin());
		$this->assertEquals(true, $groups[0]->canMakeRequests());	
	}

	function testTelephoneConfirmCode() {
		$this->setupDB();

		$user = UserAccount::findByTelephoneOrCreate(1, '7712345678');
		
		# 0 before account created
		$this->assertEquals(false,$user->isAccountCreated());

		$testTele1 = TestUserTelephone::findByCountryIDandTelphone(1, '7712345678');
		$this->assertEquals("You have b",substr($testTele1->testGetConfirmCodeMessage(),0,10));
		
		# create account
		$user->createAccount('Bobby', 'Cats');
		$this->assertEquals(true,$user->isAccountCreated());
		
		# 1 after account created
		$tele2 = $user->addTelephone('test', 1, '77456');

		$testTele2 = TestUserTelephone::findByCountryIDandTelphone(1, '77456');
		$this->assertEquals("To confirm",substr($testTele2->testGetConfirmCodeMessage(),0,10));
		
	}

	function testDeleteAccount() {
		$this->setupDB();

		# user 1 has an account, create it
		$user1 = UserAccount::findByEmailOrCreate('test1@test.com');
		$user1->createAccount("Bobby", "1234");
		
		# user 2 has an account
		$user2 = UserAccount::findByEmailOrCreate('test2@test.com');

		# user 2 is deleted
		$user2->delete($user2);
		
		$user2 = UserAccount::findByEmail('test2@test.com');
		$this->assertNull($user2);
		
		# user 1 still exists
		$user1 = UserAccount::findByEmail('test1@test.com');
		$this->assertNotNull($user1);
		
		# user 1 can't be deleted
		$this->setExpectedException("UserAccountAlreadyCreatedException");
		$user1->delete($user1);
	}

}

class TestUserTelephone extends UserTelephone {
	
	/** @return TestUserTelephone **/
	public static function findByCountryIDandTelphone($countryID, $number) {
		if (substr($number,0,1) == "0") $number = substr($number, 1);
		$number = trim(str_replace(' ', '', $number));
		$db = getDB();
		$s = $db->prepare("SELECT user_telephone.*, country.international_dailing_code FROM user_telephone ".
				"JOIN country ON country.id = user_telephone.country_id ".
				"WHERE user_telephone.call_number = :n AND user_telephone.country_id = :cid AND user_telephone.deleted_at IS NULL");
		$s->execute(array('n'=>$number,'cid'=>$countryID));
		if ($s->rowCount() == 1) {
			return new TestUserTelephone($s->fetch());
		}
	}

	public function testGetConfirmCodeMessage() { return $this->getConfirmCodeMessage(); }	
	
}