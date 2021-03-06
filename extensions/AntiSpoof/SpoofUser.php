<?php

use Wikimedia\Rdbms\IDatabase;

class SpoofUser {
	/** @var bool */
	private $legal;

	/** @var string */
	private $name;

	/** @var string */
	private $normalized;

	/** @var null|string */
	private $error;

	/**
	 * @param $name string
	 */
	public function __construct( $name ) {
		$this->name = strval( $name );
		list( $ok, $normalized ) = AntiSpoof::checkUnicodeString( $this->name );
		$this->legal = ( $ok == 'OK' );
		if ( $this->legal ) {
			$this->normalized = $normalized;
			$this->error = null;
		} else {
			$this->normalized = null;
			$this->error = $normalized;
		}
	}

	/**
	 * Does the username pass Unicode legality and script-mixing checks?
	 * @return bool
	 */
	public function isLegal() {
		return $this->legal;
	}

	/**
	 * Describe the error.
	 * @return null|string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Get the normalized key form
	 * @return string|null
	 */
	public function getNormalized() {
		return $this->normalized;
	}

	/**
	 * @return string
	 */
	protected function getTableName() {
		return 'user';
	}

	/**
	 * @return string
	 */
	protected function getUserColumn() {
		return 'user_name';
	}

	/**
	 * Does the username pass Unicode legality and script-mixing checks?
	 *
	 * @return array empty if no conflict, or array containing conflicting usernames
	 */
	public function getConflicts() {
		$dbr = $this->getDBSlave();

		// Join against the user table to ensure that we skip stray
		// entries left after an account is renamed or otherwise munged.
		$spoofedUsers = $dbr->select(
			[ 'spoofuser', $this->getTableName() ],
			[ 'su_name' ], // Same thing due to the join. Saves extra variableness
			[
				'su_normalized' => $this->normalized,
				'su_name = ' . $this->getUserColumn(),
			],
			__METHOD__,
			[
				'LIMIT' => 5
			] );

		$spoofs = [];
		foreach ( $spoofedUsers as $row ) {
			array_push( $spoofs, $row->su_name );
		}
		return $spoofs;
	}

	/**
	 * Record the username's normalized form into the database
	 * for later comparison of future names...
	 * @return bool
	 */
	public function record() {
		return self::batchRecord( $this->getDBMaster(), [ $this ] );
	}

	/**
	 * @return array
	 */
	private function insertFields() {
		return [
			'su_name'       => $this->name,
			'su_normalized' => $this->normalized,
			'su_legal'      => $this->legal ? 1 : 0,
			'su_error'      => $this->error,
		];
	}

	/**
	 * Insert a batch of spoof normalization records into the database.
	 * @param IDatabase $dbw
	 * @param SpoofUser[] $items
	 * @return bool
	 */
	public static function batchRecord( IDatabase $dbw, $items ) {
		if ( !count( $items ) ) {
			return false;
		}
		$fields = [];
		/**
		 * @var $item SpoofUser
		 */
		foreach ( $items as $item ) {
			$fields[] = $item->insertFields();
		}
		$dbw->replace(
			'spoofuser',
			[ 'su_name' ],
			$fields,
			__METHOD__ );
		return true;
	}

	/**
	 * @param string $oldName
	 */
	public function update( $oldName ) {
		$that = $this;
		$method = __METHOD__;
		$dbw = $this->getDBMaster();
		// Avoid user rename triggered deadlocks
		$dbw->onTransactionPreCommitOrIdle(
			function () use ( $dbw, $that, $method, $oldName ) {
				if ( $that->record() ) {
					$dbw->delete(
						'spoofuser',
						[ 'su_name' => $oldName ],
						$method
					);
				}
			}
		);
	}

	/**
	 * Remove a user from the spoofuser table
	 */
	public function remove() {
		$this->getDBMaster()->delete(
			'spoofuser',
			[ 'su_name' => $this->name ],
			__METHOD__
		);
	}

	/**
	 * @return IDatabase
	 */
	protected function getDBSlave() {
		return wfGetDB( DB_REPLICA );
	}

	/**
	 * @return IDatabase
	 */
	protected function getDBMaster() {
		return wfGetDB( DB_MASTER );
	}
}
