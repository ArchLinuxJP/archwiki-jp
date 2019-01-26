<?php

namespace CirrusSearch;

use CirrusSearch\LanguageDetector\LanguageDetectorFactory;
use CirrusSearch\LanguageDetector\TextCat;
use CirrusSearch\LanguageDetector\HttpAccept;

/**
 * Completion Suggester Tests
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @group CirrusSearch
 * @covers CirrusSearch\LanguageDetector\LanguageDetectorFactory
 * @covers CirrusSearch\LanguageDetector\TextCat
 * @covers CirrusSearch\LanguageDetector\HttpAccept
 */
class LanguageDetectTest extends CirrusTestCase {

	/**
	 * @var string
	 */
	private $textCatModelBaseDir;

	public function provideTestFactory() {
		return [
			'empty' => [ [ 'CirrusSearchLanguageDetectors' => [] ], [] ],
			'textcat only' => [
				[
					'CirrusSearchLanguageDetectors' => [
						'textcat' => TextCat::class
					]
				],
				[ 'textcat' => TextCat::class ]
			],
			'textcat first' => [
				[
					'CirrusSearchLanguageDetectors' => [
						'textcat' => TextCat::class,
						'accept-lang' => HttpAccept::class,
					]
				],
				[
					'textcat' => TextCat::class,
					'accept-lang' => HttpAccept::class,
				]
			],
			'accept-lang first' => [
				[
					'CirrusSearchLanguageDetectors' => [
						'accept-lang' => HttpAccept::class,
						'textcat' => TextCat::class,
					]
				],
				[
					'accept-lang' => HttpAccept::class,
					'textcat' => TextCat::class,
				]
			],
			'bad setup does not blow-up' => [
				[
					'CirrusSearchLanguageDetectors' => [
						'meh' => 'this.class.does.not.exist.Detector',
						'Title can do many things' => \Title::class,
					],
				],
				[]
			]
		];
	}

	/**
	 * @dataProvider provideTestFactory
	 */
	public function testFactory( $config, $exepected ) {
		$factory = new LanguageDetectorFactory( new HashSearchConfig( $config ), new \FauxRequest() );
		$actual = array_map( function ( $v ) {
			return get_class( $v );
		}, $factory->getDetectors() );
		$this->assertEquals( $exepected, $actual );
	}

	/**
	 * data provided is: text, lang1, lang2
	 * lang1 is result with defaults (testTextCatDetector)
	 * lang2 is result with non-defaults (testTextCatDetectorWithParams)
	 *		see notes inline
	 */
	public function getLanguageTexts() {
		return [
			// simple cases
			[ "Welcome to Wikipedia, the free encyclopedia that anyone can edit", "en", "en" ],
			[ "Добро пожаловать в Википедию", "ru", "uk" ],	// ru missing, uk present

			// more query-like cases
			[ "who stars in Breaking Bad?", "en", "en" ],
			[ "Jesenwang flugplatz", "de", "de" ],
			[ "volviendose malo", "es", null ], // en boosted -> too ambiguous
			[ "противоточный теплообменник", "ru", "uk" ], // ru missing, uk present
			[ "שובר שורות", "he", "he" ],
			[ "୨୪ ଅକ୍ଟୋବର", "or", null ],	// or missing, no alternative
			[ "th", "en", null ],	// too short
		];
	}

	public function setUp() {
		parent::setUp();
		$tc = new \ReflectionClass( 'TextCat' );
		$this->textCatModelBaseDir = dirname( $tc->getFileName() );
	}

	/**
	 * @dataProvider getLanguageTexts
	 * @param string $text
	 * @param string $language
	 * @param string $ignore
	 */
	public function testTextCatDetector( $text, $language, $ignore ) {
		$config = new HashSearchConfig( [
			'CirrusSearchTextcatModel' => [
				$this->textCatModelBaseDir . "/LM-query/",
				$this->textCatModelBaseDir . "/LM/"
			],
			'CirrusSearchTextcatLanguages' => null,
			'CirrusSearchTextcatConfig' => null,
		] );
		$textcat = new TextCat( $config );
		$detect = $textcat->detect( $text );
		$this->assertEquals( $language, $detect );
	}

	/**
	 * @dataProvider getLanguageTexts
	 * @param string $text
	 * @param string $ignore
	 * @param string $language
	 */
	public function testTextCatDetectorWithParams( $text, $ignore, $language ) {
		$config = new HashSearchConfig( [
			// only use one language model directory in old non-array format
			'CirrusSearchTextcatModel' => $this->textCatModelBaseDir . "/LM-query/",
			'CirrusSearchTextcatLanguages' => [ 'en', 'es', 'de', 'he', 'uk' ],
			'CirrusSearchTextcatConfig' => [
				'maxNgrams' => 9000,
				'maxReturnedLanguages' => 1,
				'resultsRatio' => 1.06,
				'minInputLength' => 3,
				'maxProportion' => 0.8,
				'langBoostScore' => 0.15,
				'numBoostedLangs' => 1,
			],
		] );
		$textcat = new TextCat( $config );
		$detect = $textcat->detect( $text );
		$this->assertEquals( $language, $detect );
	}

	public function testTextCatDetectorLimited() {
		$config = new HashSearchConfig( [
			'CirrusSearchTextcatModel' => [
				$this->textCatModelBaseDir . "/LM-query/",
				$this->textCatModelBaseDir . "/LM/"
			],
			'CirrusSearchTextcatLanguages' => [ "en", "ru" ],
			'CirrusSearchTextcatConfig' => null,
		] );
		$textcat = new TextCat( $config );
		$detect = $textcat->detect( "volviendose malo" );
		$this->assertEquals( "en", $detect );
	}

	/**
	 * Simply test the searchTextReal $forceLocal boolean flag.
	 * Testing the full chain seems hard so we just test that
	 * the $forceLocal flag is running a search on the local
	 * wiki.
	 */
	public function testLocalSearch() {
		$config = new HashSearchConfig( [
			'CirrusSearchInterwikiSources' => null,
			'CirrusSearchIndexBaseName' => 'mywiki',
			'CirrusSearchExtraIndexes' => [ NS_FILE => [ 'externalwiki_file' ] ],
		], [ 'inherit' ] );
		$cirrus = new MyCirrusSearch( null, $config, CirrusDebugOptions::forDumpingQueriesInUnitTests() );
		$cirrus->setNamespaces( [ NS_FILE ] );
		$result = $cirrus->mySearchTextReal( 'hello', $cirrus->getConfig(), true )->getValue();
		$result = json_decode( $result, true );
		$this->assertEquals( 'mywiki_general/page/_search', $result['path'] );
		$result = $cirrus->mySearchTextReal( 'hello', $cirrus->getConfig() )->getValue();
		$result = json_decode( $result, true );
		$this->assertEquals( 'mywiki_general,externalwiki_file/page/_search', $result['path'] );
	}

	public function getHttpLangs() {
		return [
			[ "en", [ "en" ], null ],
			[ "en", [ "en-UK", "en-US" ], null ],
			[ "pt", [ "pt-BR", "pt-PT" ], null ],
			[ "en", [ "en-UK", "*" ], null ],
			[ "es", [ "en-UK", "en-US" ], "en" ],
			[ "en", [ "pt-BR", "en-US" ], "pt" ],
			[ "en", [ "en-US", "pt-BR" ], "pt" ],
		];
	}

	/**
	 * @dataProvider getHttpLangs
	 * @param string $content
	 * @param array  $http
	 * @param string $result
	 */
	public function testHttpAcceptDetector( $content, $http, $result ) {
		$request = new \FauxRequest();
		$request->setHeader( 'Accept-Language', implode( ';', $http ) );
		$detector = HttpAccept::build( new HashSearchConfig( [ 'LanguageCode' => $content ] ), $request );

		$detect = $detector->detect( "test" );
		$this->assertEquals( $result, $detect );
	}
}

/**
 * Just a simple wrapper to access the protected method searchTextReal
 */
class MyCirrusSearch extends \CirrusSearch {
	public function mySearchTextReal( $term, SearchConfig $config = null, $forceLocal = false ) {
		return $this->searchTextReal( $term, $config, $forceLocal );
	}
}
