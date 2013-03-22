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
require dirname(__FILE__).'/../src/global.php';

logInfo("Starting to Check Email");

$inbox = imap_open(MAIL_SERVER,MAIL_USERNAME,MAIL_PASSWORD);
if (!$inbox) {
	logWarning('Cannot connect to email: ' . imap_last_error());
	die('Cannot connect to email: ' . imap_last_error());
}

$emails = imap_search($inbox,'ALL',SE_UID);

if($emails) {
	foreach($emails as $email_number) {
		//print "#############################################################\n";

		logInfo("Fetching email ".$email_number);
		$overview = imap_fetch_overview($inbox,$email_number,FT_UID);
		$struct = imap_fetchstructure($inbox,$email_number,FT_UID);

		$message = null;
		if (property_exists($struct,'parts') && is_array($struct->parts)) {
			foreach($struct->parts as $id=>$part) {
				if ($part->subtype == 'PLAIN') {
					$message = imap_fetchbody($inbox, $email_number, ($id+1), FT_UID);
				}
				if ($part->subtype == 'ALTERNATIVE') {
					foreach($part->parts as $id2=>$part) {
						if ($part->subtype == 'PLAIN') {
							$message = imap_fetchbody($inbox, $email_number, ($id+1).".".($id2+1), FT_UID);
						}
					}
				}
			}
		} else {
			$message = imap_body($inbox,$email_number,FT_UID);
		}
		
		//var_dump($overview);
		//var_dump($struct);
		//var_dump($message);

		if ($message) {
			logInfo("Parsing email ".$email_number);
			$email = new EmailIn($overview[0]->from, $overview[0]->subject, $message);
			$email->parse();
			if ($email->getReply() && $email->getRequest() && $email->getUserAccount()) {
				logInfo("Creating new reply from email ".$email_number." for Request:".$email->getRequest()->getId());
				$email->getRequest()->newResponse($email->getReply(), $email->getUserAccount());
				imap_mail_move($inbox, $email_number, "Old", CP_UID);
			}
			if ($email->getReply() && $email->getSupportGroupNewsArticle() && $email->getUserAccount()) {
				logInfo("Creating new reply from email ".$email_number." for SupportGroupNewsArticle:".$email->getSupportGroupNewsArticle()->getId());
				$email->getSupportGroupNewsArticle()->newResponse($email->getReply(), $email->getUserAccount());
				imap_mail_move($inbox, $email_number, "Old", CP_UID);
			}
		}

	}
}


imap_close($inbox,CL_EXPUNGE);

logInfo("Finished Checking Email");

