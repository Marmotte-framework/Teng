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

use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Services\Service;
use Marmotte\Http\Stream\StreamException;
use Marmotte\Http\Stream\StreamFactory;
use Marmotte\Teng\Exceptions\FunctionExistsException;
use Marmotte\Teng\Exceptions\NotHandledFileTypeException;
use Marmotte\Teng\Exceptions\TemplateNotFoundException;
use Marmotte\Teng\Parsers\HTMLParser;
use Marmotte\Teng\Parsers\MarkdownParser;
use Psr\Http\Message\StreamInterface;

#[Service('mdgen.yml')]
final class Engine
{
    private const CACHE_DIR = 'mdgen-templates';

    /**
     * @var array<string, callable|array{object, string}>
     */
    private array $functions = [];

    public function __construct(
        private readonly EngineConfig  $config,
        private readonly CacheManager  $cache_manager,
        private readonly StreamFactory $stream_factory,
    ) {
    }

    /**
     * @param string $template Name of the template, relative to template root
     * @param array<string, mixed> $values Values of variables used in template
     * @throws StreamException
     * @throws TemplateNotFoundException
     * @throws NotHandledFileTypeException
     */
    public function render(string $template, array $values = []): StreamInterface
    {
        if (str_starts_with($template, '/')) {
            $filename = $template;
        } else {
            $filename = $this->config->getTemplateDir() . '/' . $template;
        }
        if (!file_exists($filename)) {
            throw new TemplateNotFoundException($filename);
        }

        if ($this->cache_manager->exists(self::CACHE_DIR, $template)) {
            /** @var string $render_result */
            $render_result = $this->cache_manager->load(self::CACHE_DIR, $template);

            return $this->stream_factory->createStream($render_result);
        }

        $content       = file_get_contents($filename);
        $writer        = new IndentWriter($this->stream_factory->createStream(''));
        $render_result = match ($this->getFileType($filename)) {
            'html' => (new HTMLParser($writer, $this->functions))->parse($content, $values),
            'md'   => (new MarkdownParser($writer, $this->functions))->parse($content, $values),
            null   => throw new NotHandledFileTypeException($filename)
        };

        return $this->stream_factory->createStream($render_result);
    }

    /**
     * @param string $name
     * @param callable|array{object, string} $function
     * @throws FunctionExistsException
     */
    public function addFunction(string $name, callable|array $function): void
    {
        if (array_key_exists($name, $this->functions)) {
            throw new FunctionExistsException($name);
        }

        $this->functions[$name] = $function;
    }

    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

    private function getFileType(string $filename): ?string
    {
        if (str_ends_with($filename, '.html.teng'))
            return 'html';
        if (str_ends_with($filename, '.md.teng'))
            return 'md';

        return null;
    }
}
