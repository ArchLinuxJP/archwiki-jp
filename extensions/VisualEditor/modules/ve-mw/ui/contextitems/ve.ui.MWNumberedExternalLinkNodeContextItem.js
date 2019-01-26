/*!
 * VisualEditor MWNumberedExternalLinkNodeContextItem class.
 *
 * @copyright 2011-2018 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Context item for a MWNumberedExternalLinkNode.
 *
 * @class
 * @extends ve.ui.LinkContextItem
 *
 * @constructor
 * @param {ve.ui.Context} context Context item is in
 * @param {ve.dm.Model} model Model item is related to
 * @param {Object} config Configuration options
 */
ve.ui.MWNumberedExternalLinkNodeContextItem = function VeUiMWNumberedExternalLinkNodeContextItem() {
	// Parent constructor
	ve.ui.MWNumberedExternalLinkNodeContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwNumberedExternalLinkNodeContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWNumberedExternalLinkNodeContextItem, ve.ui.LinkContextItem );

/* Static Properties */

ve.ui.MWNumberedExternalLinkNodeContextItem.static.name = 'link/mwNumberedExternal';

ve.ui.MWNumberedExternalLinkNodeContextItem.static.modelClasses = [ ve.dm.MWNumberedExternalLinkNode ];

ve.ui.MWNumberedExternalLinkNodeContextItem.static.clearable = false;

ve.ui.MWNumberedExternalLinkNodeContextItem.static.deletable = true;

/* Methods */

ve.ui.MWNumberedExternalLinkNodeContextItem.prototype.isDeletable = function () {
	// We don't care about whether the context wants to show delete buttons, so override the check.
	return this.constructor.static.deletable;
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWNumberedExternalLinkNodeContextItem );
