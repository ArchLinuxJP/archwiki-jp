<?php

class EchoUserLocator {
	/**
	 * Return all users watching the event title.
	 *
	 * The echo job queue must be enabled to prevent timeouts submitting to
	 * heavily watched pages when this is used.
	 *
	 * @param EchoEvent $event
	 * @param int $batchSize
	 * @return User[]
	 */
	public static function locateUsersWatchingTitle( EchoEvent $event, $batchSize = 500 ) {
		$title = $event->getTitle();
		if ( !$title ) {
			return [];
		}

		$it = new BatchRowIterator(
			wfGetDB( DB_SLAVE, 'watchlist' ),
			/* $table = */ 'watchlist',
			/* $primaryKeys = */ [ 'wl_user' ],
			$batchSize
		);
		$it->addConditions( [
			'wl_namespace' => $title->getNamespace(),
			'wl_title' => $title->getDBkey(),
		] );

		// flatten the result into a stream of rows
		$it = new RecursiveIteratorIterator( $it );

		// add callback to convert user id to user objects
		$it = new EchoCallbackIterator( $it, function ( $row ) {
			return User::newFromId( $row->wl_user );
		} );

		return $it;
	}

	/**
	 * If the event occured on the talk page of a registered
	 * user return that user.
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateTalkPageOwner( EchoEvent $event ) {
		$title = $event->getTitle();
		if ( !$title || $title->getNamespace() !== NS_USER_TALK ) {
			return [];
		}

		$user = User::newFromName( $title->getDBkey() );
		if ( $user && !$user->isAnon() ) {
			return [ $user->getId() => $user ];
		} else {
			return [];
		}
	}

	/**
	 * Return the event agent
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateEventAgent( EchoEvent $event ) {
		$agent = $event->getAgent();
		if ( $agent && !$agent->isAnon() ) {
			return [ $agent->getId() => $agent ];
		} else {
			return [];
		}
	}

	/**
	 * Return the user that created the first revision of the
	 * associated title.
	 *
	 * @param EchoEvent $event
	 * @return User[]
	 */
	public static function locateArticleCreator( EchoEvent $event ) {
		$agent = $event->getAgent();
		$title = $event->getTitle();

		if ( !$title || $title->getArticleID() <= 0 ) {
			return [];
		}
		// why?
		if ( !$agent ) {
			return [];
		}

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->selectRow(
			[ 'revision' ],
			[ 'rev_user' ],
			[ 'rev_page' => $title->getArticleID() ],
			__METHOD__,
			[ 'LIMIT' => 1, 'ORDER BY' => 'rev_timestamp, rev_id' ]
		);
		if ( !$res || !$res->rev_user ) {
			return [];
		}

		$user = User::newFromId( $res->rev_user );
		if ( $user ) {
			return [ $user->getId() => $user ];
		} else {
			return [];
		}
	}

	/**
	 * Fetch user ids from the event extra data.  Requires additional
	 * parameter.  Example $wgEchoNotifications parameter:
	 *
	 *   'user-locator' => array( array( 'event-extra', 'mentions' ) ),
	 *
	 * The above will look in the 'mentions' parameter for a user id or
	 * array of user ids.  It will return all these users as notification
	 * targets.
	 *
	 * @param EchoEvent $event
	 * @param string[] $keys one or more keys to check for user ids
	 * @return User[]
	 */
	public static function locateFromEventExtra( EchoEvent $event, array $keys ) {
		$users = [];
		foreach ( $keys as $key ) {
			$userIds = $event->getExtraParam( $key );
			if ( !$userIds ) {
				continue;
			} elseif ( !is_array( $userIds ) ) {
				$userIds = [ $userIds ];
			}
			foreach ( $userIds as $userId ) {
				// we shouldn't receive User instances, but allow
				// it for backward compatability
				if ( $userId instanceof User ) {
					if ( $userId->isAnon() ) {
						continue;
					}
					$user = $userId;
				} else {
					$user = User::newFromId( $userId );
				}
				$users[$user->getId()] = $user;
			}
		}

		return $users;
	}
}
