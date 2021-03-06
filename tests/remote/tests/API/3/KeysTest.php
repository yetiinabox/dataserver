<?php
/*
    ***** BEGIN LICENSE BLOCK *****
    
    This file is part of the Zotero Data Server.
    
    Copyright © 2014 Center for History and New Media
                     George Mason University, Fairfax, Virginia, USA
                     http://zotero.org
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    ***** END LICENSE BLOCK *****
*/

namespace APIv3;
use API3 as API;
require_once 'APITests.inc.php';
require_once 'include/api3.inc.php';

class KeysTest extends APITests {
	public function testGetKeys() {
		// No anonymous access
		API::useAPIKey("");
		$response = API::userGet(
			self::$config['userID'],
			'keys'
		);
		$this->assert403($response);
		
		// No access with user's API key
		API::useAPIKey(self::$config['apiKey']);
		$response = API::userGet(
			self::$config['userID'],
			'keys'
		);
		$this->assert403($response);
		
		// Root access
		$response = API::userGet(
			self::$config['userID'],
			'keys',
			[],
			[
				"username" => self::$config['rootUsername'],
				"password" => self::$config['rootPassword']
			]
		);
		$this->assert200($response);
		$json = API::getJSONFromResponse($response);
		$this->assertTrue(is_array($json));
		$this->assertTrue(sizeOf($json) > 0);
	}
	
	
	public function testKeyCreateAndDelete() {
		API::useAPIKey("");
		
		$name = "Test " . uniqid();
		
		// Can't create as user
		$response = API::userPost(
			self::$config['userID'],
			'keys',
			json_encode([
				'name' => $name,
				'access' => [
					'user' => [
						'library' => true
					]
				]
			])
		);
		$this->assert403($response);
		
		// Create as root
		$response = API::userPost(
			self::$config['userID'],
			'keys',
			json_encode([
				'name' => $name,
				'access' => [
					'user' => [
						'library' => true
					]
				]
			]),
			[],
			[
				"username" => self::$config['rootUsername'],
				"password" => self::$config['rootPassword']
			]
		);
		$this->assert201($response);
		$json = API::getJSONFromResponse($response);
		$key = $json['key'];
		$this->assertEquals($json['name'], $name);
		$this->assertEquals(['user' => ['library' => true, 'files' => true]], $json['access']);
		
		// Delete anonymously (with embedded key)
		$response = API::userDelete(
			self::$config['userID'],
			"keys/$key"
		);
		$this->assert204($response);
		
		$response = API::userGet(
			self::$config['userID'],
			"keys/$key"
		);
		$this->assert404($response);
	}
}
