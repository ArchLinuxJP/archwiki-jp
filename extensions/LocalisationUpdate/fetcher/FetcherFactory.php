<?php
/**
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0+
 */

namespace LocalisationUpdate;

/**
 * Constructs fetchers based on the repository urls.
 */
class FetcherFactory {
	public function getFetcher( $path ) {
		if ( strpos( $path, 'https://raw.github.com/' ) === 0 ) {
			return new GitHubFetcher();
		} elseif ( strpos( $path, 'http://' ) === 0 ) {
			return new HttpFetcher();
		} elseif ( strpos( $path, 'https://' ) === 0 ) {
			return new HttpFetcher();
		} else {
			return new FileSystemFetcher();
		}
	}
}
