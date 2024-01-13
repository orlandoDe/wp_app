<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace org\wplake\acf_views\vendors\Twig\Node\Expression\Binary;

use org\wplake\acf_views\vendors\Twig\Compiler;
class SpaceshipBinary extends AbstractBinary
{
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('<=>');
    }
}
