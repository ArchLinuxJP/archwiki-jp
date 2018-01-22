/*!
 * VisualEditor user interface MWSettingsPage class.
 *
 * @copyright 2011-2017 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog settings page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @param {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWSettingsPage = function VeUiMWSettingsPage( name, config ) {
	var settingsPage = this;

	// Parent constructor
	ve.ui.MWSettingsPage.super.apply( this, arguments );

	// Properties
	this.metaList = null;
	this.tocOptionTouched = false;
	this.redirectOptionsTouched = false;
	this.tableOfContentsTouched = false;
	this.label = ve.msg( 'visualeditor-dialog-meta-settings-section' );

	this.settingsFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-settings-label' ),
		icon: 'settings'
	} );

	// Initialization

	// Table of Contents items
	this.tableOfContents = new OO.ui.FieldLayout(
		new OO.ui.ButtonSelectWidget( {
			classes: [ 've-test-page-settings-table-of-contents' ]
		} )
			.addItems( [
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/forcetoc',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-force' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'default',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-default' )
				} ),
				new OO.ui.ButtonOptionWidget( {
					data: 'mw:PageProp/notoc',
					label: ve.msg( 'visualeditor-dialog-meta-settings-toc-disable' )
				} )
			] )
			.connect( this, { select: 'onTableOfContentsFieldChange' } ),
		{
			$overlay: config.$overlay,
			align: 'top',
			label: ve.msg( 'visualeditor-dialog-meta-settings-toc-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-toc-help' )
		}
	);

	// Redirect items
	this.enableRedirectInput = new OO.ui.CheckboxInputWidget();
	this.enableRedirectField = new OO.ui.FieldLayout(
		this.enableRedirectInput,
		{
			$overlay: config.$overlay,
			classes: [ 've-test-page-settings-enable-redirect' ],
			align: 'inline',
			label: ve.msg( 'visualeditor-dialog-meta-settings-redirect-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-redirect-help' )
		}
	);
	this.redirectTargetInput = new mw.widgets.TitleInputWidget( {
		placeholder: ve.msg( 'visualeditor-dialog-meta-settings-redirect-placeholder' ),
		$overlay: config.$overlay
	} );
	this.redirectTargetField = new OO.ui.FieldLayout(
		this.redirectTargetInput,
		{ align: 'top' }
	);
	this.enableStaticRedirectInput = new OO.ui.CheckboxInputWidget();
	this.enableStaticRedirectField = new OO.ui.FieldLayout(
		this.enableStaticRedirectInput,
		{
			$overlay: config.$overlay,
			classes: [ 've-test-page-settings-prevent-redirect' ],
			align: 'inline',
			label: ve.msg( 'visualeditor-dialog-meta-settings-redirect-staticlabel' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-redirect-statichelp' )
		}
	);
	this.enableRedirectInput.connect( this, { change: 'onEnableRedirectChange' } );
	this.redirectTargetInput.connect( this, { change: 'onRedirectTargetChange' } );
	this.enableStaticRedirectInput.connect( this, { change: 'onEnableStaticRedirectChange' } );

	this.metaItemCheckboxes = [
		{
			metaName: 'mwNoEditSection',
			label: ve.msg( 'visualeditor-dialog-meta-settings-noeditsection-label' ),
			help: ve.msg( 'visualeditor-dialog-meta-settings-noeditsection-help' ),
			classes: [ 've-test-page-settings-noeditsection' ]
		}
	].concat( ve.ui.MWSettingsPage.static.extraMetaCheckboxes );

	if ( mw.config.get( 'wgNamespaceNumber' ) === mw.config.get( 'wgNamespaceIds' ).category ) {
		this.metaItemCheckboxes.push(
			{
				metaName: 'mwHiddenCategory',
				label: ve.msg( 'visualeditor-dialog-meta-settings-hiddencat-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-hiddencat-help' )
			},
			{
				metaName: 'mwNoGallery',
				label: ve.msg( 'visualeditor-dialog-meta-settings-nogallery-label' ),
				help: ve.msg( 'visualeditor-dialog-meta-settings-nogallery-help' )
			}
		);
	}

	this.settingsFieldset.addItems( [
		this.enableRedirectField,
		this.redirectTargetField,
		this.enableStaticRedirectField,
		this.tableOfContents
	] );

	$.each( this.metaItemCheckboxes, function () {
		this.fieldLayout = new OO.ui.FieldLayout(
			new OO.ui.CheckboxInputWidget(),
			{
				$overlay: config.$overlay,
				classes: this.classes,
				align: 'inline',
				label: this.label,
				help: this.help || ''
			}
		);
		settingsPage.settingsFieldset.addItems( [ this.fieldLayout ] );
	} );

	this.$element.append( this.settingsFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSettingsPage, OO.ui.PageLayout );

/* Allow extra meta item checkboxes to be added by extensions etc. */
ve.ui.MWSettingsPage.static.extraMetaCheckboxes = [];

/**
 * Add a checkbox to the list of changeable page settings
 *
 * @param {string} metaName The name of the DM meta item
 * @param {string} label The label to show next to the checkbox
 */
ve.ui.MWSettingsPage.static.addMetaCheckbox = function ( metaName, label ) {
	this.extraMetaCheckboxes.push( { metaName: metaName, label: label } );
};

/* Methods */

/* Table of Contents methods */

/**
 * @inheritdoc
 */
ve.ui.MWSettingsPage.prototype.setOutlineItem = function () {
	// Parent method
	ve.ui.MWSettingsPage.super.prototype.setOutlineItem.apply( this, arguments );

	if ( this.outlineItem ) {
		this.outlineItem
			.setIcon( 'settings' )
			.setLabel( ve.msg( 'visualeditor-dialog-meta-settings-section' ) );
	}
};

/**
 * Handle Table Of Contents display change events.
 *
 * @method
 */
ve.ui.MWSettingsPage.prototype.onTableOfContentsFieldChange = function () {
	this.tableOfContentsTouched = true;
};

/* Redirect methods */

/**
 * Handle redirect state change events.
 *
 * @param {boolean} value Whether a redirect is to be set for this page
 */
ve.ui.MWSettingsPage.prototype.onEnableRedirectChange = function ( value ) {
	var page = this;
	this.redirectTargetInput.setDisabled( !value );
	this.enableStaticRedirectInput.setDisabled( !value );
	if ( value ) {
		/*
		 * HACK: When editing a page which has a redirect, the meta dialog
		 * automatically opens with the settings page's redirect field focused.
		 * When this happens, we don't want the lookup dropdown to appear until
		 * the user actually does something.
		 * Using setTimeout because we need to defer this until after the
		 * dialog has opened - otherwise its internal lookupDisabled logic will
		 * fail to have any effect during the actual focusing and calling of
		 * OO.ui.LookupElement#onLookupInputFocus/OO.ui.LookupElement#populateLookupMenu.
		 * https://phabricator.wikimedia.org/T137309
		 */
		setTimeout( function () {
			page.redirectTargetInput.focus();
		} );
	} else {
		this.redirectTargetInput.setValue( '' );
		this.enableStaticRedirectInput.setSelected( false );
	}
	this.redirectOptionsTouched = true;
};

/**
 * Handle redirect target change events.
 */
ve.ui.MWSettingsPage.prototype.onRedirectTargetChange = function () {
	this.redirectOptionsTouched = true;
};

/**
 * Handle static redirect state change events.
 */
ve.ui.MWSettingsPage.prototype.onEnableStaticRedirectChange = function () {
	this.redirectOptionsTouched = true;
};

/**
 * Get the first meta item of a given name
 *
 * @param {string} name Name of the meta item
 * @return {Object|null} Meta item, if any
 */
ve.ui.MWSettingsPage.prototype.getMetaItem = function ( name ) {
	return this.metaList.getItemsInGroup( name )[ 0 ] || null;
};

/**
 * Setup settings page.
 *
 * @param {ve.dm.MetaList} metaList Meta list
 * @param {Object} [data] Dialog setup data
 */
ve.ui.MWSettingsPage.prototype.setup = function ( metaList ) {
	var tableOfContentsMetaItem, tableOfContentsField, tableOfContentsMode,
		redirectTargetItem, redirectTarget, redirectStatic,
		settingsPage = this;

	this.metaList = metaList;

	// Table of Contents items
	tableOfContentsField = this.tableOfContents.getField();
	tableOfContentsMetaItem = this.getMetaItem( 'mwTOC' );
	tableOfContentsMode = tableOfContentsMetaItem && tableOfContentsMetaItem.getAttribute( 'property' ) || 'default';
	tableOfContentsField.selectItemByData( tableOfContentsMode );
	this.tableOfContentsTouched = false;

	// Redirect items (disabled states set by change event)
	redirectTargetItem = this.getMetaItem( 'mwRedirect' );
	redirectTarget = redirectTargetItem && redirectTargetItem.getAttribute( 'title' ) || '';
	redirectStatic = this.getMetaItem( 'mwStaticRedirect' );
	this.enableRedirectInput.setSelected( !!redirectTargetItem );
	this.redirectTargetInput.setValue( redirectTarget );
	this.redirectTargetInput.setDisabled( !redirectTargetItem );
	this.enableStaticRedirectInput.setSelected( !!redirectStatic );
	this.enableStaticRedirectInput.setDisabled( !redirectTargetItem );
	this.redirectOptionsTouched = false;

	// Simple checkbox items
	$.each( this.metaItemCheckboxes, function () {
		var isSelected = !!settingsPage.getMetaItem( this.metaName );
		this.fieldLayout.getField().setSelected( isSelected );
	} );
};

/**
 * Tear down settings page.
 *
 * @param {Object} [data] Dialog tear down data
 */
ve.ui.MWSettingsPage.prototype.teardown = function ( data ) {
	var currentTableOfContents, newTableOfContentsData, newTableOfContentsItem,
		currentRedirectTargetItem, newRedirectData, newRedirectItemData,
		currentStaticRedirectItem, newStaticRedirectState,
		settingsPage = this;

	// Data initialisation
	data = data || {};
	if ( data.action !== 'apply' ) {
		return;
	}

	// Table of Contents items
	currentTableOfContents = this.getMetaItem( 'mwTOC' );
	newTableOfContentsData = this.tableOfContents.getField().getSelectedItem();

	// Redirect items
	currentRedirectTargetItem = this.getMetaItem( 'mwRedirect' );
	newRedirectData = this.redirectTargetInput.getValue();
	newRedirectItemData = { type: 'mwRedirect', attributes: { title: newRedirectData } };

	currentStaticRedirectItem = this.getMetaItem( 'mwStaticRedirect' );
	newStaticRedirectState = this.enableStaticRedirectInput.isSelected();

	// Alter the TOC option flag iff it's been touched & is actually different
	if ( this.tableOfContentsTouched ) {
		if ( newTableOfContentsData.data === 'default' ) {
			if ( currentTableOfContents ) {
				currentTableOfContents.remove();
			}
		} else {
			newTableOfContentsItem = { type: 'mwTOC', attributes: { property: newTableOfContentsData.data } };

			if ( !currentTableOfContents ) {
				this.metaList.insertMeta( newTableOfContentsItem );
			} else if ( currentTableOfContents.getAttribute( 'property' ) !== newTableOfContentsData.data ) {
				currentTableOfContents.replaceWith(
					ve.extendObject( true, {}, currentTableOfContents.getElement(), newTableOfContentsItem )
				);
			}
		}
	}

	// Alter the redirect options iff they've been touched & are different
	if ( this.redirectOptionsTouched ) {
		if ( currentRedirectTargetItem ) {
			if ( newRedirectData ) {
				if ( currentRedirectTargetItem.getAttribute( 'title' ) !== newRedirectData ) {
					// There was a redirect and is a new one, but they differ, so replace
					currentRedirectTargetItem.replaceWith(
						ve.extendObject( true, {},
							currentRedirectTargetItem.getElement(),
							newRedirectItemData
						)
					);
				}
			} else {
				// There was a redirect and is no new one, so remove
				currentRedirectTargetItem.remove();
			}
		} else {
			if ( newRedirectData ) {
				// There's no existing redirect but there is a new one, so create
				// HACK: Putting this at index 0, offset 0 so that it works – bug 61862
				this.metaList.insertMeta( newRedirectItemData, 0, 0 );
			}
		}

		if ( currentStaticRedirectItem && ( !newStaticRedirectState || !newRedirectData ) ) {
			currentStaticRedirectItem.remove();
		}
		if ( !currentStaticRedirectItem && newStaticRedirectState && newRedirectData ) {
			this.metaList.insertMeta( { type: 'mwStaticRedirect' } );
		}
	}

	$.each( this.metaItemCheckboxes, function () {
		var currentItem = settingsPage.getMetaItem( this.metaName ),
			isSelected = this.fieldLayout.getField().isSelected();

		if ( currentItem && !isSelected ) {
			currentItem.remove();
		} else if ( !currentItem && isSelected ) {
			settingsPage.metaList.insertMeta( { type: this.metaName } );
		}
	} );

	this.metaList = null;
};
