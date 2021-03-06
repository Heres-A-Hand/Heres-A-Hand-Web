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
**/

require '../src/global.php';
checkUserSession();


$s = getSmarty();
$s->assign('PAGETITLE','Contact');
$s->assign('email','');

if ($CURRENT_USER) {
	$emails = $CURRENT_USER->getEmails();
	if (count($emails) > 0) $s->assign('email',$emails[0]->getEmail());
}

if ($_POST && isset($_POST['email'])  && isset($_POST['message']) ) {
	// we could check the CSFR token here but seeing as anyone can send this form in we don't care right now
	$tpl = getEmailSmarty();
	$tpl->assign('email',$_POST['email']);
	$tpl->assign('message',$_POST['message']);
	$tpl->assign('user',$CURRENT_USER);
	$tpl->assign('browser',$_SERVER['HTTP_USER_AGENT']);
	$tpl->assign('ip',$_SERVER['REMOTE_ADDR']);
	$body = $tpl->fetch('contactform.email.txt');
	//print $body;

	list($mailer, $message) = getSwiftMailer();
	$message
		->setSubject('Contact Form')
		->setTo(array('james@heresahand.org.uk', 'catherine@heresahand.org.uk'))
		->setBody($body);
	$mailer->send($message);

	$s->assign('flashOK','Your message has been sent');	
}


$s->display('contact.htm');