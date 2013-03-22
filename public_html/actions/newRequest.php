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

require '../../src/global.php';
mustBeLoggedIn();


$group = getCurrentUserSupportGroupOrDie();

if ($group->canMakeRequests() && isset($_POST['newMessageSummary']) && trim($_POST['newMessageSummary']) && $_POST['CSFRToken'] == $_SESSION['CSFRToken']) {
	$typeIDS = isset($_POST['types']) && is_array($_POST['types']) ? $_POST['types'] : array();
	
	// The UI works in terms of users request is hidden from, function expects list of users to show it to. Invert selection here.
	// or function expects null for "all users"
	$userIDS = null;
	$hide_userIDS = isset($_POST['hide_members']) && is_array($_POST['hide_members']) ? $_POST['hide_members'] : array();
	if (count($hide_userIDS) > 0) {
		$userIDS = array();
		foreach($group->getMembers() as $member) {
			if (!in_array($member->getId(), $hide_userIDS) && $member->getId() != $CURRENT_USER->getId()) $userIDS[] =  $member->getId();
		}
	}
	
	$id = $group->newRequest($CURRENT_USER, $_POST['newMessageSummary'], $_POST['newMessageRequest'],$typeIDS, $userIDS);
	
	if (isset($_POST['incCalendar']) && $_POST['incCalendar'] == "1") {
		
		list($from, $to, $error) = parseCalendarFormInputs($_POST);
		
		if ($error) {
			$_SESSION['flashError'] = "Request created, but there was a problem adding it to the calendar:" .$error;
		} else {
			$request = Request::findByID($id);
			$request->addToCalendar($from->getTimestamp() ,$to->getTimestamp(), $CURRENT_USER);
		}
		
	}
	
	$_SESSION['flashOK'] = "Request created!";
	
	header("Location: /request.php?id=".$id);
} else {
	$_SESSION['flashError'] = "Error; you must enter a summary";
	header("Location: /");
}
