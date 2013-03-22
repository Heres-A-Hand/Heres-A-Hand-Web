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

$pheanstalk = getPheanstalk();

define('WORKER_RUNS_FOR_SECONDS',60*60);
define('WORKER_CHECKS_FOR_EXIT_EVERY_SECONDS',15*60);
$startAt = time();

while(time() < ($startAt+WORKER_RUNS_FOR_SECONDS)) {
	$job = $pheanstalk->watchOnly(BEANSTALKD_QUE)->reserve(WORKER_CHECKS_FOR_EXIT_EVERY_SECONDS);
	if ($job) {
		logDebug("Pulled Job from Que:".$job->getData());
	
		try {
			$data = json_decode($job->getData());
		
			if ($data->type == 'NewUserEmail') {
				logInfo("Pulled from Que, new UserEmail:".$data->userEmailID);
				$email = UserEmail::findByID($data->userEmailID);
				if (!$email) throw new Exception("Unknown UserEmail in job: ".$job->getData());
				$email->sendConfirmCode();
				unset($email);
			} else if ($data->type == 'NewUserTelephone') {
				logInfo("Pulled from Que, new UserTelephone:".$data->userTelephoneID);
				$telephone = UserTelephone::findByID($data->userTelephoneID);
				if (!$telephone) throw new Exception("Unknown UserTelephone in job: ".$job->getData());
				$telephone->sendConfirmCode();
				unset($telephone);
			} else if ($data->type == 'NewRequest') {
				logInfo("Pulled from Que, new Request:".$data->requestID);
				$r = Request::findByID($data->requestID);
				if (!$r) throw new Exception("Unknown Request in job: ".$job->getData());
				$r->notifyPeopleAfterRequestMade();
				unset($r);
			} else if ($data->type == 'NewRequestResponse') {
				logInfo("Pulled from Que, new Request Response:".$data->requestResponseID);
				$r = RequestResponse::findByID($data->requestResponseID);
				if (!$r) throw new Exception("Unknown Request Response in job: ".$job->getData());
				$r->notifyPeopleAfterRequestResponseMade();
				unset($r);	
			} else if ($data->type == 'NewSMSIn') {
				logInfo("Pulled from Que, new SMSIn:".$data->SMSInID);
				$s = SMSIn::findByID($data->SMSInID);
				if (!$s) throw new Exception("Unknown SMSIN in job: ".$job->getData());
				$s->process();
				unset($s);		
			} else if ($data->type == 'ForgottenPassword') {
				logInfo("Pulled from Que, forgotten password UserEmail:".$data->userEmailID);
				$email = UserEmail::findByID($data->userEmailID);
				if (!$email) throw new Exception("Unknown UserEmail in job: ".$job->getData());
				$email->sendForgottenPassword();
				unset($email);
			} else if ($data->type == 'NewSupportGroupNewsArticle') {
				logInfo("Pulled from Que, new  SupportGroupNewsArticle:".$data->supportGroupNewsArticleID);
				$supportGroupNewsArticle= SupportGroupNewsArticle::findByID($data->supportGroupNewsArticleID);
				$supportGroupNewsArticle->notifyPeopleAfterCreation();
				unset($supportGroupNewsArticle);
			} else if ($data->type == 'NewSupportGroupNewsArticleResponse') {
				logInfo("Pulled from Que, new  SupportGroupNewsArticle:".$data->supportGroupNewsArticleResponseID);
				$supportGroupNewsArticleResponse = SupportGroupNewsArticleResponse::findByID($data->supportGroupNewsArticleResponseID);
				$supportGroupNewsArticleResponse->notifyPeopleAfterCreation();
				unset($supportGroupNewsArticleResponse);
			} else if ($data->type == 'ShutDownWorker') {
				logInfo("Pulled from Que, Shut Down Worker Request. Bye.");
				$pheanstalk->delete($job);
				die();
			} else {
				throw new Exception("Unknown Type in job: ".$job->getData());
			}
		
			$pheanstalk->delete($job);
			logInfo("Finished Job. Memory ".  memory_get_usage()." peak ". memory_get_peak_usage());
		} catch (Exception $e) {
			$pheanstalk->bury($job);
			throw $e;
		}
	}

}




