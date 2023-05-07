<?php
/**
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <https://unlicense.org>
 */

declare(strict_types=1);

namespace Marmotte\BrickName;

use Marmotte\Brick\Bricks\BrickLoader;
use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Mode;
use PHPUnit\Framework\TestCase;

class LoadBrickTest extends TestCase
{
    public function testBrickCanBeLoaded(): void
    {
        $brick_manager = new BrickManager();
        $brick_loader  = new BrickLoader(
            $brick_manager,
            new CacheManager(mode: Mode::TEST)
        );
        $brick_loader->loadFromDir(__DIR__ . '/../src');
        $_service_manager = $brick_manager->initialize(__DIR__ . '/../src', __DIR__ . '/../src');

        $bricks = $brick_manager->getBricks();
        self::assertCount(1, $bricks);
        $brick = $bricks[0];
        self::assertSame(MyBrick::class, $brick->brick->getName());
    }
}
