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

class EmailInProcessTest  extends AbstractTest {
	
	function testLoadRequestIDFromEmailURL() {

		$email = new EmailInTestObject(
			"x y <xy@hotmail.com>", 
			"RE: New Request Hi everyone. Please reply if you get this message. love",
			"I am trying to reply directly via my email again, but just to let you know, this didn't go through as a text to my phone.



> To: xy@hotmail.com
> Subject: New Request [#37] Hi everyone. Please reply if you get this message. love 
> From: app@heresahand.org.uk
> Date: Tue, 31 Jan 2012 21:16:33 +0000
> 
> Hello xy,
> 
> There is a new Request.
> 
> Group:
> Norman's group
> 
> Request:
> Hi everyone. Please reply if you get this message. love 
> 
> 
> To reply, reply to this email directly or visit:
> https://heresahand.org.uk/request.php?id=37
> ");


		$email->testParse();
		
		$this->assertEquals(37,$email->getRequestID() );
		$this->assertEquals(null,$email->getSupportGroupNewsArticleID() );
		$this->assertEquals("xy@hotmail.com",$email->getEmailAdrressOnly() );
		
	}
	
	function testLoadRequestIDFromSubjectOldWay() {

		$email = new EmailInTestObject(
			"x y <xy@hotmail.com>", 
			"RE: New Request [#37] Hi everyone. Please reply if you get this message. love",
			"I am trying to reply directly via my email again, but just to let you know, this didn't go through as a text to my phone.

 ");


		$email->testParse();
		
		$this->assertEquals(37,$email->getRequestID() );
		$this->assertEquals(null,$email->getSupportGroupNewsArticleID() );
		$this->assertEquals("xy@hotmail.com",$email->getEmailAdrressOnly() );
		
	}	

	function testLoadRequestIDFromSubjectNewWay() {

		$email = new EmailInTestObject(
			"x y <xy@hotmail.com>", 
			"RE: New Request [REQ#37] Hi everyone. Please reply if you get this message. love",
			"I am trying to reply directly via my email again, but just to let you know, this didn't go through as a text to my phone.

 ");


		$email->testParse();
		
		$this->assertEquals(37,$email->getRequestID() );
		$this->assertEquals(null,$email->getSupportGroupNewsArticleID() );
		$this->assertEquals("xy@hotmail.com",$email->getEmailAdrressOnly() );
		
	}	

	function testLoadSupportGroupNewsArticleIDFromEmailURL() {

		$email = new EmailInTestObject(
			"x y <xy@hotmail.com>", 
			"RE: News some great news",
			"that would be nice.



> To: xy@hotmail.com
> Subject: RE: News [NEWS#37] some great news
> From: app@heresahand.org.uk
> Date: Tue, 31 Jan 2012 21:16:33 +0000
> 
> Hello xy,
> 
> There is a new news.
> 
> Group:
> Bob's group
> 
> Request:
> Hi everyone. Please reply if you get this message. love 
> 
> 
> To reply, reply to this email directly or visit:
> https://heresahand.org.uk/supportGroupNewsArticle.php?id=37
> ");


		$email->testParse();
		
		$this->assertEquals(37,$email->getSupportGroupNewsArticleID() );
		$this->assertEquals(null,$email->getRequestID() );
		$this->assertEquals("xy@hotmail.com",$email->getEmailAdrressOnly() );
		
	}
	function testLoadSupportGroupNewsArticleIDFromSubject() {

		$email = new EmailInTestObject(
			"x y <xy@hotmail.com>", 
			"RE: News [NEWS#37] some great news",
			"that would be nice.
 ");


		$email->testParse();
		
		$this->assertEquals(37,$email->getSupportGroupNewsArticleID() );
		$this->assertEquals(null,$email->getRequestID() );
		$this->assertEquals("xy@hotmail.com",$email->getEmailAdrressOnly() );
		
	}	
					
} 


