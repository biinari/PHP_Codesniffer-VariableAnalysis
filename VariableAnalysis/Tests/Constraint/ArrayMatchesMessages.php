<?php
/**
 * This file is part of the VariableAnalysis addon for PHP_CodeSniffer.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VariableAnalysis\Tests\Constraint;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\IsEqual;

/**
 * Constraint that asserts that the array matches expected messages.
 *
 * The evaluated array is a list of warnings or errors per line and column like:
 *
 * <code>
 * $line = 2;
 * $column = 10;
 * [
 *   $line => [
 *     $column => [
 *       [
 *         'message' => 'Unused function parameter $foo.',
 *         'source' => 'VariableAnalysis.VariableAnalysis.VariableAnalysis.UnusedVariable',
 *         'listener' => 'VariableAnalysis\Sniffs\VariableAnalysis\VariableAnalysisSniff',
 *         'severity' => 5,
 *         'fixable' => false
 *       ],
 *     ]
 *   ]
 * ]
 * </code>
 */
class ArrayMatchesMessages extends \PHPUnit\Framework\Constraint\Constraint
{
    /**
     * Expected messages as a list of [line, column, message]
     *
     * e.g.:
     *
     * <code>
     * $line = 2;
     * $column = 10;
     * [
     *   [$line, $column, 'Unused function parameter $foo.'],
     * ]
     * </code>
     *
     * @var array Expected messages
     */
    private $messages;

    /**
     * Constructor
     *
     * @param array $messages Expected list of messages as [line, column, message]
     */
    public function __construct($messages)
    {
        parent::__construct();
        $this->messages = $this->normaliseExpectedMessages($messages);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function toString(): string
    {
        return 'matches expected messages' . PHP_EOL . $this->exporter->export($this->messages);
    }

    /**
     * Evaluates the constraint for parameter $actual.
     *
     * @param array $actual PHPCodeSniffer warnings or errors list.
     *
     * @return bool True if all messages match, false otherwise.
     */
    protected function matches($actual): bool
    {
        if (!\is_array($actual)) {
            return false;
        }

        $actualMessages = $this->extractMessages($actual);

        if (\array_keys($actualMessages) != \array_keys($this->messages)) {
            return false;
        }
        foreach ($actualMessages as $line => $actualLine) {
            $expectedLine = $this->messages[$line];
            if (\array_keys($actualLine) != \array_keys($expectedLine)) {
                return false;
            }

            foreach ($actualLine as $column => $actualColumn) {
                $expectedColumn = $expectedLine[$column];
                if ($actualColumn != $expectedColumn) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $actual Evaluated warnings or errors from a PHPCodeSniffer file.
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    protected function failureDescription($actual): string
    {
        $actualMessages = $this->extractMessages($actual);
        return $this->exporter->export($actualMessages) . ' ' . $this->toString();
    }

    private function extractMessages($actual)
    {
        $extractedMessages = [];
        foreach ($actual as $line => $lineMessages) {
            $extractedMessages[$line] = [];
            foreach ($lineMessages as $column => $columnMessages) {
                $sortedMessages = array_map(
                    function ($item) {
                        return $item['message'];
                    },
                    $columnMessages
                );
                sort($sortedMessages);
                $extractedMessages[$line][$column] = $sortedMessages;
            }
        }
        return $extractedMessages;
    }

    private function normaliseExpectedMessages($messages) {
        $result = [];
        foreach ($messages as $line => $lineMessages) {
            $result[$line] = [];
            foreach ($lineMessages as $column => $columnMessages) {
                $sortedMessages = array_values($columnMessages);
                sort($sortedMessages);
                $result[$line][$column] = $sortedMessages;
            }
        }
        return $result;
    }
}
