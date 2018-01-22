/*!
 * VisualEditor ContentEditable BlockquoteNode class.
 *
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable Blockquote node.
 *
 * @class
 * @extends ve.ce.ContentBranchNode
 * @constructor
 * @param {ve.dm.BlockquoteNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.BlockquoteNode = function VeCeBlockquoteNode() {
	// Parent constructor
	ve.ce.BlockquoteNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.BlockquoteNode, ve.ce.ContentBranchNode );

/* Static Properties */

ve.ce.BlockquoteNode.static.name = 'blockquote';

ve.ce.BlockquoteNode.static.tagName = 'blockquote';

/* Registration */

ve.ce.nodeFactory.register( ve.ce.BlockquoteNode );
