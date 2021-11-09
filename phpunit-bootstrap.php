<?php

namespace VariableAnalysis\Tests;

$ds = DIRECTORY_SEPARATOR;

if (!defined('PHP_CODESNIFFER_IN_TESTS')) {
    define('PHP_CODESNIFFER_IN_TESTS', true);
}

// The below two defines are needed for PHPCS 3.x.
if (!defined('PHP_CODESNIFFER_CBF')) {
    define('PHP_CODESNIFFER_CBF', false);
}

if (!defined('PHP_CODESNIFFER_VERBOSITY')) {
    define('PHP_CODESNIFFER_VERBOSITY', 0);
}

// Get the PHP_CodeSniffer dir from an environment variable.
$phpcsDir = getenv('PHPCS_DIR');

if (is_dir(__DIR__ . $ds . 'vendor')) {
    $vendorDir = __DIR__ . $ds . 'vendor';
}

if ($phpcsDir === false && is_dir($vendorDir . $ds . 'squizlabs' . $ds . 'php_codesniffer')) {
    $phpcsDir = $vendorDir . $ds . 'squizlabs' . $ds . 'php_codesniffer';
} elseif ($phpcsDir !== false) {
    $phpcsDir = realpath($phpcsDir);
}

// Try to load the PHPCS autoloader.
if ($phpcsDir !== false && file_exists($phpcsDir . $ds . 'autoload.php')) {
    // PHPCS 3.x.
    require_once $phpcsDir . $ds . 'autoload.php';

    // Preload the token back-fills to prevent undefined constant notices.
    require_once $phpcsDir . $ds . 'src' . $ds . 'Util' . $ds . 'Tokens.php';
} elseif ($phpcsDir !== false && file_exists($phpcsDir . $ds . 'CodeSniffer.php')) {
    // PHPCS 2.x.
    require_once $phpcsDir . $ds . 'CodeSniffer.php';

    // Preload the token back-fills to prevent undefined constant notices.
    require_once $phpcsDir . $ds . 'CodeSniffer' . $ds . 'Tokens.php';
} else {
    echo 'Could not find PHP CodeSniffer.

If you use Composer, please run `composer install`.
Otherwise, make sure you set a `PHPCS_DIR` environment variable pointing to the PHP CodeSniffer directory.
';
    die(1);
}

$GLOBALS['PHP_CODESNIFFER_STANDARD_DIRS'] = [];
$GLOBALS['PHP_CODESNIFFER_TEST_DIRS'] = [];

// Load the composer autoload if available.
if (isset($vendorDir) && file_exists($vendorDir . $ds . 'autoload.php')) {
    require_once $vendorDir . $ds . 'autoload.php';
}

/*
 * Alias PHPCS 2.x classes to PHPCS 3.x names if needed.
 *
 * Also alias the non-namespaced PHPUnit 4.x/5.x test case class to the
 * namespaced PHPUnit 6+ version.
 */
require_once __DIR__ . $ds . 'VariableAnalysis' . $ds . 'autoload.php';

unset($phpcsDir, $vendorDir, $ds);
