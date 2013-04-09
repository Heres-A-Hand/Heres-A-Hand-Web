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


class SavedRequest {


	private $id;
	private $summary;
	private $request;
	private $support_group_id;
	private $created_at;
	private $created_by_user_id;
	
	public function  __construct($data) {
		if (isset($data['id'])) $this->id = $data['id'];
		if (isset($data['summary'])) $this->summary = $data['summary'];
		if (isset($data['request'])) $this->request = $data['request'];
		if (isset($data['support_group_id'])) $this->support_group_id = $data['support_group_id'];

		if (isset($data['created_at'])) $this->created_at = $data['created_at'];
		if (isset($data['created_by_user_id'])) $this->created_by_user_id = $data['created_by_user_id'];
	}


	/** @return SavedRequest **/
	public static function findByIDWithinSupportGroupForUser($id, SupportGroup $group, UserAccount $user) {
		$db = getDB();
		$s = $db->prepare("SELECT saved_request.* FROM saved_request ".
				"WHERE saved_request.id = :id AND saved_request.support_group_id = :sid  AND saved_request.created_by_user_id = :uid");
		$s->execute(array('id'=>$id,'sid'=>$group->getId(),'uid'=>$user->getId()));
		if ($s->rowCount() == 1) {
			return new SavedRequest($s->fetch());
		}
	}

	
	
	public function getId  () { return $this->id; }
	public function getRequest  () { return $this->request; }
	public function getSummary() { return $this->summary; }
	public function getSupportGroupId  () { return $this->support_group_id; }

	public function getCreatedAt  () { return $this->created_at; }
	public function getCreatedAtInSeconds() { return strtotime($this->created_at); }
	public function getAgeFromOpenedInSeconds() { return getCurrentTime() - strtotime($this->created_at); }
	public function getCreatedByUserId  () { return $this->created_by_user_id; }

	
}

