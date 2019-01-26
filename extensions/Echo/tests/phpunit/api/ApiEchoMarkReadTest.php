<?php

/**
 * @group medium
 * @group API
 * @group Database
 * @covers ApiQuery
 */
class ApiEchoMarkReadTest extends ApiTestCase {

	protected function setUp() {
		parent::setUp();
		$this->doLogin();
	}

	function getTokens() {
		return $this->getTokenList( self::$users['sysop'] );
	}

	public function testMarkReadWithList() {
		$tokens = $this->getTokens();
		// Grouping by section
		$data = $this->doApiRequest( [
			'action' => 'echomarkread',
			'notlist' => '121|122|123',
			'token' => $tokens['edittoken'] ] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		// Alert
		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		// Message
		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

	public function testMarkReadWithAll() {
		$tokens = $this->getTokens();
		// Grouping by section
		$data = $this->doApiRequest( [
			'action' => 'echomarkread',
			'notall' => '1',
			'token' => $tokens['edittoken'] ] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		// Alert
		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		// Message
		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

	public function testMarkReadWithSections() {
		$tokens = $this->getTokens();
		// Grouping by section
		$data = $this->doApiRequest( [
			'action' => 'echomarkread',
			'sections' => 'alert|message',
			'token' => $tokens['edittoken'] ] );

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'echomarkread', $data[0]['query'] );

		$result = $data[0]['query']['echomarkread'];

		// General count
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'rawcount', $result );

		// Alert
		$this->assertArrayHasKey( 'alert', $result );
		$alert = $result['alert'];
		$this->assertArrayHasKey( 'rawcount', $alert );
		$this->assertArrayHasKey( 'count', $alert );

		// Message
		$this->assertArrayHasKey( 'message', $result );
		$message = $result['message'];
		$this->assertArrayHasKey( 'rawcount', $message );
		$this->assertArrayHasKey( 'count', $message );
	}

}