#! /usr/bin/php
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

use Marmotte\Brick\Bricks\BrickLoader;
use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Mode;
use Marmotte\Teng\Engine;
use function Psl\Json\decode as psl_json_decode;

require_once __DIR__ . '/../../vendor/autoload.php';

// Setup
$brick_manager = new BrickManager();
$brick_loader  = new BrickLoader(
    $brick_manager,
    new CacheManager(mode: Mode::TEST)
);
try {
    $brick_loader->loadFromDir(__DIR__ . '/../../src', 'marmotte/teng');
    $brick_loader->loadBricks();
    $service_manager = $brick_manager->initialize(__DIR__ . '/tests', __DIR__);
} catch (\Throwable $e) {
    echo "\033[31m" . $e->getMessage() . "\033[0m\n";
    exit($e->getCode());
}

if (!$service_manager->hasService(Engine::class)) {
    echo "\033[31m" . 'Fail to load Engine Service' . "\033[0m\n";
    exit(1);
}

$engine = $service_manager->getService(Engine::class);
assert($engine !== null);
$engine->addFunction('strong', static fn(string $str) => "<strong>$str</strong>");
$engine->addFunction('get42', static fn() => 42);
$engine->addFunction('concatenate', static fn(string $str1, string $str2) => $str1 . $str2);

$tests = array_filter(
    scandir(__DIR__ . '/tests'),
    static fn(string $file) => $file !== '.' && $file !== '..'
);

// _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

$generated = [];
foreach ($tests as $test) {
    $expect = __DIR__ . '/expects/' . $test . '.expect';
    $values = __DIR__ . '/values/' . $test . '.values';
    if (!file_exists($expect)) {
        try {
            /** @var array<string, mixed> */
            $values = file_exists($values) ? psl_json_decode(file_get_contents($values)) : [];
            $result = $engine->render($test, $values);
            file_put_contents($expect, (string) $result);
            $generated[] = $expect;
        } catch (Throwable) {
            // Ignore
        }
    }
}

// _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.

if (empty($generated)) {
    echo "No expects generated\n";
} else {
    echo "Please check these files:\n";
    foreach ($generated as $g) {
        echo ' - ' . $g . "\n";
    }
}
