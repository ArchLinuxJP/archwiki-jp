<?php

$cfg = require __DIR__ . '/../../vendor/mediawiki/mediawiki-phan-config/src/config.php';
// SpecialPage->categoryTreeCategories
$cfg['suppress_issue_types'][] = 'PhanUndeclaredProperty';

return $cfg;
