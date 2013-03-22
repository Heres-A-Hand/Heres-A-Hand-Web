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

abstract class BaseSearch {


	protected $searchDone = false;

	protected $className = null;
	
	protected $results = array();

	public function  __construct() {

	}

	abstract protected function execute();

	
	//---------------------------------------------------------- get results

	public function nextResult() {
		if (!$this->searchDone) $this->execute();
		$d = array_shift($this->results);
		return $d ? new $this->className($d) : null;
	}

	/** @return Integer the number of results on the current page (if pageing is on) **/
	public function num() {
		if (!$this->searchDone) $this->execute();
		return count($this->results);
	}

}