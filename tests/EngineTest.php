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

use Marmotte\Brick\Bricks\BrickLoader;
use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Mode;
use PHPUnit\Framework\TestCase;
use function Marmotte\Teng\Fixtures\getFunctions;
use function Psl\Json\decode as psl_json_decode;

class EngineTest extends TestCase
{
    public static Engine $engine;

    public static function setUpBeforeClass(): void
    {
        $brick_manager = new BrickManager();
        $brick_loader  = new BrickLoader(
            $brick_manager,
            new CacheManager(mode: Mode::TEST)
        );
        try {
            $brick_loader->loadFromDir(__DIR__ . '/../src', 'marmotte/teng');
            $brick_loader->loadBricks();
            $service_manager = $brick_manager->initialize(__DIR__ . '/Fixtures', __DIR__ . '/Fixtures');
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertNotNull($brick_manager->getBrick('marmotte/teng'));
        self::assertNotNull($brick_manager->getBrick('marmotte/http'));

        self::assertTrue($service_manager->hasService(Engine::class));

        self::$engine = $service_manager->getService(Engine::class);
        self::assertNotNull(self::$engine);
        assert(self::$engine !== null); // Mystery: self::assertNotNull is not enough for psalm
        self::$engine->addFunction('strong', static fn(string $str) => "<strong>$str</strong>");
        self::$engine->addFunction('get42', static fn() => 42);
        self::$engine->addFunction('concatenate', static fn(string $str1, string $str2) => $str1 . $str2);
    }

    /**
     * @param array<string, mixed> $values
     * @dataProvider dataTestRender
     */
    public function testRender(string $filename, string $expect, array $values): void
    {
        try {
            $result = self::$engine->render($filename, $values);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertSame(file_get_contents($expect), $result->getContents());
    }

    public function testCanRenderAbsolutePath(): void
    {
        try {
            $result = self::$engine->render(__DIR__ . '/Fixtures/tests/empty.html.teng');
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertEmpty($result->getContents());
    }

    public static function dataTestRender(): iterable
    {
        foreach (array_filter(
                     scandir(__DIR__ . '/Fixtures/tests'),
                     static fn(string $file) => $file !== '.' && $file !== '..'
                 ) as $test) {
            $values = __DIR__ . '/Fixtures/values/' . $test . '.values';

            yield $test => [
                'filename' => 'tests/' . $test,
                'expect'   => __DIR__ . '/Fixtures/expects/' . $test . '.expect',
                'values'   => file_exists($values) ? psl_json_decode(file_get_contents($values)) : [],
            ];
        }
    }
}
