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

namespace Marmotte\Teng\Parsers;

use Marmotte\Teng\IndentWriter;
use Marmotte\Teng\Parsers\Rules\AbstractRule;
use Marmotte\Teng\Parsers\Rules\LoopRule;
use Marmotte\Teng\Parsers\Rules\NConditionRule;
use Marmotte\Teng\Parsers\Rules\PConditionRule;

abstract class AbstractParser
{
    /**
     * @param array<string, callable|array{object, string}> $functions
     */
    public function __construct(
        protected readonly IndentWriter $writer,
        private readonly array          $functions,
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public abstract function parse(string $content, array $values): string;

    private const PATTERN_RULE_VARIABLE       = /** @lang PhpRegExp */
        '/^(\{\{ *(([^ ]+?)( *\| *[^ ]+?)*) *}}).*/';
    private const PATTERN_RULE_PCONDITION     = /** @lang PhpRegExp */
        '/^(\{# *([^ ]+?) *}}).*/';
    private const PATTERN_RULE_PCONDITION_END = /** @lang PhpRegExp */
        '/^(\{\{ *([^ ]+?) *#}).*/';
    private const PATTERN_RULE_NCONDITION     = /** @lang PhpRegExp */
        '/^(\{! *([^ ]+?) *}}).*/';
    private const PATTERN_RULE_NCONDITION_END = /** @lang PhpRegExp */
        '/^(\{\{ *([^ ]+?) *!}).*/';
    private const PATTERN_RULE_LOOP           = /** @lang PhpRegExp */
        '/^(\{\( *([^ ].*?[^ ]) *}}).*/';
    private const PATTERN_RULE_LOOP_END       = /** @lang PhpRegExp */
        '/^(\{\{ *([^ ]+?) *\)}).*/';
    private const PATTERN_RULE_FUNCTION       = /** @lang PhpRegExp */
        '/(^\{\| *([a-zA-Z0-9_]*?) *(\((.*?)\))? *}}).*/';
    private const TYPE_RULE_PCONDITION        = 'type-pcondition';
    private const TYPE_RULE_NCONDITION        = 'type-ncondition';
    private const TYPE_RULE_LOOP              = 'type-loop';

    /**
     * @param array<string, mixed> $values
     */
    protected function parseScript(string $content, array $values): string
    {
        $result = '';

        /** @var AbstractRule[] $rules */
        $rules        = [];
        $current_rule = -1;
        $skip         = false;

        $i = 0;
        while ($i < mb_strlen($content)) {
            if ($content[$i] === '{') {
                $matches = [];
                $str     = substr($content, $i);
                switch (1) {
                    case preg_match(self::PATTERN_RULE_VARIABLE, $str, $matches):
                        $variable = $matches[2];
                        if (!$skip)
                            $result .= $this->parseVariable($variable, $values);
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_PCONDITION, $str, $matches):
                        if (!$skip) {
                            $condition = $matches[2];
                            $skip      = !$this->parseCondition($condition, $values);
                            $rules[]   = new PConditionRule($condition);
                            $current_rule++;
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_PCONDITION_END, $str, $matches):
                        $condition = $matches[2];
                        $rule      = $rules[$current_rule];
                        if ($rule instanceof PConditionRule && $rule->key === $condition) {
                            unset($rules[$current_rule]);
                            $current_rule--;
                            $skip = false;
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_NCONDITION, $str, $matches):
                        if (!$skip) {
                            $condition = $matches[2];
                            $skip      = $this->parseCondition($condition, $values);
                            $rules[]   = new NConditionRule($condition);
                            $current_rule++;
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_NCONDITION_END, $str, $matches):
                        $condition = $matches[2];
                        $rule      = $rules[$current_rule];
                        if ($rule instanceof NConditionRule && $rule->key === $condition) {
                            unset($rules[$current_rule]);
                            $current_rule--;
                            $skip = false;
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_LOOP, $str, $matches):
                        if (!$skip) {
                            $loop = $matches[2];
                            $rule = $this->parseLoop($loop, $values, $i + mb_strlen($matches[1]));
                            if ($rule === false) {
                                $i += mb_strlen($matches[1]);
                                break;
                            }
                            $rules[] = $rule;
                            $current_rule++;
                            $temp_values = $this->iterateLoop($rule, $values);
                            if ($temp_values === false) {
                                $skip = true;
                            } else {
                                $values = $temp_values;
                            }
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_LOOP_END, $str, $matches):
                        $loop = $matches[2];
                        $rule = $rules[$current_rule];
                        if ($rule instanceof LoopRule && $rule->key === $loop) {
                            if ($skip || $rule->end()) {
                                unset($rules[$current_rule]);
                                $current_rule--;
                                $skip = false;
                                $i    += mb_strlen($matches[1]);
                                break;
                            }
                            $temp_values = $this->iterateLoop($rule, $values);
                            if ($temp_values !== false) {
                                $values = $temp_values;
                                $i      = $rule->begin;
                                break;
                            }
                            unset($rules[$current_rule]);
                            $current_rule--;
                        }
                        $i += mb_strlen($matches[1]);
                        break;
                    case preg_match(self::PATTERN_RULE_FUNCTION, $str, $matches):
                        $name = $matches[2];
                        $args = $this->trimExplodeTrim($matches[4] ?? '', ',');
                        if (!$skip)
                            $result .= $this->parseFunction($name, $args, $values);
                        $i += mb_strlen($matches[1]);
                        break;
                    default:
                        if (!$skip)
                            $result .= $content[$i];
                        $i++;
                        break;
                }
            } else {
                if (!$skip)
                    $result .= $content[$i];
                $i++;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function parseVariable(string $variable, array $values): string
    {
        $components = $this->trimExplodeTrim($variable, '|');

        if (empty($components)) {
            return '';
        }

        $value = $this->getValue(array_shift($components), $values);
        if (!is_string($value)) {
            return $variable;
        }

        foreach ($components as $component) {
            $value = $this->parseFunction($component, [$value], $values);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function parseFunction(string $name, array $args, array $values): string
    {
        $function_name = trim($name);
        if (!array_key_exists($function_name, $this->functions)) {
            return '';
        }

        $fun       = $this->functions[$function_name];
        $arguments = array_map(
            fn(string $arg) => $this->getValue($arg, $values),
            $args
        );
        try {
            if (is_callable($fun)) {
                return (string) $fun(...$arguments);
            } else {
                return (string) $fun[0]->{$fun[1]}(...$arguments);
            }
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param array<string, mixed> $values
     */
    private function parseCondition(string $condition, array $values): bool
    {
        return $this->hasValue($condition, $values);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function parseLoop(string $loop, array $values, int $begin): LoopRule|false
    {
        $components = $this->trimExplodeTrim($loop, ':');
        if (count($components) !== 2) {
            return false;
        }

        $key = $components[0];
        if (!$this->hasValue($key, $values)) {
            return false;
        }
        $key_values = $this->getValue($key, $values, true);
        if (!is_array($key_values)) {
            return false;
        }
        $formatted_values = array_map(
            static fn($key, $value) => [
                'key'   => $key,
                'value' => $value,
            ],
            array_keys($key_values),
            array_values($key_values)
        );

        $key_value = $this->trimExplodeTrim($components[1], '->');
        if (count($key_value) === 1) {
            $key_name   = null;
            $value_name = $key_value[0];
        } else if (count($key_value) === 2) {
            $key_name   = $key_value[0];
            $value_name = $key_value[1];
        } else {
            return false;
        }

        return new LoopRule(
            $key,
            $key_name,
            $value_name,
            $formatted_values,
            0,
            count($formatted_values),
            $begin
        );
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>|false
     */
    public function iterateLoop(LoopRule $rule, array $values): array|false
    {
        $res = $rule->getNext();

        if ($res === false) {
            return false;
        }

        return array_merge($values, $res);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function hasValue(string $variable, array $values): bool
    {
        $keys = $this->trimExplodeTrim($variable, '.');

        $current = $values;
        foreach ($keys as $key) {
            /** @psalm-suppress MixedPropertyFetch */
            if (isset($current->{$key})) {
                /** @psalm-suppress MixedAssignment */
                $current = $current->{$key};
            } else if (isset($current[$key])) {
                /** @psalm-suppress MixedAssignment */
                $current = $current[$key];
            } else {
                $matches = [];
                if (preg_match('/^(.*)\[(.*)]$/', $key, $matches) === 1) {
                    $array_key = $matches[1];
                    $n         = $matches[2];
                    /** @psalm-suppress MixedArrayAccess */
                    if (is_array($current[$array_key]) && isset($current[$array_key][$n])) {
                        /** @psalm-suppress MixedAssignment */
                        $current = $current[$array_key][$n];
                    }
                } else {
                    return false;
                }
            }
        }

        return $current !== false && $current !== null;
    }

    /**
     * @param array<string, mixed> $values
     */
    private function getValue(string $variable, array $values, bool $array = false): string|array
    {
        $keys = $this->trimExplodeTrim($variable, '.');

        $current = $values;
        foreach ($keys as $key) {
            /** @psalm-suppress MixedPropertyFetch */
            if (isset($current->{$key})) {
                /** @psalm-suppress MixedAssignment */
                $current = $current->{$key};
            } else if (isset($current[$key])) {
                /** @psalm-suppress MixedAssignment */
                $current = $current[$key];
            } else {
                $matches = [];
                if (preg_match('/^(.*)\[(.*)]$/', $key, $matches) === 1) {
                    $array_key = $matches[1];
                    $n         = $matches[2];
                    /** @psalm-suppress MixedArrayAccess */
                    if (is_array($current[$array_key]) && isset($current[$array_key][$n])) {
                        /** @psalm-suppress MixedAssignment */
                        $current = $current[$array_key][$n];
                    }
                } else {
                    return $variable;
                }
            }
        }

        if ($array && is_array($current)) {
            return $current;
        }

        if (!is_scalar($current)) {
            return $variable;
        }

        return (string) $current;
    }

    /**
     * @param non-empty-string $separator
     * @return string[]
     */
    private function trimExplodeTrim(string $str, string $separator): array
    {
        return array_map('trim', explode($separator, trim($str)));
    }
}
