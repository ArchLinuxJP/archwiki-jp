/*!
 * VisualEditor DataModel MWCategoryMetaItem class.
 *
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel category meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWCategoryMetaItem = function VeDmMWCategoryMetaItem() {
	// Parent constructor
	ve.dm.MWCategoryMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWCategoryMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWCategoryMetaItem.static.name = 'mwCategory';

ve.dm.MWCategoryMetaItem.static.group = 'mwCategory';

ve.dm.MWCategoryMetaItem.static.matchTagNames = [ 'link' ];

ve.dm.MWCategoryMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/Category' ];

ve.dm.MWCategoryMetaItem.static.toDataElement = function ( domElements ) {
	var href = domElements[ 0 ].getAttribute( 'href' ),
		matches = href.match( /^((?:\.\.?\/)*)(.*?)(?:#(.*))?$/ ),
		rawSortkey = matches[ 3 ] || '';
	return {
		type: this.name,
		attributes: {
			hrefPrefix: matches[ 1 ],
			category: ve.decodeURIComponentIntoArticleTitle( matches[ 2 ] ),
			origCategory: matches[ 2 ],
			sortkey: ve.decodeURIComponentIntoArticleTitle( rawSortkey ),
			origSortkey: rawSortkey
		}
	};
};

ve.dm.MWCategoryMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var href,
		domElement = doc.createElement( 'link' ),
		hrefPrefix = dataElement.attributes.hrefPrefix || '',
		category = dataElement.attributes.category || '',
		sortkey = dataElement.attributes.sortkey || '',
		origCategory = dataElement.attributes.origCategory || '',
		origSortkey = dataElement.attributes.origSortkey || '',
		normalizedOrigCategory = ve.decodeURIComponentIntoArticleTitle( origCategory ),
		normalizedOrigSortkey = ve.decodeURIComponentIntoArticleTitle( origSortkey );
	if ( normalizedOrigSortkey === sortkey ) {
		sortkey = origSortkey;
	} else {
		sortkey = encodeURIComponent( sortkey );
	}
	if ( normalizedOrigCategory === category ) {
		category = origCategory;
	} else {
		category = encodeURIComponent( category );
	}
	domElement.setAttribute( 'rel', 'mw:PageProp/Category' );
	href = hrefPrefix + category;
	if ( sortkey !== '' ) {
		href += '#' + sortkey;
	}
	domElement.setAttribute( 'href', href );
	return [ domElement ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWCategoryMetaItem );
