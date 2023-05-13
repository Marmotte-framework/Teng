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

class EngineTest extends TestCase
{
    public static Engine $engine;

    public static function setUpBeforeClass(): void
    {
        $brick_manager = new BrickManager();
        $brick_loader = new BrickLoader(
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
    }

    /**
     * @dataProvider dataTestRender
     */
    public function testRender(string $filename, string $expect): void
    {
        try {
            $result = self::$engine->render($filename);
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }

        self::assertSame(file_get_contents($expect), $result->getContents());
    }

    public static function dataTestRender(): iterable
    {
        foreach (array_filter(
                     scandir(__DIR__ . '/Fixtures/tests'),
                     static fn(string $file) => $file !== '.' && $file !== '..'
                 ) as $test) {
            yield $test => [
                'filename' => 'tests/' . $test,
                'expect' => __DIR__ . '/Fixtures/expects/' . $test . '.expect',
            ];
        }
    }
}
