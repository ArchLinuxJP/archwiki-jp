<?php

namespace PageImages\Tests\Hooks;

use LinksUpdate;
use PageImages;
use PageImages\Hooks\LinksUpdateHookHandler;
use ParserOutput;
use MediaWikiTestCase;
use RepoGroup;
use Title;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \PageImages\Hooks\LinksUpdateHookHandler
 *
 * @group PageImages
 *
 * @license WTFPL
 * @author Thiemo Kreuz
 */
class LinksUpdateHookHandlerTest extends MediaWikiTestCase {

	public function tearDown() {
		// remove mock added in testGetMetadata()
		RepoGroup::destroySingleton();
		parent::tearDown();
	}

	public function setUp() {
		parent::setUp();

		// Force LinksUpdateHookHandler::getPageImageCanditates to look at all
		// sections.
		$this->setMwGlobals( 'wgPageImagesLeadSectionOnly', false );
	}

	/**
	 * @param array[] $images
	 * @param array[]|bool $leadImages
	 *
	 * @return LinksUpdate
	 */
	private function getLinksUpdate( array $images, $leadImages = false ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData( 'pageImages', $images );
		$parserOutputLead = new ParserOutput();
		$parserOutputLead->setExtensionData( 'pageImages', $leadImages ?: $images );

		$rev = $this->getMockBuilder( 'Revision' )
			->disableOriginalConstructor()
			->getMock();

		$content = $this->getMockBuilder( 'AbstractContent' )
			->disableOriginalConstructor()
			->getMock();

		$sectionContent = $this->getMockBuilder( 'AbstractContent' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate = $this->getMockBuilder( 'LinksUpdate' )
			->disableOriginalConstructor()
			->getMock();

		$linksUpdate->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( new Title( 'LinksUpdateHandlerTest' ) ) );

		$linksUpdate->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$linksUpdate->expects( $this->any() )
			->method( 'getRevision' )
			->will( $this->returnValue( $rev ) );

		$rev->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

		$content->expects( $this->any() )
			->method( 'getSection' )
			->will( $this->returnValue( $sectionContent ) );

		$sectionContent->expects( $this->any() )
			->method( 'getParserOutput' )
			->will( $this->returnValue( $parserOutputLead ) );

		return $linksUpdate;
	}

	/**
	 * Required to make wfFindFile in LinksUpdateHookHandler::getScore return something.
	 * @return RepoGroup
	 */
	private function getRepoGroup() {
		$file = $this->getMockBuilder( 'File' )
			->disableOriginalConstructor()
			->getMock();
		// ugly hack to avoid all the unmockable crap in FormatMetadata
		$file->expects( $this->any() )
			->method( 'isDeleted' )
			->will( $this->returnValue( true ) );

		$repoGroup = $this->getMockBuilder( 'RepoGroup' )
			->disableOriginalConstructor()
			->getMock();
		$repoGroup->expects( $this->any() )
			->method( 'findFile' )
			->will( $this->returnValue( $file ) );

		return $repoGroup;
	}

	/**
	 * @dataProvider provideDoLinksUpdate
	 * @covers \PageImages\Hooks\LinksUpdateHookHandler::doLinksUpdate
	 */
	public function testDoLinksUpdate(
		array $images,
		$expectedFreeFileName,
		$expectedNonFreeFileName
	) {
		$linksUpdate = $this->getLinksUpdate( $images );
		$mock = TestingAccessWrapper::newFromObject(
				$this->getMockBuilder( LinksUpdateHookHandler::class )
				->setMethods( [ 'getScore', 'isImageFree' ] )
				->getMock()
		);

		$scoreMap = [];
		$isFreeMap = [];
		$counter = 0;
		foreach ( $images as $image ) {
			array_push( $scoreMap, [ $image, $counter++, $image['score'] ] );
			array_push( $isFreeMap, [ $image['filename'], $image['isFree'] ] );
		}

		$mock->expects( $this->any() )
			->method( 'getScore' )
			->will( $this->returnValueMap( $scoreMap ) );

		$mock->expects( $this->any() )
			->method( 'isImageFree' )
			->will( $this->returnValueMap( $isFreeMap ) );

		$mock->doLinksUpdate( $linksUpdate );

		$this->assertTrue( property_exists( $linksUpdate, 'mProperties' ), 'precondition' );
		if ( is_null( $expectedFreeFileName ) ) {
			$this->assertArrayNotHasKey( PageImages::PROP_NAME_FREE, $linksUpdate->mProperties );
		} else {
			$this->assertSame( $expectedFreeFileName,
				$linksUpdate->mProperties[PageImages::PROP_NAME_FREE] );
		}
		if ( is_null( $expectedNonFreeFileName ) ) {
			$this->assertArrayNotHasKey( PageImages::PROP_NAME, $linksUpdate->mProperties );
		} else {
			$this->assertSame( $expectedNonFreeFileName, $linksUpdate->mProperties[PageImages::PROP_NAME] );
		}
	}

	public function provideDoLinksUpdate() {
		return [
			// both images are non-free
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => false ],
				],
				null,
				'A.jpg'
			],
			// both images are free
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => true ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => true ],
				],
				'A.jpg',
				null
			],
			// one free (with a higher score), one non-free image
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => true ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => false ],
				],
				'A.jpg',
				null
			],
			// one non-free (with a higher score), one free image
			[
				[
					[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
					[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => true ],
				],
				'B.jpg',
				'A.jpg'
			]
		];
	}

	/**
	 * @covers \PageImages\Hooks\LinksUpdateHookHandler::getPageImageCandidates
	 */
	public function testGetPageImageCandidates() {
		$candidates = [
				[ 'filename' => 'A.jpg', 'score' => 100, 'isFree' => false ],
				[ 'filename' => 'B.jpg', 'score' => 90, 'isFree' => false ],
		];
		$linksUpdate = $this->getLinksUpdate( $candidates, array_slice( $candidates, 0, 1 ) );

		// should get without lead.
		$handler = new LinksUpdateHookHandler();
		$this->setMwGlobals( 'wgPageImagesLeadSectionOnly', false );
		$images = $handler->getPageImageCandidates( $linksUpdate );
		$this->assertCount( 2, $images, 'All images are returned.' );

		$this->setMwGlobals( 'wgPageImagesLeadSectionOnly', true );
		$images = $handler->getPageImageCandidates( $linksUpdate );
		$this->assertCount( 1, $images, 'Only lead images are returned.' );
	}

	/**
	 * @dataProvider provideGetScore
	 */
	public function testGetScore( $image, $scoreFromTable, $position, $expected ) {
		$mock = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( LinksUpdateHookHandler::class )
				->setMethods( [ 'scoreFromTable', 'getMetadata', 'getRatio', 'getBlacklist' ] )
				->getMock()
		);
		$mock->expects( $this->any() )
			->method( 'scoreFromTable' )
			->will( $this->returnValue( $scoreFromTable ) );
		$mock->expects( $this->any() )
			->method( 'getRatio' )
			->will( $this->returnValue( 0 ) );
		$mock->expects( $this->any() )
			->method( 'getBlacklist' )
			->will( $this->returnValue( [ 'blacklisted.jpg' => 1 ] ) );

		$score = $mock->getScore( $image, $position );
		$this->assertEquals( $expected, $score );
	}

	public function provideGetScore() {
		return [
			[
				[ 'filename' => 'A.jpg', 'handler' => [ 'width' => 100 ] ],
				100,
				0,
				// width score + ratio score + position score
				100 + 100 + 8
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				1,
				// width score + ratio score + position score
				106
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				2,
				// width score + ratio score + position score
				104
			],
			[
				[ 'filename' => 'A.jpg', 'fullwidth' => 100 ],
				50,
				3,
				// width score + ratio score + position score
				103
			],
			[
				[ 'filename' => 'blacklisted.jpg', 'fullwidth' => 100 ],
				50,
				3,
				// blacklist score
				- 1000
			],
		];
	}

	/**
	 * @dataProvider provideScoreFromTable
	 * @covers \PageImages\Hooks\LinksUpdateHookHandler::scoreFromTable
	 */
	public function testScoreFromTable( $type, $value, $expected ) {
		global $wgPageImagesScores;

		$handlerWrapper = TestingAccessWrapper::newFromObject( new LinksUpdateHookHandler );

		$score = $handlerWrapper->scoreFromTable( $value, $wgPageImagesScores[$type] );
		$this->assertEquals( $expected, $score );
	}

	public function provideScoreFromTable() {
		return [
			[ 'width', 100, -100 ],
			[ 'width', 119, -100 ],
			[ 'width', 300, 10 ],
			[ 'width', 400, 10 ],
			[ 'width', 500, 5 ],
			[ 'width', 600, 5 ],
			[ 'width', 601, 0 ],
			[ 'width', 999, 0 ],
			[ 'galleryImageWidth', 99, -100 ],
			[ 'galleryImageWidth', 100, 0 ],
			[ 'galleryImageWidth', 500, 0 ],
			[ 'ratio', 1, -100 ],
			[ 'ratio', 3, -100 ],
			[ 'ratio', 4, 0 ],
			[ 'ratio', 5, 0 ],
			[ 'ratio', 10, 5 ],
			[ 'ratio', 20, 5 ],
			[ 'ratio', 25, 0 ],
			[ 'ratio', 30, 0 ],
			[ 'ratio', 31, -100 ],
			[ 'ratio', 40, -100 ],
		];
	}

	/**
	 * @dataProvider provideIsFreeImage
	 * @covers \PageImages\Hooks\LinksUpdateHookHandler::isImageFree
	 */
	public function testIsFreeImage( $fileName, $metadata, $expected ) {
		RepoGroup::setSingleton( $this->getRepoGroup() );
		$mock = TestingAccessWrapper::newFromObject(
			$this->getMockBuilder( LinksUpdateHookHandler::class )
				->setMethods( [ 'fetchFileMetadata' ] )
				->getMock()
		);
		$mock->expects( $this->any() )
			->method( 'fetchFileMetadata' )
			->will( $this->returnValue( $metadata ) );
		$this->assertEquals( $expected, $mock->isImageFree( $fileName ) );
	}

	public function provideIsFreeImage() {
		return [
			[ 'A.jpg', [], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => '0' ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => 0 ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => false ] ], true ],
			[ 'A.jpg', [ 'NonFree' => [ 'value' => 'something' ] ], false ],
			[ 'A.jpg', [ 'something' => [ 'value' => 'something' ] ], true ],
		];
	}
}
