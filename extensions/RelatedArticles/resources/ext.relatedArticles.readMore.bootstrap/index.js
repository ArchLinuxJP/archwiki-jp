( function () {

	var relatedPages = new mw.relatedPages.RelatedPagesGateway(
			new mw.Api(),
			mw.config.get( 'wgPageName' ),
			mw.config.get( 'wgRelatedArticles' ),
			mw.config.get( 'wgRelatedArticlesUseCirrusSearch' ),
			mw.config.get( 'wgRelatedArticlesOnlyUseCirrusSearch' )
		),
		// Make sure this is never undefined as I'm paranoid
		LIMIT = mw.config.get( 'wgRelatedArticlesCardLimit', 3 ),
		debouncedLoad = $.debounce( 100, function () {
			loadRelatedArticles(); // eslint-disable-line no-use-before-define
		} ),
		$window = $( window );

	/**
	 * Load related articles when the user scrolls past half of the window height.
	 *
	 * @ignore
	 */
	function loadRelatedArticles() {
		var readMore = $( '.read-more-container' ).get( 0 ),
			scrollThreshold = $window.height() * 2;

		if ( mw.viewport.isElementCloseToViewport( readMore, scrollThreshold ) ) {
			$.when(
				// Note we load dependencies here rather than ResourceLoader
				// to avoid PHP exceptions when Cards not installed
				// which should never happen given the if statement.
				mw.loader.using( [
					'ext.relatedArticles.cards',
					'ext.relatedArticles.readMore'
				] ),
				relatedPages.getForCurrentPage( LIMIT )
			).then( function ( _, pages ) {
				if ( pages.length ) {
					mw.track( 'ext.relatedArticles.init', pages );
				} else {
					$( readMore ).remove();
				}
			} );
			// detach handler to stop subsequent loads on scroll
			$window.off( 'scroll', debouncedLoad );
		}
	}

	function showReadMore() {
		// Add container to DOM for checking distance on scroll
		// If a skin has marked up a footer content area prepend it there
		if ( $( '.footer-content' ).length ) {
			$( '<div class="read-more-container" />' ).prependTo( '.footer-content' );
		} else {
			$( '<div class="read-more-container post-content" />' )
				.insertAfter( '#content' );
		}

		// try related articles load on scroll
		$window.on( 'scroll', debouncedLoad );
		// try an initial load, in case of no scroll
		loadRelatedArticles();
	}

	$( showReadMore );
}() );
