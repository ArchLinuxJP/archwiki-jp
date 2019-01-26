<?php

namespace CirrusSearch\BuildDocument;

use Content;
use HtmlFormatter\HtmlFormatter;
use MediaWiki\Logger\LoggerFactory;
use ParserOutput;
use Sanitizer;

/**
 * Adds fields to the document that require article text.
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
 */
class PageTextBuilder extends ParseBuilder {
	/**
	 * @var string[] selectors to elements that are excluded entirely from search
	 */
	private $excludedElementSelectors = array(
		'audio', 'video',       // "it looks like you don't have javascript enabled..." do not need to index
		'sup.reference',        // The [1] for references
		'.mw-cite-backlink',    // The ↑ next to references in the references section
		'h1', 'h2', 'h3',       // Headings are already indexed in their own field.
		'h5', 'h6', 'h4',
		'.autocollapse',        // Collapsed fields are hidden by default so we don't want them showing up.
	);
	/**
	 * @var string[] selectors to elements that are considered auxiliary to article text for search
	 */
	private $auxiliaryElementSelectors = array(
		'.thumbcaption',        // Thumbnail captions aren't really part of the text proper
		'table',                // Neither are tables
		'.rellink',             // Common style for "See also:".
		'.dablink',             // Common style for calling out helpful links at the top of the article.
		'.searchaux',           // New class users can use to mark stuff as auxiliary to searches.
	);

	/**
	 * @param \Elastica\Document $doc
	 * @param Content $content
	 * @param ParserOutput $parserOutput
	 */
	public function __construct( \Elastica\Document $doc, Content $content, ParserOutput $parserOutput ) {
		parent::__construct( $doc, null, $content, $parserOutput );
	}

	/**
	 * @return \Elastica\Document
	 */
	public function build() {
		list( $text, $opening, $auxiliary ) = $this->buildTextToIndex();
		$this->doc->set( 'text', $text );
		$this->doc->set( 'opening_text', $opening );
		$this->doc->set( 'auxiliary_text', $auxiliary );
		$this->doc->set( 'text_bytes', $this->content->getSize() );
		$this->doc->set( 'source_text', $this->content->getTextForSearchIndex() );

		return $this->doc;
	}

	/**
	 * Fetch text to index. If $content is wikitext then render and strip things from it.
	 * Otherwise delegate to the $content itself.
	 *
	 * @return array Three tuple of (text, opening, auxiliary). Text is always string. Opening
	 *  is string or null. Auxiliary is string[].
	 */
	private function buildTextToIndex() {
		switch ( $this->content->getModel() ) {
			case CONTENT_MODEL_WIKITEXT:
				return $this->formatWikitext( $this->parserOutput );
			default:
				$text = $this->content->getTextForSearchIndex();
				return array( $text, null, array() );
		}
	}

	/**
	 * Get text to index from a ParserOutput assuming the content was wikitext.
	 *
	 * @param ParserOutput $parserOutput The parsed wikitext's parser output
	 * @return array who's first entry is text and second is opening text, and third is an
	 *  array of auxiliary text
	 */
	private function formatWikitext( ParserOutput $parserOutput ) {
		global $wgCirrusSearchBoostOpening;

		$parserOutput->setEditSectionTokens( false );
		$parserOutput->setTOCEnabled( false );
		$text = $parserOutput->getText();
		$opening = null;

		switch ( $wgCirrusSearchBoostOpening ) {
		case 'first_heading':
			$opening = $this->extractHeadingBeforeFirstHeading( $text );
			break;
		case 'none':
			break;
		default:
			LoggerFactory::getInstance( 'CirrusSearch' )->warning(
				"Invalid value for \$wgCirrusSearchBoostOpening: {wgCirrusSearchBoostOpening}",
				array( 'wgCirrusSearchBoostOpening' =>  $wgCirrusSearchBoostOpening )
			);
		}

		// Add extra spacing around break tags so text crammed together like<br>this doesn't make one word.
		$text = str_replace( '<br', "\n<br", $text );

		$formatter = new HtmlFormatter( $text );

		// Strip elements from the page that we never want in the search text.
		$formatter->remove( $this->excludedElementSelectors );
		$formatter->filterContent();

		// Strip elements from the page that are auxiliary text.  These will still be
		// searched but matches will be ranked lower and non-auxiliary matches will be
		// preferred in highlighting.
		$formatter->remove( $this->auxiliaryElementSelectors );
		$auxiliaryElements = $formatter->filterContent();
		$allText = trim( Sanitizer::stripAllTags( $formatter->getText() ) );
		$auxiliary = array();
		foreach ( $auxiliaryElements as $auxiliaryElement ) {
			$auxiliary[] = trim( Sanitizer::stripAllTags( $formatter->getText( $auxiliaryElement ) ) );
		}

		return array( $allText, $opening, $auxiliary );
	}

	/**
	 * @param string $text
	 * @return string|null
	 */
	private function extractHeadingBeforeFirstHeading( $text ) {
		$matches = array();
		if ( !preg_match( '/<h[123456]>/', $text, $matches, PREG_OFFSET_CAPTURE ) ) {
			// There isn't a first heading so we interpret this as the article
			// being entirely without heading.
			return null;
		}
		$text = substr( $text, 0, $matches[ 0 ][ 1 ] );
		if ( !$text ) {
			// There isn't any text before the first heading so we declare there isn't
			// a first heading.
			return null;
		}

		$formatter = new HtmlFormatter( $text );
		$formatter->remove( $this->excludedElementSelectors );
		$formatter->remove( $this->auxiliaryElementSelectors );
		$formatter->filterContent();
		$text = trim( Sanitizer::stripAllTags( $formatter->getText() ) );

		if ( !$text ) {
			// There isn't any text after filtering before the first heading so we declare
			// that there isn't a first heading.
			return null;
		}

		return $text;
	}
}
