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


$data = array_merge($_POST, $_GET);

if (!isset($data['AccountSid'])) die("No");
if (!isset($data['SmsSid'])) die("No");
if (!isset($data['From'])) die("No");
if (!isset($data['To'])) die("No");
if (!isset($data['Body'])) die("No");

logDebug("TWILIO call from ".$_SERVER['REMOTE_ADDR']." for TWILIO ID ".$data['AccountSid']);

if ($data['AccountSid'] == TWILIO_ID) {
	SMSIn::create($data['From'], $data['Body'], $data['AccountSid']);
} else {
	logWarning("We recieved a TWILIO msg for acct ID ".$data['AccountSid']." which is not ours! Ours is ".TWILIO_ID);
}


header('Content-type: text/xml');
print '<?xml version="1.0" encoding="UTF-8" ?><Response></Response>';
