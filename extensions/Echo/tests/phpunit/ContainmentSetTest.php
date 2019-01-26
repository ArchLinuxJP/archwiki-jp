<?php

/**
 * @group Echo
 */
class ContainmentSetTest extends MediaWikiTestCase {

	public function testGenericContains() {
		$list = new EchoContainmentSet( self::getTestUser()->getUser() );

		$list->addArray( [ 'foo', 'bar' ] );
		$this->assertTrue( $list->contains( 'foo' ) );
		$this->assertTrue( $list->contains( 'bar' ) );
		$this->assertFalse( $list->contains( 'whammo' ) );

		$list->addArray( [ 'whammo' ] );
		$this->assertTrue( $list->contains( 'whammo' ) );
	}

	public function testCachedListInnerListIsOnlyCalledOnce() {
		// the global $wgMemc during tests is an EmptyBagOStuff, so it
		// wont do anything.  We use a HashBagOStuff to get more like a real
		// client
		$innerCache = new HashBagOStuff;

		$inner = [ 'bing', 'bang' ];
		// We use a mock instead of the real thing for the $this->once() assertion
		// verifying that the cache doesn't just keep asking the inner object
		$list = $this->getMockBuilder( 'EchoArrayList' )
			->disableOriginalConstructor()
			->getMock();
		$list->expects( $this->once() )
			->method( 'getValues' )
			->will( $this->returnValue( $inner ) );

		$cached = new EchoCachedList( $innerCache, 'test_key', $list );

		// First run through should hit the main list, and save to innerCache
		$this->assertEquals( $inner, $cached->getValues() );
		$this->assertEquals( $inner, $cached->getValues() );

		// Reinitialize to get a fresh instance that will pull directly from
		// innerCache without hitting the $list
		$freshCached = new EchoCachedList( $innerCache, 'test_key', $list );
		$this->assertEquals( $inner, $freshCached->getValues() );
	}

	/**
	 * @Database
	 */
	public function testOnWikiList() {
		$this->editPage( 'User:Foo/Bar-baz', "abc\ndef\r\nghi\n\n\n" );

		$list = new EchoOnWikiList( NS_USER, "Foo/Bar-baz" );
		$this->assertEquals(
			[ 'abc', 'def', 'ghi' ],
			$list->getValues()
		);
	}

	public function testOnWikiListNonExistant() {
		$list = new EchoOnWikiList( NS_USER, "Some_Non_Existant_Page" );
		$this->assertEquals( [], $list->getValues() );
	}

	protected function editPage( $pageName, $text, $summary = '', $defaultNs = NS_MAIN ) {
		$title = Title::newFromText( $pageName, $defaultNs );
		$page = WikiPage::factory( $title );

		return $page->doEditContent( ContentHandler::makeContent( $text, $title ), $summary );
	}
}
