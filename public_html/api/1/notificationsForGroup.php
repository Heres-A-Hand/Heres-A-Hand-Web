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
require '../../../src/global.php';
require '../../../src/apiFuncs.php';


$currentUser = UserAccount::findByIDwithSessionID($in['userID'],$in['userCookie']);
if (!$currentUser) sendBadAuthAndDie();

$group = SupportGroup::findByIDForUser($in['groupID'], $currentUser);
if (!$group) sendNotfoundAndDie();

$out = array('result'=>true,'data'=>array());
foreach($group->getNotificationsVisibleToUser($currentUser) as $notification) {
	if (get_class($notification) == "Request") {
		$out['data'][] = array(
			'type'=>'request',
			'requestID'=>$notification->getId(),
			'userID'=>$notification->getCreatedByUserId(),
			'userDisplayName'=>$notification->getCreatedByDisplayName(),
			'summary'=>$notification->getSummary(),
		);
	} else if (get_class($notification) == "RequestResponse") {
		$out['data'][] = array(
			'type'=>'response',
			'requestID'=>$notification->getRequestId(),
			'responseID'=>$notification->getId(),
			'userID'=>$notification->getUserAccountID(),
			'userDisplayName'=>$notification->getDisplayName(),
			'response'=>trimString($notification->getResponse()),
		);
	} else if (get_class($notification) == "SupportGroupNewsArticle") {
		$out['data'][] = array(
			'type'=>'supportGroupNewsArticle',
			'supportGroupNewsArticleID'=>$notification->getId(),
			'userID'=>$notification->getCreatedByUserId(),
			'userDisplayName'=>$notification->getCreatedByDisplayName(),
			'summary'=>$notification->getSummary(),
		);
	} else if (get_class($notification) == "SupportGroupNewsArticleResponse") {
		$out['data'][] = array(
			'type'=>'supportGroupNewsArticleResponse',
			'supportGroupNewsArticleID'=>$notification->getSupportGroupNewsArticleId(),
			'supportGroupNewsArticleResponseID'=>$notification->getId(),
			'userID'=>$notification->getCreatedByUserId(),
			'userDisplayName'=>$notification->getDisplayName(),
			'response'=>trimString($notification->getResponse()),
		);		
	}
}

print json_encode($out);

