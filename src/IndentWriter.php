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

namespace Marmotte\Teng;

use Psr\Http\Message\StreamInterface;

final class IndentWriter
{
    private int $nb = 0;

    public function __construct(
        private readonly StreamInterface $stream,
        private readonly string          $indent = '    ',
    ) {
    }

    public function indent(): self
    {
        $this->nb++;

        return $this;
    }

    public function unindent(): self
    {
        $this->nb = --$this->nb < 0 ? 0 : $this->nb;

        return $this;
    }

    public function write(string $text): self
    {
        $this->stream->write($text);

        return $this;
    }

    public function writeIndent(string $text): self
    {
        return $this->write(
            str_repeat($this->indent, $this->nb) . $text
        );
    }

    public function getStream(): StreamInterface
    {
        $this->stream->rewind();

        return $this->stream;
    }
}
