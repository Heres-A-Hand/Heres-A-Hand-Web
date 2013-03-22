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

class SupportGroupNewsArticleSearch extends BaseSearch {
	
	
	public function __construct() {
		parent::__construct();
		$this->className = 'SupportGroupNewsArticle';				
	}

	protected $orderBy = 'created_at DESC';

	private $supportGroup;
	public function inSupportGroup(SupportGroup $suppportGroup) {
		$this->supportGroup = $suppportGroup;
	}
	
	protected function execute() {
		if ($this->searchDone) throw new Exception("Search already done!");
		$db = getDB();
		$where = array();
		$joins = array();
		$vars = array();
		
		if ($this->supportGroup) {
			$where[] = " support_group_news_article.support_group_id = :sgid ";
			$vars['sgid'] = $this->supportGroup->getId();
		}
		
		$sql = "SELECT support_group_news_article.*, ".
			"user_account_created.display_name AS created_by_display_name,  user_account_created.avatar_key AS created_by_avatar_key ".
			"FROM support_group_news_article ".
			"JOIN user_account AS user_account_created ON user_account_created.id = support_group_news_article.created_by_user_id ".
			implode(" ", $joins).
			(count($where) > 0 ? " WHERE ".implode(" AND ", $where) : "").
			" ORDER BY ".$this->orderBy;
		$stat = $db->prepare($sql);
		$stat->execute($vars);
		while($d = $stat->fetch(PDO::FETCH_ASSOC)) {
			$this->results[] = $d;
		}
		$this->searchDone = true;
	}
	
	
	
}