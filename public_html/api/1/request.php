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

$request = Request::findByIDForUser($in['requestID'], $currentUser);
if (!$request) sendNotfoundAndDie("Not Found");

$out = array('result'=>true,'data'=>array(
		'id'=>$request->getId(),
		'summary'=>$request->getSummary(),
		'request'=>$request->getRequest(),
		'createdByDisplayName'=>$request->getCreatedByDisplayName(),
		'createdByUserID'=>$request->getCreatedByUserId(),
		'isOpen'=>$request->isOpen(),
		'isClosed'=>$request->isClosed(),
		'isCancelled'=>$request->isCancelled(),
		'responses'=>array(),
	));

foreach($request->getResponses() as $response) {
	$out['data']['responses'][] = array(
			'id'=>$response->getId(),
			'response'=>$response->getResponse(),
			'displayName'=>$response->getDisplayName(),
			'userID'=>$response->getUserAccountID(),
		);
}

print json_encode($out);

