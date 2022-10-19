<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Shopware\Components\Theme\Compiler;
use Shopware\Models\Shop;

class BlackHoleThemeCompiler extends Compiler
{
    public function compileLess($timestamp, Shop\Template $template, Shop\Shop $shop)
    {
        //nth
    }

    public function compileJavascript($timestamp, Shop\Template $template, Shop\Shop $shop)
    {
        //nth
    }
}
