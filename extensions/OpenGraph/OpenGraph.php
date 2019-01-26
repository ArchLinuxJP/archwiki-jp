<?php

if ( function_exists( 'wfLoadExtension' ) ) {
    wfLoadExtension( 'OpenGraph' );
    // Keep i18n globals so mergeMessageFileList.php doesn't break
    $wgMessagesDirs['OpenGraph'] = __DIR__ . '/i18n';
    wfWarn(
        'Deprecated PHP entry point used for BoilerPlate extension. Please use wfLoadExtension ' .
        'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
    );
    return true;
} else {
    die( 'This version of the OpenGraph extension requires MediaWiki 1.25+' );
}