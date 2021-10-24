<?php

if (!defined('VARIABLE_ANALYSIS_AUTOLOAD')) {
    spl_autoload_register(function ($className) {
        if (stripos($className, 'VariableAnalysis') !== 0) {
            return;
        }

        $file = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR . strtr($className, '\\', DIRECTORY_SEPARATOR) . '.php';

        if (file_exists($file)) {
            include_once $file;
        }
    });

    define('VARIABLE_ANALYSIS_AUTOLOAD', true);
}

if (!defined('VARIABLE_ANALYSIS_ALIASES_SET')) {
    /*
     * Alias some PHPCS 2.x classes to their PHPCS 3.x equivalents.
     */
    if (interface_exists('\PHP_CodeSniffer_Sniff')
        && !interface_exists('\PHP_CodeSniffer\Sniffs\Sniff')
    ) {
        class_alias('\PHP_CodeSniffer_Sniff', '\PHP_CodeSniffer\Sniffs\Sniff');
    }

    if (interface_exists('\PHP_CodeSniffer_File')
        && !interface_exists('\PHP_CodeSniffer\Files\File')
    ) {
        class_alias('\PHP_CodeSniffer_File', '\PHP_CodeSniffer\Files\File');
    }

    /*
     * Alias the PHPUnit 4/5 TestCase class to its PHPUnit 6+ name.
     */
    if (class_exists('PHPUnit_Framework_TestCase')
        && !class_exists('PHPUnit\Framework\TestCase')
    ) {
        class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
    }

    define('VARIABLE_ANALYSIS_ALIASES_SET', true);
}
