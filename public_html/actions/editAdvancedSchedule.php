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

$schedule = ScheduleRule::findByIDForUserAccount($_POST['id'], $CURRENT_USER);
if (!$schedule) die('Not Found');

$emails = isset($_POST['emails']) && is_array($_POST['emails']) ? $_POST['emails'] : array();
$telephones = isset($_POST['telephones']) && is_array($_POST['telephones']) ? $_POST['telephones'] : array();
$twitters = isset($_POST['twitters']) && is_array($_POST['twitters']) ? $_POST['twitters'] : array();
$types = isset($_POST['types']) && is_array($_POST['types']) ? $_POST['types'] : array();
$days = isset($_POST['days']) && is_array($_POST['days']) ? $_POST['days'] : array();
$fromHours = isset($_POST['fromHours']) ? $_POST['fromHours'] : null;
$toHours = isset($_POST['toHours']) ? $_POST['toHours'] : null;

$schedule->edit($emails, $telephones, $twitters, $fromHours, $toHours, $days, $types);
$_SESSION['flashOK'] = "Schedule Edited";

header("Location: /myAdvancedSchedule.php");


