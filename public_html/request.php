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
mustBeLoggedIn();

$request = Request::findByIDForUser($_GET['id'], $CURRENT_USER);
if (!$request) die("Not Found");

// getting the group using this function also has the effect of setting this group as the current group, so the header of the page appears properly.
$group = getCurrentUserSupportGroupOrDie($request->getSupportGroupId());

$s = getSmarty();
$s->assign('request', $request);
$s->assign('PAGETITLE','Request');
$s->assign('helpPage','request');

if ($request->isOpen() && !$request->isOnCalendar() && $request->getCreatedByUserId() == $CURRENT_USER->getId()) {
	setCalendarVariablesOnSmarty($s);
}

if (isset($_SESSION['requestResponsePosted']) && $_SESSION['requestResponsePosted']) {
	$s->assign('requestResponsePosted',true);
	unset($_SESSION['requestResponsePosted']);
} else {
	$s->assign('requestResponsePosted',false);
}

$s->display('request.htm');
