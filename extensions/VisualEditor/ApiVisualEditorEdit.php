<?php
/**
 * Parsoid/RESTBase+MediaWiki API wrapper.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

use \MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class ApiVisualEditorEdit extends ApiVisualEditor {

	public function __construct( ApiMain $main, $name, Config $config ) {
		parent::__construct( $main, $name, $config );
	}

	protected function saveWikitext( $title, $wikitext, $params ) {
		$apiParams = [
			'action' => 'edit',
			'title' => $title->getPrefixedDBkey(),
			'text' => $wikitext,
			'summary' => $params['summary'],
			'basetimestamp' => $params['basetimestamp'],
			'starttimestamp' => $params['starttimestamp'],
			'token' => $params['token'],
			'errorformat' => 'html',
		];

		if ( $params['minor'] ) {
			$apiParams['minor'] = true;
		} else {
			$apiParams['notminor'] = true;
		}

		// FIXME add some way that the user's preferences can be respected
		$apiParams['watchlist'] = $params['watch'] ? 'watch' : 'unwatch';

		if ( $params['captchaid'] ) {
			$apiParams['captchaid'] = $params['captchaid'];
		}

		if ( $params['captchaword'] ) {
			$apiParams['captchaword'] = $params['captchaword'];
		}

		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams + $this->getRequest()->getValues(),
				/* was posted? */ true
			),
			/* enable write? */ true
		);

		$api->execute();

		return $api->getResult()->getResultData();
	}

	protected function parseWikitext( $newRevId ) {
		$apiParams = [
			'action' => 'parse',
			'oldid' => $newRevId,
			'prop' => 'text|revid|categorieshtml|displaytitle|modules|jsconfigvars',
		];
		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams,
				/* was posted? */ false
			),
			/* enable write? */ true
		);

		$api->execute();
		$result = $api->getResult()->getResultData( null, [
			/* Transform content nodes to '*' */ 'BC' => [],
			/* Add back-compat subelements */ 'Types' => [],
			/* Remove any metadata keys from the links array */ 'Strip' => 'all',
		] );
		$content = isset( $result['parse']['text']['*'] ) ? $result['parse']['text']['*'] : false;
		$categorieshtml = isset( $result['parse']['categorieshtml']['*'] ) ?
			$result['parse']['categorieshtml']['*'] : false;
		$links = isset( $result['parse']['links'] ) ? $result['parse']['links'] : [];
		$revision = Revision::newFromId( $result['parse']['revid'] );
		$timestamp = $revision ? $revision->getTimestamp() : wfTimestampNow();
		$displaytitle = isset( $result['parse']['displaytitle'] ) ?
			$result['parse']['displaytitle'] : false;
		$modules = array_merge(
			isset( $result['parse']['modulescripts'] ) ? $result['parse']['modulescripts'] : [],
			isset( $result['parse']['modules'] ) ? $result['parse']['modules'] : [],
			isset( $result['parse']['modulestyles'] ) ? $result['parse']['modulestyles'] : []
		);
		$jsconfigvars = isset( $result['parse']['jsconfigvars'] ) ?
			$result['parse']['jsconfigvars'] : [];

		if ( $content === false || ( strlen( $content ) && $revision === null ) ) {
			return false;
		}

		if ( $displaytitle !== false ) {
			// Escape entities as in OutputPage::setPageTitle()
			$displaytitle = Sanitizer::normalizeCharReferences(
				Sanitizer::removeHTMLtags( $displaytitle ) );
		}

		return [
			'content' => $content,
			'categorieshtml' => $categorieshtml,
			'basetimestamp' => $timestamp,
			'starttimestamp' => wfTimestampNow(),
			'displayTitleHtml' => $displaytitle,
			'modules' => $modules,
			'jsconfigvars' => $jsconfigvars
		];
	}

	public static function tryDeflate( $content ) {
		if ( substr( $content, 0, 11 ) === 'rawdeflate,' ) {
			$deflated = base64_decode( substr( $content, 11 ) );
			MediaWiki\suppressWarnings();
			$inflated = gzinflate( $deflated );
			MediaWiki\restoreWarnings();
			if ( $deflated === $inflated || $inflated === false ) {
				// Static equivalent of $this->dieWithError
				throw ApiUsageException::newWithMessage(
					null,
					'apierror-visualeditor-invaliddeflate',
					'invaliddeflate'
				);
			}
			return $inflated;
		}
		return $content;
	}

	protected function getWikitext( $title, $params, $parserParams ) {
		if ( $params['cachekey'] !== null ) {
			$wikitext = $this->trySerializationCache( $params['cachekey'] );
			if ( !is_string( $wikitext ) ) {
				$this->dieWithError( 'apierror-visualeditor-badcachekey', 'badcachekey' );
			}
		} else {
			$wikitext = $this->getWikitextNoCache( $title, $params, $parserParams );
		}
		return $wikitext;
	}

	protected function getWikitextNoCache( $title, $params, $parserParams ) {
		$this->requireOnlyOneParameter( $params, 'html' );
		$wikitext = $this->postHTML(
			$title, self::tryDeflate( $params['html'] ), $parserParams, $params['etag']
		);
		if ( $wikitext === false ) {
			$this->dieWithError( 'apierror-visualeditor-docserver', 'docserver' );
		}
		return $wikitext;
	}

	protected function storeInSerializationCache( $title, $wikitext ) {
		global $wgMemc;

		if ( $wikitext === false ) {
			return false;
		}

		// Store the corresponding wikitext, referenceable by a new key
		$hash = md5( $wikitext );
		$key = wfMemcKey( 'visualeditor', 'serialization', $hash );
		$wgMemc->set( $key, $wikitext,
			$this->veConfig->get( 'VisualEditorSerializationCacheTimeout' ) );

		// Also parse and prepare the edit in case it might be saved later
		$page = WikiPage::factory( $title );
		$content = ContentHandler::makeContent( $wikitext, $title, CONTENT_MODEL_WIKITEXT );

		$status = ApiStashEdit::parseAndStash( $page, $content, $this->getUser(), '' );
		if ( $status === ApiStashEdit::ERROR_NONE ) {
			$logger = LoggerFactory::getInstance( 'StashEdit' );
			$logger->debug( "Cached parser output for VE content key '$key'." );
		}
		MediaWikiServices::getInstance()->getStatsdDataFactory()->increment(
			"editstash.ve_cache_stores.$status"
		);

		return $hash;
	}

	protected function trySerializationCache( $hash ) {
		global $wgMemc;
		$key = wfMemcKey( 'visualeditor', 'serialization', $hash );
		return $wgMemc->get( $key );
	}

	protected function postData( $path, $title, $data, $parserParams, $etag ) {
		$path .= urlencode( $title->getPrefixedDBkey() );
		if ( isset( $parserParams['oldid'] ) && $parserParams['oldid'] ) {
			$path .= '/' . $parserParams['oldid'];
		}
		return $this->requestRestbase(
			'POST', $path, $data,
			[ 'If-Match' => $etag ]
		);
	}

	protected function postHTML( $title, $html, $parserParams, $etag ) {
		return $this->postData(
			'transform/html/to/wikitext/', $title,
			[ 'html' => $html, 'scrub_wikitext' => 1 ], $parserParams, $etag
		);
	}

	protected function diffWikitext( $title, $wikitext, $section = null ) {
		$apiParams = [
			'action' => 'query',
			'prop' => 'revisions',
			'titles' => $title->getPrefixedDBkey(),
			'rvdifftotext' => $this->pstWikitext( $title, $wikitext ),
			'rvsection' => $section
		];

		$api = new ApiMain(
			new DerivativeRequest(
				$this->getRequest(),
				$apiParams,
				/* was posted? */ false
			),
			/* enable write? */ false
		);
		$api->execute();
		$result = $api->getResult()->getResultData( null, [
			/* Transform content nodes to '*' */ 'BC' => [],
			/* Add back-compat subelements */ 'Types' => [],
		] );
		if ( !isset( $result['query']['pages'][$title->getArticleID()]['revisions'][0]['diff']['*'] ) ) {
			return [ 'result' => 'fail' ];
		}
		$diffRows = $result['query']['pages'][$title->getArticleID()]['revisions'][0]['diff']['*'];

		if ( $diffRows !== '' ) {
			$context = new DerivativeContext( $this->getContext() );
			$context->setTitle( $title );
			$engine = new DifferenceEngine( $context );
			return [
				'result' => 'success',
				'diff' => $engine->addHeader(
					$diffRows,
					$context->msg( 'currentrev' )->parse(),
					$context->msg( 'yourtext' )->parse()
				)
			];
		} else {
			return [ 'result' => 'nochanges' ];
		}
	}

	public function execute() {
		$this->serviceClient->mount( '/restbase/', $this->getVRSObject() );

		$user = $this->getUser();
		$params = $this->extractRequestParams();
		$title = Title::newFromText( $params['page'] );
		if ( !$title ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
		}

		$parserParams = [];
		if ( isset( $params['oldid'] ) ) {
			$parserParams['oldid'] = $params['oldid'];
		}

		if ( isset( $params['wikitext'] ) ) {
			$wikitext = $params['wikitext'];
		} else {
			$wikitext = $this->getWikitext( $title, $params, $parserParams );
		}

		if ( $params['paction'] === 'serialize' ) {
			$result = [ 'result' => 'success', 'content' => $wikitext ];
		} elseif ( $params['paction'] === 'serializeforcache' ) {
			$key = $this->storeInSerializationCache(
				$title,
				$wikitext
			);
			$result = [ 'result' => 'success', 'cachekey' => $key ];
		} elseif ( $params['paction'] === 'diff' ) {
			$section = isset( $params['section'] ) ? $params['section'] : null;
			$diff = $this->diffWikitext( $title, $wikitext, $section );
			if ( $diff['result'] === 'fail' ) {
				$this->dieWithError( 'apierror-visualeditor-difffailed', 'difffailed' );
			}
			$result = $diff;
		} elseif ( $params['paction'] === 'save' ) {
			$saveresult = $this->saveWikitext( $title, $wikitext, $params );
			$editStatus = $saveresult['edit']['result'];

			// Error
			if ( $editStatus !== 'Success' ) {
				$result = [
					'result' => 'error',
					'edit' => $saveresult['edit']
				];

				if ( isset( $saveresult['edit']['spamblacklist'] ) ) {
					$matches = explode( '|', $saveresult['edit']['spamblacklist'] );
					$matcheslist = $this->getLanguage()->listToText( $matches );
					$result['edit']['sberrorparsed'] = $this->msg( 'spamprotectiontext' )->parse() . ' ' .
						$this->msg( 'spamprotectionmatch', $matcheslist )->parse();
				}

			// Success
			} else {
				if ( isset( $saveresult['edit']['newrevid'] ) ) {
					$newRevId = intval( $saveresult['edit']['newrevid'] );
					if ( $this->veConfig->get( 'VisualEditorUseChangeTagging' ) ) {
						// Defer till after the RC row is inserted
						// @TODO: doEditContent should let callers specify desired tags
						$tag = $params['wikitext'] ? 'visualeditor-wikitext' : 'visualeditor';
						DeferredUpdates::addCallableUpdate( function () use ( $tag, $newRevId ) {
							ChangeTags::addTags( $tag, null, $newRevId, null );
						} );
					}
				} else {
					$newRevId = $title->getLatestRevId();
				}

				// Return result of parseWikitext instead of saveWikitext so that the
				// frontend can update the page rendering without a refresh.
				$result = $this->parseWikitext( $newRevId );
				if ( $result === false ) {
					$this->dieWithError( 'apierror-visualeditor-docserver', 'docserver' );
				}

				$result['isRedirect'] = (string)$title->isRedirect();

				if ( class_exists( 'FlaggablePageView' ) ) {
					$view = FlaggablePageView::singleton();

					$originalRequest = $view->getContext()->getRequest();
					$originalTitle = RequestContext::getMain()->getTitle();
					// Defeat !$this->isPageView( $request ) || $request->getVal( 'oldid' ) check in setPageContent
					$newRequest = new DerivativeRequest(
						$this->getRequest(),
						[
							'diff' => null,
							'oldid' => '',
							'title' => $title->getPrefixedText(),
							'action' => 'view'
						] + $this->getRequest()->getValues()
					);
					$view->getContext()->setRequest( $newRequest );
					RequestContext::getMain()->setTitle( $title );

					// The two parameters here are references but we don't care
					// about what FlaggedRevs does with them.
					$outputDone = null;
					$useParserCache = null;
					$view->setPageContent( $outputDone, $useParserCache );
					$view->displayTag();
					$view->getContext()->setRequest( $originalRequest );
					RequestContext::getMain()->setTitle( $originalTitle );
				}

				$context = new RequestContext;
				$context->setTitle( $title );
				$tempOut = new OutputPage( $context );
				$tempOut->setArticleFlag( true );

				$subpagestr = $this->getSkin()->subPageSubtitle( $tempOut );
				if ( $subpagestr !== '' ) {
					$subpagestr = '<span class="subpages">' . $subpagestr . '</span>';
				}
				$result['contentSub'] = $subpagestr . $this->getOutput()->getSubtitle();

				$lang = $this->getLanguage();

				if ( isset( $saveresult['edit']['newtimestamp'] ) ) {
					$ts = $saveresult['edit']['newtimestamp'];

					$result['lastModified'] = [
						'date' => $lang->userDate( $ts, $user ),
						'time' => $lang->userTime( $ts, $user )
					];
				}

				if ( isset( $saveresult['edit']['newrevid'] ) ) {
					$result['newrevid'] = intval( $saveresult['edit']['newrevid'] );
				}

				$result['result'] = 'success';
			}
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	public function getAllowedParams() {
		return [
			'paction' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => [
					'serialize',
					'serializeforcache',
					'diff',
					'save',
				],
				ApiBase::PARAM_DFLT => 'save',
			],
			'page' => [
				ApiBase::PARAM_REQUIRED => true,
			],
			'token' => [
				ApiBase::PARAM_REQUIRED => true,
			],
			'wikitext' => null,
			'section' => null,
			'sectiontitle' => null,
			'basetimestamp' => null,
			'starttimestamp' => null,
			'oldid' => null,
			'minor' => null,
			'watch' => null,
			'html' => null,
			'etag' => null,
			'summary' => null,
			'captchaid' => null,
			'captchaword' => null,
			'cachekey' => null,
		];
	}

	public function needsToken() {
		return 'csrf';
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}
}
