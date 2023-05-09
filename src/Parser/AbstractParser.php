<?php
/**
 * MIT License
 *
 * Copyright (c) 2023-Present Kevin Traini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Marmotte\MdGen\Parser;

use Marmotte\MdGen\IndentWriter;

abstract class AbstractParser
{
    /**
     * @var string[]
     */
    protected array $lines;

    public function __construct(
        string                          $content,
        protected readonly IndentWriter $writer,
        protected readonly array        $functions,
    ) {
        $this->lines = explode("\n", $content);
    }

    private const PATTERN_RULE_VARIABLE       = /** @lang PhpRegExp */
        '/^\{\{(.*?)}}.*/';
    private const PATTERN_RULE_PCONDITION     = /** @lang PhpRegExp */
        '/^\{#(.*?)}}.*/';
    private const PATTERN_RULE_PCONDITION_END = /** @lang PhpRegExp */
        '/^\{\{(.*?)#}.*/';
    private const PATTERN_RULE_NCONDITION     = /** @lang PhpRegExp */
        '/^\{!(.*?)}}.*/';
    private const PATTERN_RULE_NCONDITION_END = /** @lang PhpRegExp */
        '/^\{\{(.*?)!}.*/';
    private const PATTERN_RULE_LOOP           = /** @lang PhpRegExp */
        '/^\{\((.*?)}}.*/';
    private const PATTERN_RULE_LOOP_END       = /** @lang PhpRegExp */
        '/^\{\{(.*?)\)}.*/';
    private const PATTERN_RULE_FUNCTION       = /** @lang PhpRegExp */
        '/^\{\|(.*?)}}.*/';
    private const TYPE_RULE_PCONDITION        = 'type-pcondition';
    private const TYPE_RULE_NCONDITION        = 'type-ncondition';
    private const TYPE_RULE_LOOP              = 'type-loop';

    /**
     * @param string[] $lines
     * @param array<string, mixed> $values
     * @return string[]
     */
    protected function parseScript(array $lines, array $values): array
    {
        $result = [];

        /**
         * @var array<array-key, array{
         *     type: string,
         *     name: string,
         *     begin: array{
         *         line: int,
         *         column: int,
         *     }
         * }> $rule
         */
        $rule = [];
        $current_rule = -1;
        foreach ($lines as $line) {
            $result_line = '';

            $i = 0;
            while ($i < mb_strlen($line)) {
                if ($line[$i] === '{') {
                    $matches = [];
                    $str = substr($line, $i);
                    switch (1) {
                        case preg_match(self::PATTERN_RULE_VARIABLE, $str, $matches):
                            $match = $matches[1];
                            // parseVariable;
                            $i += mb_strlen($match) + 4;
                            break;
                        case preg_match(self::PATTERN_RULE_PCONDITION, $str, $matches):
                            $match = $matches[1];
                            // parsePCondition
                            break;
                        case preg_match(self::PATTERN_RULE_PCONDITION_END, $str, $matches):
                            $match = $matches[1];
                            // parsePConditionEnd
                            $i += mb_strlen($match) + 4;
                            break;
                        case preg_match(self::PATTERN_RULE_NCONDITION, $str, $matches):
                            $match = $matches[1];
                            // parseNCondition
                            break;
                        case preg_match(self::PATTERN_RULE_NCONDITION_END, $str, $matches):
                            $match = $matches[1];
                            // parseNConditionEnd
                            $i += mb_strlen($match) + 4;
                            break;
                        case preg_match(self::PATTERN_RULE_LOOP, $str, $matches):
                            $match = $matches[1];
                            // parseLoop
                            break;
                        case preg_match(self::PATTERN_RULE_LOOP_END, $str, $matches):
                            $match = $matches[1];
                            // parseLoopEnd
                            $i += mb_strlen($match) + 4;
                            break;
                        case preg_match(self::PATTERN_RULE_FUNCTION, $str, $matches):
                            $match = $matches[1];
                            // parseFunction
                            $i += mb_strlen($match) + 4;
                            break;
                        default:
                            $result_line .= $line[$i];
                            $i++;
                            break;
                    }
                } else {
                    $result_line .= $line[$i];
                    $i++;
                }
            }

            $result[] = $result_line;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     * @return string
     */
    public abstract function parse(array $values): string;
}
