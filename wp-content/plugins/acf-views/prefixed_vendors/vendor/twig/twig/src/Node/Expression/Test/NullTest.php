<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace org\wplake\acf_views\vendors\Twig\Node\Expression\Test;

use org\wplake\acf_views\vendors\Twig\Compiler;
use org\wplake\acf_views\vendors\Twig\Node\Expression\TestExpression;
/**
 * Checks that a variable is null.
 *
 *  {{ var is none }}
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class NullTest extends TestExpression
{
    public function compile(Compiler $compiler) : void
    {
        $compiler->raw('(null === ')->subcompile($this->getNode('node'))->raw(')');
    }
}
