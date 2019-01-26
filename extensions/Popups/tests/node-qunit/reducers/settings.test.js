import settings from '../../../src/reducers/settings';
import actionTypes from '../../../src/actionTypes';

QUnit.module( 'reducers/settings' );

QUnit.test( '@@INIT', ( assert ) => {
	const state = settings( undefined, { type: '@@INIT' } );

	assert.deepEqual(
		state,
		{
			shouldShow: false,
			showHelp: false,
			shouldShowFooterLink: false
		},
		'The initial state doesn\'t show, doesn\'t show help, and doesn\'t show a footer link.'
	);
} );

QUnit.test( 'BOOT', ( assert ) => {
	const action = {
		type: actionTypes.BOOT,
		isEnabled: false,
		user: {
			isAnon: true
		}
	};

	assert.deepEqual(
		settings( {}, action ),
		{
			shouldShowFooterLink: true
		},
		'The boot state shows a footer link.'
	);

	// ---

	// And when the user is logged out...
	action.user.isAnon = false;

	assert.deepEqual(
		settings( {}, action ),
		{
			shouldShowFooterLink: false
		},
		'If the user is logged in, then it doesn\'t signal that the footer link should be shown.'
	);
} );

QUnit.test( 'SETTINGS_SHOW', ( assert ) => {
	assert.expect( 1, 'All assertions are executed.' );

	assert.deepEqual(
		settings( {}, { type: actionTypes.SETTINGS_SHOW } ),
		{
			shouldShow: true,
			showHelp: false
		},
		'It should mark the settings dialog as ready to be shown, with no help.'
	);
} );

QUnit.test( 'SETTINGS_HIDE', ( assert ) => {
	assert.expect( 1, 'All assertions are executed.' );

	assert.deepEqual(
		settings( {}, { type: actionTypes.SETTINGS_HIDE } ),
		{
			shouldShow: false,
			showHelp: false
		},
		'It should mark the settings dialog as ready to be closed, and hide help.'
	);
} );

QUnit.test( 'SETTINGS_CHANGE', ( assert ) => {
	const action = ( wasEnabled, enabled ) => {
		return {
			type: actionTypes.SETTINGS_CHANGE,
			wasEnabled,
			enabled
		};
	};

	assert.deepEqual(
		settings( {}, action( false, false ) ),
		{ shouldShow: false },
		'It should just hide the settings dialog when enabled state stays the same.'
	);
	assert.deepEqual(
		settings( {}, action( true, true ) ),
		{ shouldShow: false },
		'It should just hide the settings dialog when enabled state stays the same.'
	);

	assert.deepEqual(
		settings( {}, action( false, true ) ),
		{
			shouldShow: false,
			showHelp: false,
			shouldShowFooterLink: false
		},
		'It should hide the settings dialog and help when we enable.'
	);

	assert.deepEqual(
		settings( {}, action( true, false ) ),
		{
			shouldShow: true,
			showHelp: true,
			shouldShowFooterLink: true
		},
		'It should keep the settings showing and show the help when we disable.'
	);

} );
