<?php

namespace CirrusSearch\Query\Builder;

use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\SearchConfig;

/**
 * WIP: figure out what we need when building
 * certainly some states built by some keyword
 * or some classification of the query
 */
interface QueryBuildingContext {

	/**
	 * @return SearchConfig
	 */
	function getSearchConfig();

	/**
	 * @param KeywordFeatureNode $node
	 * @return array
	 */
	function getKeywordExpandedData( KeywordFeatureNode $node );
}
