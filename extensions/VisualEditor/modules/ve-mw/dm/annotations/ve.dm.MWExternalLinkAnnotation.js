/*!
 * VisualEditor DataModel MWExternalLinkAnnotation class.
 *
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki external link annotation.
 *
 * Example HTML sources:
 *
 *     <a rel="mw:ExtLink">
 *     <a rel="mw:ExtLink/Numbered">
 *
 * Each example is semantically slightly different, but they don't need special treatment (yet).
 *
 * @class
 * @extends ve.dm.LinkAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.MWExternalLinkAnnotation = function VeDmMWExternalLinkAnnotation() {
	// Parent constructor
	ve.dm.MWExternalLinkAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWExternalLinkAnnotation, ve.dm.LinkAnnotation );

/* Static Properties */

ve.dm.MWExternalLinkAnnotation.static.name = 'link/mwExternal';

ve.dm.MWExternalLinkAnnotation.static.matchFunction = function ( domElement ) {
	var type = domElement.getAttribute( 'rel' ) || domElement.getAttribute( 'typeof' ) || domElement.getAttribute( 'property' );
	// Match explicity mw:ExtLink, or plain RDFa-less links with an href (e.g. from external paste)
	return ( !type && domElement.hasAttribute( 'href' ) ) || type === 'mw:ExtLink';
};

ve.dm.MWExternalLinkAnnotation.static.toDataElement = function ( domElements, converter ) {
	var dataElement, annotation,
		rel = domElements[ 0 ].getAttribute( 'rel' );

	// If a plain link is pasted, auto-convert it to the correct type (internal/external)
	if ( !rel ) {
		annotation = ve.ui.MWLinkAction.static.getLinkAnnotation( domElements[ 0 ].getAttribute( 'href' ), converter.getHtmlDocument() );
		return annotation.element;
	}

	// Parent method
	dataElement = ve.dm.MWExternalLinkAnnotation.super.static.toDataElement.apply( this, arguments );

	dataElement.attributes.rel = rel;
	return dataElement;
};

ve.dm.MWExternalLinkAnnotation.static.toDomElements = function ( dataElement ) {
	// Parent method
	var domElements = ve.dm.MWExternalLinkAnnotation.super.static.toDomElements.apply( this, arguments );

	domElements[ 0 ].setAttribute( 'rel', dataElement.attributes.rel || 'mw:ExtLink' );
	return domElements;
};

ve.dm.MWExternalLinkAnnotation.static.describeChange = function ( key, change ) {
	if ( key === 'href' ) {
		return ve.msg( 'visualeditor-changedesc-link-href', change.from, change.to );
	}
	return null;
};

/* Methods */

/**
 * @return {Object}
 */
ve.dm.MWExternalLinkAnnotation.prototype.getComparableObject = function () {
	return {
		type: this.getType(),
		href: this.getAttribute( 'href' ),
		rel: this.getAttribute( 'rel' ) || 'mw:ExtLink'
	};
};

/**
 * @inheritdoc
 */
ve.dm.MWExternalLinkAnnotation.prototype.getComparableHtmlAttributes = function () {
	// Assume that wikitext never adds meaningful html attributes for comparison purposes,
	// although ideally this should be decided by Parsoid (Bug T95028).
	return {};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWExternalLinkAnnotation );
