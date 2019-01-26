/*!
 * VisualEditor UserInterface AnnotationInspector class.
 *
 * @copyright 2011-2018 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Inspector for working with content annotations.
 *
 * @class
 * @abstract
 * @extends ve.ui.FragmentInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.AnnotationInspector = function VeUiAnnotationInspector() {
	// Parent constructor
	ve.ui.AnnotationInspector.super.apply( this, arguments );

	// Properties
	this.initialSelection = null;
	this.initialAnnotation = null;
	this.initialAnnotationIsCovering = false;
};

/* Inheritance */

OO.inheritClass( ve.ui.AnnotationInspector, ve.ui.FragmentInspector );

/**
 * Annotation models this inspector can edit.
 *
 * @static
 * @inheritable
 * @property {Function[]}
 */
ve.ui.AnnotationInspector.static.modelClasses = [];

// Override the parent action array to only have a 'cancel' button
// on insert, since the annotation inspectors immediately apply the
// action and 'cancel' is meaningless. Instead, they use 'done' to
// perform the same dismissal after applying action that clicking away
// from the inspector performs.
ve.ui.AnnotationInspector.static.actions = [
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'edit'
	},
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'back' ],
		modes: [ 'edit', 'insert' ]
	},
	{
		action: 'done',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-insert' ),
		flags: [ 'progressive', 'primary' ],
		modes: 'insert'
	}
];

/* Methods */

/**
 * Check if form is empty, which if saved should result in removing the annotation.
 *
 * Only override this if the form provides the user a way to blank out primary information, allowing
 * them to remove the annotation by clearing the form.
 *
 * @return {boolean} Form is empty
 */
ve.ui.AnnotationInspector.prototype.shouldRemoveAnnotation = function () {
	return false;
};

/**
 * Get data to insert if nothing was selected when the inspector opened.
 *
 * Defaults to using #getInsertionText.
 *
 * @return {Array} Linear model content to insert
 */
ve.ui.AnnotationInspector.prototype.getInsertionData = function () {
	return this.getInsertionText().split( '' );
};

/**
 * Get text to insert if nothing was selected when the inspector opened.
 *
 * @return {string} Text to insert
 */
ve.ui.AnnotationInspector.prototype.getInsertionText = function () {
	if ( this.sourceMode ) {
		return OO.ui.resolveMsg( this.constructor.static.title );
	}
	return '';
};

/**
 * Get the annotation object to apply.
 *
 * This method is called when the inspector is closing, and should return the annotation to apply
 * to the text. If this method returns a falsey value like null, no annotation will be applied,
 * but existing annotations won't be removed either.
 *
 * @abstract
 * @method
 * @return {ve.dm.Annotation} Annotation to apply
 */
ve.ui.AnnotationInspector.prototype.getAnnotation = null;

/**
 * Get an annotation object from a fragment.
 *
 * @abstract
 * @method
 * @param {ve.dm.SurfaceFragment} fragment Surface fragment
 * @return {ve.dm.Annotation|null} Annotation
 */
ve.ui.AnnotationInspector.prototype.getAnnotationFromFragment = null;

/**
 * Get matching annotations within a fragment.
 *
 * @method
 * @param {ve.dm.SurfaceFragment} fragment Fragment to get matching annotations within
 * @param {boolean} [all] Get annotations which only cover some of the fragment
 * @return {ve.dm.AnnotationSet} Matching annotations
 */
ve.ui.AnnotationInspector.prototype.getMatchingAnnotations = function ( fragment, all ) {
	var modelClasses = this.constructor.static.modelClasses;

	return fragment.getAnnotations( all ).filter( function ( annotation ) {
		return ve.isInstanceOfAny( annotation, modelClasses );
	} );
};

/**
 * @inheritdoc
 */
ve.ui.AnnotationInspector.prototype.getMode = function () {
	if ( this.initialSelection ) {
		return this.initialSelection.isCollapsed() ? 'insert' : 'edit';
	}
	return '';
};

/**
 * Handle the inspector being setup.
 *
 * There are 4 scenarios:
 *
 * - Zero-length selection not near a word -> no change, text will be inserted on close
 * - Zero-length selection inside or adjacent to a word -> expand selection to cover word
 * - Selection covering non-annotated text -> trim selection to remove leading/trailing whitespace
 * - Selection covering annotated text -> expand selection to cover annotation
 *
 * @method
 * @param {Object} [data] Inspector opening data
 * @param {boolean} [data.noExpand] Don't expand the selection when opening
 * @return {OO.ui.Process}
 */
ve.ui.AnnotationInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.AnnotationInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var initialCoveringAnnotation,
				inspector = this,
				annotationSet, annotations,
				fragment = this.getFragment(),
				surfaceModel = fragment.getSurface(),
				annotation = this.getMatchingAnnotations( fragment, true ).get( 0 );

			surfaceModel.pushStaging();

			// Initialize range
			if ( this.previousSelection instanceof ve.dm.LinearSelection && !annotation ) {
				if (
					fragment.getSelection().isCollapsed() &&
					fragment.getDocument().data.isContentOffset( fragment.getSelection().getRange().start )
				) {
					// Expand to nearest word
					if ( !data.noExpand ) {
						fragment = fragment.expandLinearSelection( 'word' );
					}

					// TODO: We should review how getMatchingAnnotation works in light of the fact
					// that in the case of a collapsed range, the method falls back to retrieving
					// insertion annotations.

					// Check if we're inside a relevant annotation and if so, define it
					annotationSet = fragment.document.data.getAnnotationsFromRange( fragment.selection.range );
					annotations = annotationSet.filter( function ( existingAnnotation ) {
						return ve.isInstanceOfAny( existingAnnotation, inspector.constructor.static.modelClasses );
					} );
					if ( annotations.getLength() > 0 ) {
						// We're in the middle of an annotation, let's make sure we expand
						// our selection to include the entire existing annotation
						annotation = annotations.get( 0 );
					}
				} else {
					// Trim whitespace
					fragment = fragment.trimLinearSelection();
				}

				if ( !fragment.getSelection().isCollapsed() && !annotation ) {
					// Create annotation from selection
					annotation = this.getAnnotationFromFragment( fragment );
					if ( annotation ) {
						fragment.annotateContent( 'set', annotation );
					}
				}
			}
			if ( annotation && !data.noExpand ) {
				// Expand range to cover annotation
				fragment = fragment.expandLinearSelection( 'annotation', annotation );
			}

			// Update selection
			fragment.select();
			this.initialSelection = fragment.getSelection();

			// The initial annotation is the first matching annotation in the fragment
			this.initialAnnotation = this.getMatchingAnnotations( fragment, true ).get( 0 );
			initialCoveringAnnotation = this.getMatchingAnnotations( fragment ).get( 0 );
			// Fallback to a default annotation
			if ( !this.initialAnnotation ) {
				this.initialAnnotation = this.getAnnotationFromFragment( fragment );
			} else if (
				initialCoveringAnnotation &&
				initialCoveringAnnotation.compareTo( this.initialAnnotation )
			) {
				// If the initial annotation doesn't cover the fragment, record this as we'll need
				// to forcefully apply it to the rest of the fragment later
				this.initialAnnotationIsCovering = true;
			}

			this.fragment = fragment;

			// Set the mode - this was done already in FragmentInspector but now that we may have
			// changed what the fragment is covering we need to run it again
			this.actions.setMode( this.getMode() );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.AnnotationInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	return ve.ui.AnnotationInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			var i, len, annotations, insertion,
				insertionAnnotation = false,
				insertText = false,
				replace = false,
				annotation = this.getAnnotation(),
				remove = data.action === 'done' && this.shouldRemoveAnnotation(),
				surfaceModel = this.fragment.getSurface(),
				fragment = surfaceModel.getFragment( this.initialSelection, false ),
				selection = this.fragment.getSelection();

			if (
				!( selection instanceof ve.dm.LinearSelection ) ||
				( remove && selection.getRange().isCollapsed() )
			) {
				// Since we pushStaging on SetupProcess we need to make sure
				// all terminations pop
				surfaceModel.popStaging();
				return;
			}

			if ( !remove ) {
				if ( data.action !== 'done' ) {
					surfaceModel.popStaging();
					if ( this.previousSelection ) {
						surfaceModel.setSelection( this.previousSelection );
					}
					return;
				}
				if ( this.initialSelection.isCollapsed() ) {
					insertText = true;
				}
				if ( annotation ) {
					// Check if the initial annotation has changed, or didn't cover the whole fragment
					// to begin with
					if (
						!this.initialAnnotationIsCovering ||
						!this.initialAnnotation ||
						!this.initialAnnotation.compareTo( annotation )
					) {
						replace = true;
					}
				}
			}
			// If we are setting a new annotation, clear any annotations the inspector may have
			// applied up to this point. Otherwise keep them.
			if ( replace ) {
				surfaceModel.popStaging();
			} else {
				surfaceModel.applyStaging();
			}
			if ( insertText ) {
				insertion = this.getInsertionData();
				if ( insertion.length ) {
					fragment.insertContent( insertion, true );
					// Move cursor to the end of the inserted content, even if back button is used
					fragment.adjustLinearSelection( -insertion.length, 0 );
					this.previousSelection = new ve.dm.LinearSelection( fragment.getDocument(), new ve.Range(
						this.initialSelection.getRange().start + insertion.length
					) );
				}
			}
			if ( remove || replace ) {
				// Clear all existing annotations
				annotations = this.getMatchingAnnotations( fragment, true ).get();
				for ( i = 0, len = annotations.length; i < len; i++ ) {
					fragment.annotateContent( 'clear', annotations[ i ] );
				}
			}
			if ( replace ) {
				// Apply new annotation
				if ( fragment.getSelection().isCollapsed() ) {
					insertionAnnotation = true;
				} else {
					fragment.annotateContent( 'set', annotation );
				}
			}
			// HACK: ui.WindowAction unsets previousSelection in source mode,
			// so we can't rely on it existing.
			if ( this.previousSelection && ( !data.action || insertText ) ) {
				// Restore selection to what it was before we expanded it
				selection = this.previousSelection;
			}
			if ( data.action ) {
				surfaceModel.setSelection( selection );
			}

			if ( insertionAnnotation ) {
				surfaceModel.addInsertionAnnotations( annotation );
			}
		}, this )
		.next( function () {
			// Reset state
			this.initialSelection = null;
			this.initialAnnotation = null;
			this.initialAnnotationIsCovering = false;
		}, this );
};
