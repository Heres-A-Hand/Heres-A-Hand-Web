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

class UserEmailTest extends AbstractTest {

	function testCantAddTwicetoDifferentAccounts() {
		$this->setupDB();
		
		$user1 = UserAccount::findByEmailOrCreate("test1@test.com");
		$user2 = UserAccount::findByEmailOrCreate("test2@test.com");
		
		$user1->addEmail("Test", "ohno@test.com");
		$this->setExpectedException("UserEmailAlreadyExistsException");
		$user2->addEmail("Test", "ohno@test.com");
		
	}
	
	function testCantAddTwicetoSameAccount() {
		$this->setupDB();
		$this->setupDB();
		
		$user1 = UserAccount::findByEmailOrCreate("test1@test.com");
		
		$user1->addEmail("Test", "ohno@test.com");
		$this->setExpectedException("UserEmailAlreadyExistsException");
		$user1->addEmail("Test", "ohno@test.com");
	}	
	
}

