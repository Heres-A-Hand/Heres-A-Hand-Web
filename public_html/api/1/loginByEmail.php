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


if (isset($_POST['email']) && $_POST['email'] && isset($_POST['password']) && $_POST['password']) {
	if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$user = UserAccount::findByEmail($_POST['email']);
		if ($user) {
			if ($user->checkPassword($_POST['password'])) {
				logIn($user, true);

				$data = array(
						'result'=>true,
						'userID'=>$user->getId(),
						'userCookie'=>$user->getNewSessionID(),
						'userDisplayName'=>$user->getDisplayName(),
						'groupID'=>-1
					);
				$groups = SupportGroup::findForUser($user);
				if (count($groups) > 0) $data['groupID'] = $groups[0]->getId();
				print json_encode($data);
			} else {
				logWarning("[API] Wrong Password when trying to log into User:".$user->getId()." using email ".$_POST['email']);
				print json_encode(array(
						'result'=>false,
						'error'=>'Details wrong'
					));
			}
		} else {
			logWarning("[API] Email not Known when trying to log in. ".$_POST['email']);
			print json_encode(array(
					'result'=>false,
					'error'=>'Details wrong'
				));
		}
	} else {
		logWarning("[API] Email not a normal address when trying to log in. ".$_POST['email']);
		print json_encode(array(
				'result'=>false,
				'error'=>'Details wrong'
			));
	}
} else {
	print json_encode(array(
			'result'=>false,
			'error'=>'You must pass an email and password'
		));
}


