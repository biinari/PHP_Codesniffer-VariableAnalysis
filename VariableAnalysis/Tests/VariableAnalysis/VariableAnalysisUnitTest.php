<?php

namespace VariableAnalysis\Tests\VariableAnalysis;

use VariableAnalysis\Tests\Constraint\ArrayMatchesMessages;
use VariableAnalysis\Tests\Constraint\ContainsMessage;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Exceptions\RuntimeException;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\InvalidArgumentHelper;

/**
 * Unit test class for the VariableAnalysis sniff.
 *
 * A sniff unit test checks a .inc file for expected violations of a single
 * coding standard. Expected errors and warnings are stored in this class.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Manuel Pichler <mapi@manuel-pichler.de>
 * @author    Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @copyright 2011-2012 Sam Graham <php-codesniffer-variableanalysis BLAHBLAH illusori.co.uk>
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class VariableAnalysisUnitTest extends TestCase
{

    // FIXME: These static variables are used but not detected.
    // phpcs:disable VariableAnalysis.VariableAnalysis.VariableAnalysis.UnusedVariable
    private static Config $config;
    private static array $savedConfigSettings;
    // phpcs:enable

    /**
     * Setup config for this sniff.
     *
     * @beforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
        $config = new Config();
        $config->standards = ['VariableAnalysis'];
        $config->sniffs = ['VariableAnalysis.VariableAnalysis.VariableAnalysis'];
        self::$config = $config;
    }

    /**
     * Setup per test case.
     *
     * @before
     *
     * @return void
     */
    public function setUp() {
        self::$savedConfigSettings = self::$config->getSettings();
    }

    /**
     * Teardown per test.
     *
     * @after
     *
     * @return void
     */
    public function tearDown() {
        self::$config->setSettings(self::$savedConfigSettings);
    }

    /**
     * Create a dummy file for code under test
     *
     * @param string $contents Contents of code snippet under test.
     *
     * @return PHPCodeSniffer\Files\DummyFile
     */
    private function dummyFile($contents) {
        $ruleset = new Ruleset(self::$config);
        $dummyFile = new DummyFile($contents, $ruleset, self::$config);
        try {
            $dummyFile->process();
        } catch (RuntimeException $e) {
            $this->fail('Error processing dummy file: ' . $e->getMessage());
        }
        return $dummyFile;
    }

    /**
     * Insert code before and after assignment to variable(s)
     *
     * @param string $template Surrounding code to insert into. BEFORE and
     *                         AFTER markers show where to insert $code such
     *                         that it is before and after the assignment
     *                         respectively.
     * @param string $code     The code to insert in place of the BEFORE and
     *                         AFTER markers.
     *
     * @return [string] The result of inserting $code before and after
     *                  respectively, i.e. [$before, $after]
     */
    private function insertBeforeAndAfter($template, $code) {
        $beforeAssign = str_replace(
            'AFTER', '', str_replace('BEFORE', $code, $template)
        );
        $afterAssign = str_replace(
            'AFTER', $code, str_replace('BEFORE', '', $template)
        );

        return [$beforeAssign, $afterAssign];
    }

    /**
     * Assert file warnings match the expected messages on given lines.
     *
     * @param PHPCodeSniffer\Files\File $file     File under test.
     * @param array                     $expected Expected warnings, each as [line, column, message]
     * @param string                    $desc     Additional description for the assertion.
     */
    private function assertWarningsMatch(File $file, $expected, $desc = '') {
        $desc = $desc == '' ? $desc : ' ' . $desc;
        if (!\is_array($expected)) {
            throw InvalidArgumentHelper::factory(2, 'array');
        }

        $constraint = new ArrayMatchesMessages($expected);
        $this->assertThat($file->getWarnings(), $constraint, 'warnings' . $desc);
    }

    /**
     * Assert file errors match the expected messages on given lines.
     *
     * @param PHPCodeSniffer\Files\File $file     File under test.
     * @param array                     $expected Expected errors, each as [line, column, message]
     * @param string                    $desc     Additional description for the assertion.
     */
    private function assertErrorsMatch(File $file, $expected, $desc = '') {
        $desc = $desc == '' ? $desc : ' ' . $desc;
        if (!\is_array($expected)) {
            throw InvalidArgumentHelper::factory(2, 'array');
        }

        $constraint = new ArrayMatchesMessages($expected);
        $this->assertThat($file->getErrors(), $constraint, 'errors' . $desc);
    }

    /**
     * Add line and column offset to expected messages.
     *
     * @param array $expectedMessages List of expected messages
     * @param int   $line             Offset to code line.
     * @param int   $column           Offset to code column.
     *
     * @return array expectedMessages with offsets applied.
     */
    private function offset($expectedMessages, $line, $column) {
        $result = [];
        foreach ($expectedMessages as $y => $lineMessages) {
            $result[$y + $line] = [];
            foreach ($lineMessages as $x => $columnMessages) {
                $result[$y + $line][$x + $column] = $columnMessages;
            }
        }
        return $result;
    }

    /**
     * Test checks for use of undefined variables in function with no parameters.
     *
     * @param string $code         Code snippet under test
     * @param array  $errorsBefore Expected errors before $var is assigned.
     * @param array  $errorsAfter  Optional. Expected errors after $var is
     *                             assigned.  Defaults to []
     *
     * @dataProvider dataUndefinedVar
     *
     * @return void
     */
    public function testFunctionNoParams($code, $errorsBefore, $errorsAfter = []) {
        $template = <<<'CODE'
<?php
function function_without_param() {
    BEFORE
    $var = 'set the var';
    AFTER
    return $var;
}
CODE;
        list(
            $beforeAssign, $afterAssign
        ) = $this->insertBeforeAndAfter($template, $code);

        $beforeFile = $this->dummyFile($beforeAssign);
        $this->assertWarningsMatch($beforeFile, [], 'before assign');
        $this->assertErrorsMatch($beforeFile, $this->offset($errorsBefore, 2, 4), 'before assign');

        $afterFile = $this->dummyFile($afterAssign);
        $this->assertWarningsMatch($afterFile, [], 'after assign');
        $this->assertErrorsMatch($afterFile, $this->offset($errorsAfter, 4, 4), 'after assign');
    }

    /**
     * Data provider for undefined variable sniff in function without param.
     *
     * @see testFunctionNoParams()
     * @see testClassNoMembersMethodNoParams()
     *
     * @return array
     */
    public function dataUndefinedVar() {
        return [
            [
                'echo $var;',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'echo "xxx $var xxx";',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'echo "xxx {$var} xxx";',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'echo "xxx ${var} xxx";',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'echo "xxx $var $var2 xxx";',
                [
                    1 => [
                        6 => [
                            'Variable $var is undefined.',
                            'Variable $var2 is undefined.'
                        ],
                    ],
                ],
                [
                    1 => [ 6 => ['Variable $var2 is undefined.'] ],
                ],
            ],
            [
                'echo "xxx {$var} {$var2} xxx";',
                [
                    1 => [
                        6 => [
                            'Variable $var is undefined.',
                            'Variable $var2 is undefined.'
                        ],
                    ],
                ],
                [
                    1 => [ 6 => ['Variable $var2 is undefined.'] ],
                ],
            ],
            [
                'func($var);',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'func(12, $var);',
                [
                    1 => [ 10 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'func($var, 12);',
                [
                    1 => [ 6 => ['Variable $var is undefined.'] ],
                ],
            ],
            [
                'func(12, $var, 12);',
                [
                    1 => [ 10 => ['Variable $var is undefined.'] ],
                ],
            ],
        ];
    }

    /**
     * Test checks for use of undefined variables in function with parameter.
     *
     * @return void
     */
    public function testFunctionWithParam() {
        $code = <<<'CODE'
<?php
function function_with_param($param) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks for unused variables in function with default param.
     *
     * @return void
     */
    public function testFunctionWithDefaultDefinedParam() {
        $code = <<<'CODE'
<?php
function function_with_default_defined_param($unused, $param = 12) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}
CODE;

        $file = $this->dummyFile($code);

        $this->assertWarningsMatch(
            $file,
            [
                2 => [ 46 => ['Unused function parameter $unused.'] ],
            ]
        );
    }

    /**
     * Test checks for unused variables in function with default null param.
     *
     * @return void
     */
    public function testFunctionWithDefaultNullParam() {
        $code = <<<'CODE'
<?php
function function_with_default_null_param($unused, $param = null) {
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    $param = 'set the param';
    echo $param;
    echo "xxx $param xxx";
    echo "xxx {$param} xxx";
    return $param;
}
CODE;

        $file = $this->dummyFile($code);

        $this->assertWarningsMatch(
            $file,
            [
                2 => [ 43 => ['Unused function parameter $unused.'] ],
            ]
        );
    }

    /**
     * Test checks for unused / undefined variables in function with global vars.
     *
     * @return void
     */
    public function testFunctionWithGlobalVars() {
        $code = <<<'CODE'
<?php
function function_with_global_var() {
    global $var, $var2, $unused;

    echo $var;
    echo $var3;
    return $var2;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                3 => [ 25 => ['Unused global variable $unused.'] ],
            ]
        );
        $this->assertErrorsMatch(
            $file,
            [
                6 => [ 10 => ['Variable $var3 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks for unused and undefined variables in function with foreach.
     *
     * @return void
     */
    public function testFunctionWithUndefinedForeach() {
        $code = <<<'CODE'
<?php
function function_with_undefined_foreach() {
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}
CODE;

        $file = $this->dummyFile($code);

        $this->assertWarningsMatch(
            $file,
            [
                19 => [
                    24 => ['Unused variable $element3.']
                ],
                21 => [
                    25 => ['Unused variable $element4.']
                ],
                23 => [
                    24 => ['Unused variable $key3.'],
                    33 => ['Unused variable $value3.'],
                ],
                25 => [
                    24 => ['Unused variable $key4.'],
                    34 => ['Unused variable $value4.']
                ],
            ]
        );
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 14 => ['Variable $array is undefined.'] ],
                7 => [ 14 => ['Variable $array is undefined.'] ],
                11 => [ 14 => ['Variable $array is undefined.'] ],
                15 => [ 14 => ['Variable $array is undefined.'] ],
                19 => [ 14 => ['Variable $array is undefined.'] ],
                21 => [ 14 => ['Variable $array is undefined.'] ],
                23 => [ 14 => ['Variable $array is undefined.'] ],
                25 => [ 14 => ['Variable $array is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks for unused variables in function with foreach, all defined.
     *
     * @return void
     */
    public function testFunctionWithDefinedForeach() {
        $code = <<<'CODE'
<?php
function function_with_defined_foreach() {
    $array = array();
    foreach ($array as $element1) {
        echo $element1;
    }
    echo $element1;
    foreach ($array as &$element2) {
        echo $element2;
    }
    echo $element2;
    foreach ($array as $key1 => $value1) {
        echo "$key1 => $value1\n";
    }
    echo "$key1 => $value1\n";
    foreach ($array as $key2 => &$value2) {
        echo "$key2 => $value2\n";
    }
    echo "$key2 => $value2\n";
    foreach ($array as $element3) {
    }
    foreach ($array as &$element4) {
    }
    foreach ($array as $key3 => $value3) {
    }
    foreach ($array as $key4 => &$value4) {
    }
}
CODE;

        $file = $this->dummyFile($code);

        $this->assertWarningsMatch(
            $file,
            [
                20 => [
                    24 => ['Unused variable $element3.']
                ],
                22 => [
                    25 => ['Unused variable $element4.']
                ],
                24 => [
                    24 => ['Unused variable $key3.'],
                    33 => ['Unused variable $value3.']
                ],
                26 => [
                    24 => ['Unused variable $key4.'],
                    34 => ['Unused variable $value4.']
                ],
            ]
        );
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks for use of undefined variables in class method without params.
     *
     * @param string $code         Code snippet under test
     * @param array  $errorsBefore Expected errors before $var is assigned.
     * @param array  $errorsAfter  Optional. Expected errors after $var is
     *                             assigned.  Defaults to []
     *
     * @dataProvider dataUndefinedVar
     *
     * @return void
     */
    public function testClassNoMembersMethodNoParams($code, $errorsBefore, $errorsAfter = []) {
        $template = <<<'CODE'
<?php
class ClassWithoutMembers {
    function method_without_param() {
        BEFORE
        $var = 'set the var';
        AFTER
        $this->other_method();
        return $var;
    }

    function other_method() {
    }
}
CODE;

        list(
            $beforeAssign, $afterAssign
        ) = $this->insertBeforeAndAfter($template, $code);

        $beforeFile = $this->dummyFile($beforeAssign);
        $this->assertWarningsMatch($beforeFile, [], 'before assign');
        $this->assertErrorsMatch($beforeFile, $this->offset($errorsBefore, 3, 8), 'before assign');

        $afterFile = $this->dummyFile($afterAssign);
        $this->assertWarningsMatch($afterFile, [], 'after assign');
        $this->assertErrorsMatch($afterFile, $this->offset($errorsAfter, 5, 8), 'after assign');
    }

    /**
     * Test checks for use of undefined variables in class method with param.
     *
     * @return void
     */
    public function testClassNoMembersMethodWithParam() {
        $code = <<<'CODE'
<?php
class ClassWithoutMembers {
    function method_with_param($param) {
        echo $param;
        echo "xxx $param xxx";
        echo "xxx {$param} xxx";
        $param = 'set the param';
        echo $param;
        echo "xxx $param xxx";
        echo "xxx {$param} xxx";
        $this->other_method():
        return $param;
    }

    function other_method() {
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks for use of undefined variables in class method using member var.
     *
     * @return void
     */
    public function testClassNoMembersMethodWithMemberVar() {
        $code = <<<'CODE'
<?php
class ClassWithoutMembers {
    function method_with_member_var() {
        echo $this->member_var;
        echo self::$static_member_var;
    }
}
CODE;
        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);

        // NOTE: We don't inspect class inheritance so we can't determine that
        // member variables are undeclared.
        $this->assertErrorsMatch(
            $file,
            [
                4 => [ 21 => ['Variable $member_var is undefined.'] ],
            ]
        );

        $this->markTestIncomplete(
            'FIXME: Should we report that self::$static_member_var is undefined?'
        );
        $this->assertErrorsMatch(
            $file,
            [
                4 => [ 21 => ['Variable $member_var is undefined.'] ],
                5 => [ 21 => ['Variable $static_member_var is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks for use of undefined variables in class with member vars.
     */
    public function testClassWithMembersMethodWithMemberVar() {
        $code = <<<'CODE'
<?php
class ClassWithMembers {
    public $member_var;
    static $static_member_var;

    function method_with_member_var() {
        echo $this->member_var;
        echo $this->no_such_member_var;
        echo self::$static_member_var;
        echo self::$no_such_static_member_var;
        echo SomeOtherClass::$external_static_member_var;
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertErrorsMatch(
            $file,
            [
                8 => [ 21 => ['Variable $no_such_member_var is undefined.'] ],
            ]
        );

        $this->markTestIncomplete(
            'FIXME: $static_member_var is actually used and we should add ' .
            'checks for static variable use.'
        );
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                8 => [ 21 => ['Variable $no_such_member_var is undefined.'] ],
                10 => [ 21 => ['Variable $no_such_static_member_var is undefined.'] ],
                11 => [ 21 => ['Variable $external_static_member_var is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks for use of $this outside of a class.
     */
    public function testThisOutsideClass() {
        $code = <<<'CODE'
<?php
function function_with_this_outside_class() {
    return $this->whatever();
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 12 => ['Variable $this is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks for use of static members outside of a class.
     */
    public function testStaticMembersOutsideClass() {
        $code = <<<'CODE'
<?php
function function_with_static_members_outside_class() {
    echo SomeOtherClass::$external_static_member_var;
    return self::$whatever;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                4 => [ 18 => ['Use of self::$whatever outside class definition.'] ],
            ]
        );
    }

    /**
     * Test checks for use of late static binding outside of a class.
     */
    public function testLateStaticBindingOutsideClass() {
        $code = <<<'CODE'
<?php
function function_with_late_static_binding_outside_class() {
    echo static::$whatever;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 18 => ['Use of static::$whatever outside class definition.'] ],
            ]
        );
    }

    /**
     * Test checks for use of variables defined in outer scope of closure.
     */
    public function testFunctionWithClosure() {
        $code = <<<'CODE'
<?php
function function_with_closure($outer_param) {
    $outer_var  = 1;
    $outer_var2 = 2;
    array_map(function ($inner_param) {
            $inner_var = 1;
            echo $outer_param;
            echo $inner_param;
            echo $outer_var;
            echo $outer_var2;
            echo $inner_var;
        }, array());
    array_map(function () use ($outer_var, $outer_var3, &$outer_param) {
            $inner_var2 = 2;
            echo $outer_param;
            echo $inner_param;
            echo $outer_var;
            echo $outer_var2;
            echo $outer_var3;
            echo $inner_var;
            echo $inner_var2;
        }, array());
    echo $outer_var;
    echo $outer_var2;
    echo $outer_var3;
    echo $inner_param;
    echo $inner_var;
    echo $inner_var2;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                7 => [ 18 => ['Variable $outer_param is undefined.'] ],
                9 => [ 18 => ['Variable $outer_var is undefined.'] ],
                10 => [ 18 => ['Variable $outer_var2 is undefined.'] ],
                13 => [ 44 => ['Variable $outer_var3 is undefined.'] ],
                16 => [ 18 => ['Variable $inner_param is undefined.'] ],
                18 => [ 18 => ['Variable $outer_var2 is undefined.'] ],
                19 => [ 18 => ['Variable $outer_var3 is undefined.'] ],
                20 => [ 18 => ['Variable $inner_var is undefined.'] ],
                25 => [ 10 => ['Variable $outer_var3 is undefined.'] ],
                26 => [ 10 => ['Variable $inner_param is undefined.'] ],
                27 => [ 10 => ['Variable $inner_var is undefined.'] ],
                28 => [ 10 => ['Variable $inner_var2 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with return by reference.
     */
    public function testFunctionWithReturnByRefAndParam() {
        $code = <<<'CODE'
<?php
function &function_with_return_by_reference_and_param($param) {
    echo $param;
    return $param;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks function with unused static variables.
     */
    public function testFunctionWithStaticVar() {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $code = <<<'CODE'
<?php
function function_with_static_var() {
    static $static1, $static_num = 12, $static_neg_num = -1.5, $static_string = 'abc', $static_string2 = "def", $static_define = MYDEFINE, $static_constant = MyClass::CONSTANT, $static2;
    static $static_heredoc = <<<END_OF_HEREDOC
this is an ugly but valid way to continue after a heredoc
END_OF_HEREDOC
        , $static3;
    static $static_nowdoc = <<<'END_OF_NOWDOC'
this is an ugly but valid way to continue after a nowdoc
END_OF_NOWDOC
        , $static4;
    echo $static1;
    echo $static_num;
    echo $static2;
    echo $var;
    echo $static_heredoc;
    echo $static3;
    echo $static_nowdoc;
    echo $static4;
}
CODE;
        // phpcs:enable

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                3 => [
                    40 => ['Unused variable $static_neg_num.'],
                    64 => ['Unused variable $static_string.'],
                    88 => ['Unused variable $static_string2.'],
                    113 => ['Unused variable $static_define.'],
                    140 => ['Unused variable $static_constant.']
                ],
            ]
        );
        $this->assertErrorsMatch(
            $file,
            [
                15 => [ 10 => ['Variable $var is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with pass by reference param.
     */
    public function testFunctionWithPassByRefParam() {
        $code = <<<'CODE'
<?php
function function_with_pass_by_reference_param(&$param) {
    echo $param;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks use of undefined vars in function with pass by reference calls.
     */
    public function testFunctionWithPassByReferenceCalls() {
        $code = <<<'CODE'
<?php
function function_with_pass_by_reference_calls() {
    echo $matches;
    echo $needle;
    echo $haystack;
    preg_match('/(abc)/', 'defabcghi', $matches);
    preg_match($needle,   'defabcghi', $matches);
    preg_match('/(abc)/', $haystack,   $matches);
    echo $matches;
    echo $needle;
    echo $haystack;
    $stmt = 'whatever';
    $var1 = 'one';
    $var2 = 'two';
    echo $var1;
    echo $var2;
    echo $var3;
    maxdb_stmt_bind_result($stmt, $var1, $var2, $var3);
    echo $var1;
    echo $var2;
    echo $var3;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 10 => ['Variable $matches is undefined.'] ],
                4 => [ 10 => ['Variable $needle is undefined.'] ],
                5 => [ 10 => ['Variable $haystack is undefined.'] ],
                7 => [ 16 => ['Variable $needle is undefined.'] ],
                8 => [ 27 => ['Variable $haystack is undefined.'] ],
                10 => [ 10 => ['Variable $needle is undefined.'] ],
                11 => [ 10 => ['Variable $haystack is undefined.'] ],
                17 => [ 10 => ['Variable $var3 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with try ... catch.
     */
    public function testFunctionWithTryCatch() {
        $code = <<<'CODE'
<?php
function function_with_try_catch() {
    echo $e;
    $var = 1;
    echo $var;
    try {
        echo $e;
        echo $var;
    } catch (Exception $e) {
        echo $e;
        echo $var;
    }
    echo $e;
    echo $var;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 10 => ['Variable $e is undefined.'] ],
                7 => [ 14 => ['Variable $e is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks $this usage in a closure within a class.
     */
    public function testClassWithThisInsideClosure() {
        $code = <<<'CODE'
<?php
class ClassWithThisInsideClosure {
    function method_with_this_inside_closure() {
        echo $this;
        echo "$this";
        array_map(function ($inner_param) {
                echo $this;
                echo "$this";
            }, array());
        echo $this;
        echo "$this";
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                6 => [ 29 => ['Unused function parameter $inner_param.'] ],
            ]
        );
        // NOTE: $this is automatically passed to anonymous functions since
        // PHP 5.4.0 so no errors for that.
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks self usage in a closure within a class.
     */
    public function testClassWithSelfInsideClosure() {
        $code = <<<'CODE'
<?php
class ClassWithSelfInsideClosure {
    static $static_member;

    function method_with_self_inside_closure() {
        echo self::$static_member;
        array_map(function () {
                echo self::$static_member;
            }, array());
        echo self::$static_member;
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertErrorsMatch($file, []);

        $this->markTestIncomplete('FIXME: static variable $static_member is used.');
        $this->assertWarningsMatch($file, []);
    }

    /**
     * Test checks function with inline assignments.
     */
    public function testFunctionWithInlineAssigns() {
        $code = <<<'CODE'
<?php
function function_with_inline_assigns() {
    echo $var;
    ($var = 12) && $var;
    echo $var;
    echo $var2;
    while ($var2 = whatever()) {
        echo $var2;
    }
    echo $var2;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                3 => [ 10 => ['Variable $var is undefined.'] ],
                6 => [ 10 => ['Variable $var2 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with redeclarations as globals.
     */
    public function testFunctionWithGlobalRedeclarations() {
        $code = <<<'CODE'
<?php
function function_with_global_redeclarations($param) {
    global $global;
    static $static;
    $bound = 12;
    $local = function () use ($bound) {
            global $bound;
            echo $bound;
        };
    try {
    } catch (Exception $e) {
    }
    echo "$param $global $static $bound $local $e\n"; // Stop unused var warnings.
    global $param;
    global $static;
    global $bound;
    global $local;
    global $e;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                7 => [ 20 => ['Redeclaration of bound variable $bound as global variable.'] ],
                14 => [ 12 => ['Redeclaration of function parameter $param as global variable.'] ],
                15 => [ 12 => ['Redeclaration of static variable $static as global variable.'] ],
                16 => [ 12 => ['Redeclaration of variable $bound as global variable.'] ],
                17 => [ 12 => ['Redeclaration of variable $local as global variable.'] ],
                18 => [ 12 => ['Redeclaration of variable $e as global variable.'] ],
            ]
        );
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks function with redeclarations as static variables.
     */
    public function testFunctionWithStaticRedeclarations() {
        $code = <<<'CODE'
<?php
function function_with_static_redeclarations($param) {
    global $global;
    static $static, $static;
    $bound = 12;
    $local = function () use ($bound) {
            static$bound;
            echo $bound;
        };
    try {
    } catch (Exception $e) {
    }
    echo "$param $global $static $bound $local $e\n"; // Stop unused var warnings.
    static $param;
    static $static;
    static $bound;
    static $local;
    static $e;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                4 => [ 21 => ['Redeclaration of static variable $static as static variable.'] ],
                7 => [ 19 => ['Redeclaration of bound variable $bound as static variable.'] ],
                14 => [ 12 => ['Redeclaration of function parameter $param as static variable.'] ],
                15 => [ 12 => ['Redeclaration of static variable $static as static variable.'] ],
                16 => [ 12 => ['Redeclaration of variable $bound as static variable.'] ],
                17 => [ 12 => ['Redeclaration of variable $local as static variable.'] ],
                18 => [ 12 => ['Redeclaration of variable $e as static variable.'] ],
            ]
        );
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks function with redeclarations in catch declarations.
     */
    public function testFunctionWithCatchRedeclarations() {
        $code = <<<'CODE'
<?php
function function_with_catch_redeclarations() {
    try {
    } catch (Exception $e) {
        echo $e;
    }
    try {
    } catch (Exception $e) {
        echo $e;
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks function with superglobals
     */
    public function testFunctionWithSuperglobals() {
        $code = <<<'CODE'
<?php
function function_with_superglobals() {
    echo print_r($GLOBALS, true);
    echo print_r($_SERVER, true);
    echo print_r($_GET, true);
    echo print_r($_POST, true);
    echo print_r($_FILES, true);
    echo print_r($_COOKIE, true);
    echo print_r($_SESSION, true);
    echo print_r($_REQUEST, true);
    echo print_r($_ENV, true);
    echo "{$GLOBALS['whatever']}";
    echo "{$GLOBALS['whatever']} $var";
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                13 => [ 10 => ['Variable $var is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with heredoc
     */
    public function testFunctionWithHeredoc() {
        $code = <<<'CODE'
<?php
function function_with_heredoc() {
    $var = 10;
    echo <<<END_OF_TEXT
$var
{$var}
${var}
$var2
{$var2}
${var2}
\$var2
\\$var2
END_OF_TEXT;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                8 => [ 1 => ['Variable $var2 is undefined.'] ],
                9 => [ 1 => ['Variable $var2 is undefined.'] ],
                10 => [ 1 => ['Variable $var2 is undefined.'] ],
                12 => [ 1 => ['Variable $var2 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks variable property names.
     */
    public function testClassWithSymbolicRefProperty() {
        $code = <<<'CODE'
<?php
class ClassWithSymbolicRefProperty {
    public $my_property;

    function method_with_symbolic_ref_property() {
        $properties = array('my_property');
        foreach ($properties as $property) {
            $this->$property = 'some value';
            $this -> $property = 'some value';
            $this->$undefined_property = 'some value';
            $this -> $undefined_property = 'some value';
        }
    }

    function method_with_symbolic_ref_method() {
        $methods = array('method_with_symbolic_ref_property');
        foreach ($methods as $method) {
            $this->$method();
            $this -> $method();
            $this->$undefined_method();
            $this -> $undefined_method();
        }
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertErrorsMatch(
            $file,
            [
                // FIXME: $this->$property refers to defined property.
                8 => [ 20 => ['Variable $property is undefined.'] ],
                9 => [ 22 => ['Variable $property is undefined.'] ],

                10 => [ 20 => ['Variable $undefined_property is undefined.'] ],
                11 => [ 22 => ['Variable $undefined_property is undefined.'] ],

                20 => [ 20 => ['Variable $undefined_method is undefined.'] ],
                21 => [ 22 => ['Variable $undefined_method is undefined.'] ],
            ]
        );

        $this->markTestIncomplete('FIXME: property $my_property is used + defined.');
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                10 => [ 20 => ['Variable $undefined_property is undefined.'] ],
                11 => [ 22 => ['Variable $undefined_property is undefined.'] ],

                20 => [ 20 => ['Variable $undefined_method is undefined.'] ],
                21 => [ 22 => ['Variable $undefined_method is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with only assign to pass by reference parameter.
     */
    public function testFunctionWithPassByRefAssignOnlyArg() {
        $code = <<<'CODE'
<?php
function function_with_pass_by_ref_assign_only_arg(&$return_value) {
    $return_value = 42;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch($file, []);
    }

    /**
     * Test checks class with late static binding.
     */
    public function testClassWithLateStaticBinding() {
        $code = <<<'CODE'
<?php
class ClassWithLateStaticBinding {
    static function method_with_late_static_binding($param) {
        static::some_method($param);
        static::some_method($var);
        static::some_method(static::CONSTANT, $param);
    }
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch($file, []);
        $this->assertErrorsMatch(
            $file,
            [
                5 => [ 29 => ['Variable $var is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with single quoted compact.
     */
    public function testFunctionWithSingleQuotedCompact() {
        $code = <<<'CODE'
<?php
function function_with_literal_compact($param1, $param2, $param3, $param4) {
    $var1 = 'value1';
    $var2 = 'value2';
    $var4 = 'value4';
    $squish = compact('var1');
    $squish = compact('var3');
    $squish = compact('param1');
    $squish = compact('var2', 'param3');
    $squish = compact(array('var4'), array('param4', 'var5'));
    echo $squish;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                2 => [ 49 => ['Unused function parameter $param2.'] ],
            ]
        );
        $this->assertErrorsMatch(
            $file,
            [
                7 => [ 23 => ['Variable $var3 is undefined.'] ],
                10 => [ 54 => ['Variable $var5 is undefined.'] ],
            ]
        );
    }

    /**
     * Test checks function with double quoted expression compact.
     */
    public function testFunctionWithExpressionCompact() {
        $code = <<<'CODE'
<?php
function function_with_expression_compact($param1, $param2, $param3, $param4) {
    $var1 = "value1";
    $var2 = "value2";
    $var4 = "value4";
    $var6 = "value6";
    $var7 = "value7";
    $var8 = "value8";
    $var9 = "value9";
    $squish = compact("var1");
    $squish = compact("var3");
    $squish = compact("param1");
    $squish = compact("var2", "param3");
    $squish = compact(array("var4"), array("param4", "var5"));
    $squish = compact($var6);
    $squish = compact("var" . "7");
    $squish = compact("blah $var8");
    $squish = compact("$var9");
    echo $squish;
}
CODE;

        $file = $this->dummyFile($code);
        $this->assertWarningsMatch(
            $file,
            [
                2 => [ 52 => ['Unused function parameter $param2.'] ],
                7 => [ 5 => ['Unused variable $var7.'] ],
            ]
        );
        $this->assertErrorsMatch(
            $file,
            [
                11 => [ 23 => ['Variable $var3 is undefined.'] ],
                14 => [ 54 => ['Variable $var5 is undefined.'] ],
            ]
        );
    }
}
