<?php

namespace CirrusSearch\Query;

use CirrusSearch\Search\Escaper;
use CirrusSearch\Search\SearchContext;

/**
 * @group CirrusSearch
 */
class InTitleFeatureTest extends BaseSimpleKeywordFeatureTest {

	public function parseProvider() {
		$defaults = [
			'fields' => [ 'title', 'redirect.title' ],
			'default_operator' => 'AND',
			'allow_leading_wildcard' => true,
			'fuzzy_prefix_length' => 2,
			'rewrite' => 'top_terms_boost_1024',
		];
		return [
			'basic search' => [
				[ 'query_string' => $defaults + [
					'query' => 'bridge',
				] ],
				'bridge ',
				false,
				'intitle:bridge',
			],
			'fuzzy search' => [
				[ 'query_string' => $defaults + [
					'query' => 'bridge~2',
				] ],
				'bridge~2 ',
				true,
				'intitle:bridge~2',
			],
			'gracefully handles titles including ~' => [
				[ 'query_string' => $defaults + [
					'query' => 'this\~that',
				] ],
				'this~that ',
				false,
				'intitle:this~that',
			],
			'maintains provided quotes' => [
				[ 'query_string' => $defaults + [
					'query' => '"something or other"',
				] ],
				'"something or other" ',
				false,
				'intitle:"something or other"',
			],
			'contains a star' => [
				[ 'query_string' => [
					'query' => 'zomg*',
					'fields' => [ 'title.plain', 'redirect.title.plain' ],
				] + $defaults ],
				'zomg* ',
				false,
				'intitle:zomg*'
			],
		];
	}

	/**
	 * @dataProvider parseProvider
	 */
	public function testParse( array $expectedQuery, $expectedTerm, $isFuzzy, $term ) {
		$context = $this->mockContextExpectingAddFilter( $expectedQuery );
		$context->expects( $this->once() )
			->method( 'setFuzzyQuery' )
			->with( $isFuzzy );

		// This test is kinda-sorta testing the escaper too ... maybe not optimal but simple
		$context->expects( $this->once() )
			->method( 'escaper' )
			->will( $this->returnValue( new Escaper( 'en' ) ) );

		$feature = new InTitleFeature();
		$this->assertEquals(
			$expectedTerm,
			$feature->apply( $context, $term )
		);
	}

	public function testNegatingDoesntKeepTerm() {
		$context = $this->getMockBuilder( SearchContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->once() )
			->method( 'escaper' )
			->will( $this->returnValue( new Escaper( 'en' ) ) );

		$feature = new InTitleFeature();
		$this->assertEquals( '', $feature->apply( $context, '-intitle:mediawiki' ) );
	}
}
