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

$in = array_merge($_GET,$_POST);
header('Content-type: application/json');

function sendBadAuthAndDie() {
	print json_encode(array('result'=>false,'authenticationError'=>true,'error'=>'Authentication'));
	die();
}


function sendNotfoundAndDie() {
	print json_encode(array('result'=>false,'error'=>'Not Found'));
	die();
}

function getSummaryDataForRequest(Request $request) {
	return array(
		'id'=>$request->getId(),
		'summary'=>$request->getSummary(),
		'request'=>$request->getRequest(),
		'isOpen'=>$request->isOpen(),
		'isClosed'=>$request->isClosed(),
		'isCancelled'=>$request->isCancelled(),
		'fromDay'=>$request->getFromDay(),
		'fromMonth'=>$request->getFromMonth(),
		'fromYear'=>$request->getFromYear(),
		'fromHour'=>$request->getFromHour(),
		'toDay'=>$request->getToDay(),
		'toMonth'=>$request->getToMonth(),
		'toYear'=>$request->getToYear(),
		'toHour'=>$request->getToHour(),


	);
}

function trimString($in, $len=200) {
	if (strlen($in) > $len) {
		return substr($in, 0, $len)." ...";
	} else {
		return $in;
	}
}


