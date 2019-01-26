<?php

namespace CirrusSearch;

use CirrusSearch\Test\DummyConnection;
use Elastica\Query;
use MediaWiki\MediaWikiServices;
use Title;

/**
 * @covers \CirrusSearch\Searcher
 * @group CirrusSearch
 */
class SearcherTest extends CirrusTestCase {

	public function setUp() {
		parent::setUp();
		MediaWikiServices::getInstance()->getConfigFactory()->register( 'CirrusSearch',
			function () {
				return new SearchConfigUsageDecorator();
			}
		);
	}

	public function tearDown() {
		MediaWikiServices::getInstance()
			->resetServiceForTesting( 'ConfigFactory' );
		parent::tearDown();
	}

	public function searchTextProvider() {
		$configs = [
			'default' => [],
		];
		// globals overrides. All tests will be run for each defined configuration
		foreach ( CirrusTestCase::findFixtures( 'searchText/*.config' ) as $configFile ) {
			$configName = substr( basename( $configFile ), 0, -7 );
			$configs[$configName] = CirrusTestCase::loadFixture( $configFile );
		}
		$tests = [];
		foreach ( CirrusTestCase::findFixtures( 'searchText/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, -6 );
			$querySettings = CirrusTestCase::loadFixture( $queryFile );
			foreach ( $configs as $configName => $config ) {
				$expectedFile = substr( $queryFile, 0, -5 ) . $configName . '.expected';
				$expected = CirrusTestCase::hasFixture( $expectedFile )
					? CirrusTestCase::loadFixture( $expectedFile )
					// Flags test to generate a new fixture
					: $expectedFile;
				if ( isset( $querySettings['config'] ) ) {
					$config = $querySettings['config'] + $config;
				}
				$tests["{$testName}-{$configName}"] = [
					$config,
					$expected,
					$querySettings['query'],
				];
			}
		}

		return $tests;
	}

	/**
	 * @dataProvider searchTextProvider
	 */
	public function testSearchText( array $config, $expected, $queryString ) {
		// Override some config for parsing purposes
		$this->setMwGlobals( $config + [
			// We want to override the wikiid for consistent output, but this might break everything else...
			'wgCirrusSearchExtraIndexes' => [],
			'wgCirrusSearchExtraIndexBoostTemplates' => [],
			'wgCirrusSearchIndexBaseName' => 'wiki',
			'wgCirrusSearchUseExperimentalHighlighter' => true,
			'wgCirrusSearchWikimediaExtraPlugin' => [
				'regex' => [ 'build', 'use' ],
			],
			'wgCirrusSearchQueryStringMaxDeterminizedStates' => 500,
			'wgContentNamespaces' => [ NS_MAIN ],
			// Override the list of namespaces to give more deterministic results
			'wgHooks' => [
				'CanonicalNamespaces' => [
					function ( &$namespaces ) {
						$namespaces = [
							0 => '',
							-2 => 'Media',
							-1 => 'Special',
							1 => 'Talk',
							2 => 'User',
							3 => 'User_talk',
							4 => 'Project',
							5 => 'Project_talk',
							6 => 'File',
							7 => 'File_talk',
							8 => 'MediaWiki',
							9 => 'MediaWiki_talk',
							10 => 'Template',
							11 => 'Template_talk',
							12 => 'Help',
							13 => 'Help_talk',
							14 => 'Category',
							15 => 'Category_talk',
						];
					}
				],
			] + $GLOBALS['wgHooks']
		] );

		// Set a couple pre-defined pages for anything (morelike) that needs valid pages
		$linkCache = MediaWikiServices::getInstance()->getLinkCache();
		$linkCache->addGoodLinkObj( 12345, Title::newFromText( 'Some page' ) );
		$linkCache->addGoodLinkObj( 23456, Title::newFromText( 'Other page' ) );

		$engine = new \CirrusSearch( null, null, CirrusDebugOptions::forDumpingQueriesInUnitTests() );
		// Set some default namespaces, otherwise installed extensions will change
		// the generated query
		$engine->setNamespaces( [
			NS_MAIN, NS_TALK, NS_USER, NS_USER_TALK,
		] );
		$engine->setShowSuggestion( true );
		$engine->setLimitOffset( 20, 0 );
		$encodedQuery = $engine->searchText( $queryString )->getValue();
		$elasticQuery = json_decode( $encodedQuery, true );
		// For extra fun, prefer-recent queries include a 'now' timestamp. We need to normalize that so
		// the output is actually the same.
		$elasticQuery = $this->normalizeNow( $elasticQuery );
		// The helps with ensuring if there are minor code changes that change the ordering,
		// regenerating the fixture wont cause changes. Do it always, instead of only when
		// writing, so that the diff's from phpunit are also as minimal as possible.
		$elasticQuery = $this->normalizeOrdering( $elasticQuery );

		if ( is_string( $expected ) ) {
			// Flag to generate a new fixture.
			CirrusTestCase::saveFixture( $expected, $elasticQuery );
		} else {
			// Repeat normalizations applied to $elasticQuery
			$expected = $this->normalizeNow( $expected );

			// Finally compare some things
			$this->assertEquals( $expected, $elasticQuery, $encodedQuery );
		}
		$this->assertConfigIsExported();
	}

	/**
	 * @var string[] List of false positives detected by the assertions below
	 * Add config vars when you don't want to explicit export it and are sure
	 * that it won't be needed to build query on a target wiki.
	 */
	private static $CONFIG_VARS_FALSE_POSITIVES = [
		'CirrusSearchFetchConfigFromApi', // Should not be needed to build a crosswiki search
		'DBname',
		'SiteMatrixSites',
		'CirrusSearchInterwikiPrefixOverrides',
		'CirrusSearchCrossClusterSearch', // We explicitly want this to fall through to local wiki conf
	];

	private function assertConfigIsExported() {
		try {
			$notInApi = [];
			$notInSearchConfig = [];
			foreach ( array_keys( SearchConfigUsageDecorator::getUsedConfigKeys() ) as $k ) {
				if ( in_array( $k, self::$CONFIG_VARS_FALSE_POSITIVES ) ) {
					continue;
				}
				if ( !in_array( $k, \CirrusSearch\Api\ConfigDump::$WHITE_LIST ) ) {
					$notInApi[] = $k;
				}
				if ( preg_match( '/^CirrusSearch/', $k ) == 0 ) {
					if ( !in_array( 'wg' . $k, SearchConfig::getNonCirrusConfigVarNames() ) ) {
						$notInSearchConfig[] = $k;
					}
				}
			}
			$this->assertEmpty( $notInApi, implode( ',', $notInApi ) .
				" are exported from \CirrusSearch\Api\ConfigDump" );
			$this->assertEmpty( $notInSearchConfig, implode( ',', $notInSearchConfig ) .
				" are allowed in SearchConfig::getNonCirrusConfigVarNames()" );
		} finally {
			SearchConfigUsageDecorator::resetUsedConfigKeys();
		}
	}

	private function normalizeNow( array $query ) {
		array_walk_recursive( $query, function ( &$value, $key ) {
			if ( $key === 'now' && is_int( $value ) ) {
				$value = 1468084245000;
			}
		} );

		return $query;
	}

	private function normalizeOrdering( array $query ) {
		foreach ( $query as $key => $value ) {
			if ( is_array( $value ) ) {
				$query[$key] = $this->normalizeOrdering( $value );
			}
		}
		if ( isset( $query[0] ) ) {
			// list like. Expensive, but sorta-works?
			// TODO: This breaks things that require a specific ordering, such as the token count router
			usort( $query, function ( $a, $b ) {
				return strcmp( json_encode( $a ), json_encode( $b ) );
			} );
		} else {
			// dict like
			ksort( $query );
		}

		return $query;
	}

	public function archiveFixtureProvider() {
		$tests = [];
		foreach ( CirrusTestCase::findFixtures( 'archiveSearch/*.query' ) as $queryFile ) {
			$testName = substr( basename( $queryFile ), 0, - 6 );
			$query = file_get_contents( CirrusTestCase::FIXTURE_DIR . $queryFile );
			// Remove trailing newline
			$query = preg_replace( '/\n$/', '', $query );
			$expectedFile = substr( $queryFile, 0, -5 ) . 'expected';
			$expected = CirrusTestCase::hasFixture( $expectedFile )
					? CirrusTestCase::loadFixture( $expectedFile )
					// Flags test to generate a new fixture
					: $expectedFile;
			$tests[$testName] = [
				$expected,
				$query,
			];

		}
		return $tests;
	}

	/**
	 * @dataProvider archiveFixtureProvider
	 * @param mixed $expected
	 * @param array $query
	 */
	public function testArchiveQuery( $expected, $query ) {
		$this->setMwGlobals( [
				'wgCirrusSearchIndexBaseName' => 'wiki',
				'wgCirrusSearchQueryStringMaxDeterminizedStates' => 500,
				'wgContentNamespaces' => [ NS_MAIN ],
				'wgCirrusSearchEnableArchive' => true,
		] );

		$title = Title::newFromText( $query );
		if ( $title ) {
			$ns = $title->getNamespace();
			$termMain = $title->getText();
		} else {
			$ns = 0;
			$termMain = $query;
		}

		$engine = new \CirrusSearch( null, null, CirrusDebugOptions::forDumpingQueriesInUnitTests() );
		$engine->setLimitOffset( 20, 0 );
		$engine->setNamespaces( [ $ns ] );
		$elasticQuery = $engine->searchArchiveTitle( $termMain )->getValue();
		$decodedQuery = json_decode( $elasticQuery, true );

		if ( is_string( $expected ) ) {
			// Flag to generate a new fixture.
			CirrusTestCase::saveFixture( $expected, $decodedQuery );
		} else {

			// Finally compare some things
			$this->assertEquals( $expected, $decodedQuery, $elasticQuery );
		}
	}

	public function testImpossibleQueryResults() {
		$engine = new \CirrusSearch();
		// query is invalid, filesize:> needs an integer
		$status = $engine->searchText( 'filesize:>q' );
		$this->assertTrue( $status->isOK(), 'search didnt fail' );
		$this->assertFalse( $status->isGood(), 'but it has warnings' );
		$this->assertTrue( $status->getValue()->searchContainedSyntax(), 'it used special syntax' );
		$this->assertEquals( 0, $status->getValue()->numRows(), 'and returned no results' );
	}

	public function testApplyDebugOptions() {
		$config = new HashSearchConfig( [] );
		$searcher = new Searcher( new DummyConnection(), 0, 20, $config,
			[], null, false,
			CirrusDebugOptions::fromRequest( new \FauxRequest( [ 'cirrusExplain' => 'pretty' ] ) ) );
		$query = new Query();
		$searcher->applyDebugOptionsToQuery( $query );
		$this->assertTrue( $query->getParam( 'explain' ) );

		$searcher = new Searcher( new DummyConnection(), 0, 20, $config,
			[], null, null,
			CirrusDebugOptions::fromRequest( new \FauxRequest() ) );
		$query = new Query();
		$searcher->applyDebugOptionsToQuery( $query );
		$this->assertFalse( $query->hasParam( 'explain' ) );
	}
}

class SearchConfigUsageDecorator extends SearchConfig {
	private static $usedConfigKeys = [];

	public function get( $name ) {
		$val = parent::get( $name );
		// Some config vars are objects.. (e.g. wgContLang)
		if ( !is_object( $val ) ) {
			static::$usedConfigKeys[$name] = true;
		}
		return $val;
	}

	public static function getUsedConfigKeys() {
		return static::$usedConfigKeys;
	}

	public static function resetUsedConfigKeys() {
		static::$usedConfigKeys = [];
	}
}
