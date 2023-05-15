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

namespace Marmotte\Teng\Parsers\Rules;

final class LoopRule extends AbstractRule
{
    /**
     * @param array{
     *     key: mixed,
     *     value: mixed
     * }[] $values
     */
    public function __construct(
        public readonly string  $key,
        public readonly ?string $key_name,
        public readonly string  $value_name,
        public readonly array   $values,
        public int              $n,
        public int              $size,
        public readonly int     $begin,
    ) {
    }

    public function end(): bool
    {
        return $this->n === $this->size;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function getNext(): array|false
    {
        if ($this->end()) {
            return false;
        }

        $res = [$this->value_name => $this->values[$this->n]['value']];
        if ($this->key_name !== null) {
            /** @psalm-suppress MixedAssignment */
            $res[$this->key_name] = $this->values[$this->n]['key'];
        }
        $this->n++;

        return $res;
    }
}