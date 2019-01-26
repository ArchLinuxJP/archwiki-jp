<?php
/*
 * This file is part of the MediaWiki extension Popups.
 *
 * Popups is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * Popups is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Popups.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @file
 * @ingroup extensions
 */
namespace Popups;

use MediaWiki\MediaWikiServices;
use User;
use OutputPage;
use Skin;

/**
 * Hooks definitions for Popups extension
 *
 * @package Popups
 */
class PopupsHooks {
	const PREVIEWS_PREFERENCES_SECTION = 'rendering/reading';

	/**
	 * Add Page Previews options to user Preferences page
	 *
	 * @param User $user User whose preferences are being modified
	 * @param array &$prefs Preferences description array, to be fed to a HTMLForm object
	 */
	public static function onGetPreferences( User $user, array &$prefs ) {
		$context = MediaWikiServices::getInstance()->getService( 'Popups.Context' );

		if ( !$context->showPreviewsOptInOnPreferencesPage() ) {
			return;
		}
		$option = [
			'type' => 'radio',
			'label-message' => 'popups-prefs-optin-title',
			'help-message' => 'popups-prefs-conflicting-gadgets-info',
			'options' => [
				wfMessage( 'popups-prefs-optin-enabled-label' )->escaped()
				=> PopupsContext::PREVIEWS_ENABLED,
				wfMessage( 'popups-prefs-optin-disabled-label' )->escaped()
				=> PopupsContext::PREVIEWS_DISABLED
			],
			'section' => self::PREVIEWS_PREFERENCES_SECTION
		];
		if ( $context->conflictsWithNavPopupsGadget( $user ) ) {
			$option[ 'disabled' ] = true;
			$option[ 'help-message' ] = [ 'popups-prefs-disable-nav-gadgets-info',
				'Special:Preferences#mw-prefsection-gadgets' ];
		}

		$skinPosition = array_search( 'skin', array_keys( $prefs ) );

		if ( $skinPosition !== false ) {
			$injectIntoIndex = $skinPosition + 1;
			$prefs = array_slice( $prefs, 0, $injectIntoIndex, true )
				+ [ PopupsContext::PREVIEWS_OPTIN_PREFERENCE_NAME => $option ]
				+ array_slice( $prefs, $injectIntoIndex, count( $prefs ) - 1, true );
		} else {
			$prefs[ PopupsContext::PREVIEWS_OPTIN_PREFERENCE_NAME ] = $option;
		}
	}

	/**
	 * Allows last minute changes to the output page, e.g. adding of CSS or JavaScript by extensions.
	 *
	 * @param OutputPage &$out The Output page object
	 * @param Skin &$skin &Skin object that will be used to generate the page
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$context = MediaWikiServices::getInstance()->getService( 'Popups.Context' );
		if ( $context->isTitleBlacklisted( $out->getTitle() ) ) {
			return;
		}

		if ( !$context->areDependenciesMet() ) {
			$logger = $context->getLogger();
			$logger->error( 'Popups requires the PageImages extensions.
				TextExtracts extension is required when using mwApiPlain gateway.' );
			return;
		}

		$user = $out->getUser();
		if ( $context->shouldSendModuleToUser( $user ) ) {
			$out->addModules( [ 'ext.popups' ] );
		}
	}

	/**
	 * @param array &$vars Array of variables to be added into the output of the startup module
	 */
	public static function onResourceLoaderGetConfigVars( array &$vars ) {
		$conf = MediaWikiServices::getInstance()->getService( 'Popups.Config' );
		$vars['wgPopupsVirtualPageViews'] = $conf->get( 'PopupsVirtualPageViews' );
		$vars['wgPopupsGateway'] = $conf->get( 'PopupsGateway' );
		$vars['wgPopupsEventLogging'] = $conf->get( 'PopupsEventLogging' );
		$vars['wgPopupsRestGatewayEndpoint'] = $conf->get( 'PopupsRestGatewayEndpoint' );
		$vars['wgPopupsStatsvSamplingRate'] = $conf->get( 'PopupsStatsvSamplingRate' );
	}

	/**
	 * MakeGlobalVariablesScript hook handler.
	 *
	 * Variables added:
	 * * `wgPopupsShouldSendModuleToUser' - The server's notion of whether or not the
	 *   user has enabled Page Previews (see `\Popups\PopupsContext#shouldSendModuleToUser`).
	 * * `wgPopupsConflictsWithNavPopupGadget' - The server's notion of whether or not the
	 *   user has enabled conflicting Navigational Popups Gadget.
	 *
	 * @param array &$vars variables to be added into the output of OutputPage::headElement
	 * @param OutputPage $out OutputPage instance calling the hook
	 */
	public static function onMakeGlobalVariablesScript( array &$vars, OutputPage $out ) {
		$context = MediaWikiServices::getInstance()->getService( 'Popups.Context' );
		$user = $out->getUser();

		$vars['wgPopupsShouldSendModuleToUser'] = $context->shouldSendModuleToUser( $user );
		$vars['wgPopupsConflictsWithNavPopupGadget'] = $context->conflictsWithNavPopupsGadget(
			$user );
	}

	/**
	 * Register default preferences for popups
	 *
	 * @param array &$wgDefaultUserOptions Reference to default options array
	 */
	public static function onUserGetDefaultOptions( &$wgDefaultUserOptions ) {
		$config = MediaWikiServices::getInstance()->getService( 'Popups.Config' );

		$wgDefaultUserOptions[ PopupsContext::PREVIEWS_OPTIN_PREFERENCE_NAME ] =
			$config->get( 'PopupsOptInDefaultState' );
	}

	/**
	 * Change the default PagePreviews visibility state for newly created accounts
	 *
	 * @param User $user Newly created user object
	 * @param bool $autocreated Is user autocreated
	 */
	public static function onLocalUserCreated( User $user, $autocreated ) {
		// ignore the $autocreated flag, we always want to set PagePreviews visibility
		$config = MediaWikiServices::getInstance()->getService( 'Popups.Config' );

		$user->setOption( PopupsContext::PREVIEWS_OPTIN_PREFERENCE_NAME,
			$config->get( 'PopupsOptInStateForNewAccounts' ) );
	}
}
