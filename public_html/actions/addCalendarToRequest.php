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

$request = Request::findByIDForUser($_GET['id'], $CURRENT_USER);
if (!$request) die("Not Found");
if (!$request->isOpen()) die("Request not open");
if ($request->isOnCalendar()) die("Request already on calendar");
if ($request->getCreatedByUserId() != $CURRENT_USER->getId()) die("No Request Author");

list($from, $to, $error) = parseCalendarFormInputs($_POST);

if ($error) {
	$_SESSION['flashError'] = $error;
} else {
	$request->addToCalendar($from->getTimestamp() ,$to->getTimestamp(), $CURRENT_USER);
}

header("Location: /request.php?id=".$request->getId());
