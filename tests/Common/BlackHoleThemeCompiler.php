<?php

declare(strict_types=1);

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
