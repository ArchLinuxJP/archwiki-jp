<?php

namespace MediaWiki\Tests\Revision;

use ActorMigration;
use CommentStore;
use MediaWiki\Logger\Spi as LoggerSpi;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\Revision\RevisionStoreFactory;
use MediaWiki\Storage\BlobStore;
use MediaWiki\Storage\BlobStoreFactory;
use MediaWiki\Storage\NameTableStore;
use MediaWiki\Storage\NameTableStoreFactory;
use MediaWiki\Storage\SqlBlobStore;
use MediaWikiTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WANObjectCache;
use Wikimedia\Rdbms\ILBFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\TestingAccessWrapper;

class RevisionStoreFactoryTest extends MediaWikiTestCase {

	public function testValidConstruction_doesntCauseErrors() {
		new RevisionStoreFactory(
			$this->getMockLoadBalancerFactory(),
			$this->getMockBlobStoreFactory(),
			$this->getNameTableStoreFactory(),
			$this->getHashWANObjectCache(),
			$this->getMockCommentStore(),
			ActorMigration::newMigration(),
			MIGRATION_OLD,
			$this->getMockLoggerSpi(),
			true
		);
		$this->assertTrue( true );
	}

	public function provideWikiIds() {
		yield [ true ];
		yield [ false ];
		yield [ 'somewiki' ];
		yield [ 'somewiki', MIGRATION_OLD , false ];
		yield [ 'somewiki', MIGRATION_NEW , true ];
	}

	/**
	 * @dataProvider provideWikiIds
	 */
	public function testGetRevisionStore(
		$wikiId,
		$mcrMigrationStage = MIGRATION_OLD,
		$contentHandlerUseDb = true
	) {
		$lbFactory = $this->getMockLoadBalancerFactory();
		$blobStoreFactory = $this->getMockBlobStoreFactory();
		$nameTableStoreFactory = $this->getNameTableStoreFactory();
		$cache = $this->getHashWANObjectCache();
		$commentStore = $this->getMockCommentStore();
		$actorMigration = ActorMigration::newMigration();
		$loggerProvider = $this->getMockLoggerSpi();

		$factory = new RevisionStoreFactory(
			$lbFactory,
			$blobStoreFactory,
			$nameTableStoreFactory,
			$cache,
			$commentStore,
			$actorMigration,
			$mcrMigrationStage,
			$loggerProvider,
			$contentHandlerUseDb
		);

		$store = $factory->getRevisionStore( $wikiId );
		$wrapper = TestingAccessWrapper::newFromObject( $store );

		// ensure the correct object type is returned
		$this->assertInstanceOf( RevisionStore::class, $store );

		// ensure the RevisionStore is for the given wikiId
		$this->assertSame( $wikiId, $wrapper->wikiId );

		// ensure all other required services are correctly set
		$this->assertSame( $cache, $wrapper->cache );
		$this->assertSame( $commentStore, $wrapper->commentStore );
		$this->assertSame( $mcrMigrationStage, $wrapper->mcrMigrationStage );
		$this->assertSame( $actorMigration, $wrapper->actorMigration );
		$this->assertSame( $contentHandlerUseDb, $store->getContentHandlerUseDB() );

		$this->assertInstanceOf( ILoadBalancer::class, $wrapper->loadBalancer );
		$this->assertInstanceOf( BlobStore::class, $wrapper->blobStore );
		$this->assertInstanceOf( NameTableStore::class, $wrapper->contentModelStore );
		$this->assertInstanceOf( NameTableStore::class, $wrapper->slotRoleStore );
		$this->assertInstanceOf( LoggerInterface::class, $wrapper->logger );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|ILoadBalancer
	 */
	private function getMockLoadBalancer() {
		return $this->getMockBuilder( ILoadBalancer::class )
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|ILBFactory
	 */
	private function getMockLoadBalancerFactory() {
		$mock = $this->getMockBuilder( ILBFactory::class )
			->disableOriginalConstructor()->getMock();

		$mock->method( 'getMainLB' )
			->willReturnCallback( function () {
				return $this->getMockLoadBalancer();
			} );

		return $mock;
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|SqlBlobStore
	 */
	private function getMockSqlBlobStore() {
		return $this->getMockBuilder( SqlBlobStore::class )
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|BlobStoreFactory
	 */
	private function getMockBlobStoreFactory() {
		$mock = $this->getMockBuilder( BlobStoreFactory::class )
			->disableOriginalConstructor()->getMock();

		$mock->method( 'newSqlBlobStore' )
			->willReturnCallback( function () {
				return $this->getMockSqlBlobStore();
			} );

		return $mock;
	}

	/**
	 * @return NameTableStoreFactory
	 */
	private function getNameTableStoreFactory() {
		return new NameTableStoreFactory(
			$this->getMockLoadBalancerFactory(),
			$this->getHashWANObjectCache(),
			new NullLogger() );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|CommentStore
	 */
	private function getMockCommentStore() {
		return $this->getMockBuilder( CommentStore::class )
			->disableOriginalConstructor()->getMock();
	}

	private function getHashWANObjectCache() {
		return new WANObjectCache( [ 'cache' => new \HashBagOStuff() ] );
	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockObject|LoggerSpi
	 */
	private function getMockLoggerSpi() {
		$mock = $this->getMock( LoggerSpi::class );

		$mock->method( 'getLogger' )
			->willReturn( new NullLogger() );

		return $mock;
	}

}
