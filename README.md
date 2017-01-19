PHP_CodeSniffer VariableAnalysis
================================

Forked from https://github.com/grohiro/PHP_Codesniffer-VariableAnalysis and merged different PRs.
Code Modernization for PHP7 and ongoing changes coming up.

Plugin for PHP_CodeSniffer static analysis tool that adds analysis of problematic
variable use.

 * Performs static analysis of variable use.
 * Warns on use of undefined variables.
 * Warns if variables are set or declared but never used within that scope.
 * Warns if variables are redeclared within same scope.
 * Warns if $this, self::$static_member, static::$static_member is used outside class scope.
 * Allows $this inside closures in PHP >=5.4
 * Add analysis of instance variables ($this->...)

INSTALLATION
------------

    composer require --global ksjogo/variable-analysis

Then add

    <rule ref="VariableAnalysis"/>

to your ruleset.xml.

CUSTOMIZATION
-------------

There's a variety of options to customize the behaviour of VariableAnalysis, take
a look at the included ruleset.xml for commented examples of a configuration.

KNOWN ISSUES & BUGS
-------------------

 * File scope isn't currently analysed.
