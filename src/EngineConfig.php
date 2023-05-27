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

use Marmotte\Brick\Config\ServiceConfig;

final class EngineConfig extends ServiceConfig
{
    public function __construct(
        private readonly string $template_dir,
        private readonly string $asset_dir,
    ) {
    }

    /**
     * @return string
     */
    public function getTemplateDir(): string
    {
        return $this->project_root . '/' . $this->template_dir;
    }

    /**
     * @return string
     */
    public function getAssetDir(): string
    {
        return $this->asset_dir;
    }

    public static function fromArray(array $array): ServiceConfig
    {
        $defaults = self::defaultArray();

        if (array_key_exists('template_dir', $array) && is_string($array['template_dir'])) {
            $template_dir = $array['template_dir'];
        } else {
            $template_dir = $defaults['template_dir'];
        }

        if (array_key_exists('asset_dir', $array) && is_string($array['asset_dir'])) {
            $asset_dir = $array['asset_dir'];
        } else {
            $asset_dir = $defaults['asset_dir'];
        }

        return new self(
            $template_dir,
            $asset_dir
        );
    }

    /**
     * @return array{
     *     template_dir: string,
     *     asset_dir: string
     * }
     */
    public static function defaultArray(): array
    {
        return [
            'template_dir' => '',
            'asset_dir'    => '',
        ];
    }
}
