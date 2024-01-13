<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace org\wplake\acf_views\vendors\Twig\TokenParser;

use org\wplake\acf_views\vendors\Twig\Node\FlushNode;
use org\wplake\acf_views\vendors\Twig\Node\Node;
use org\wplake\acf_views\vendors\Twig\Token;
/**
 * Flushes the output to the client.
 *
 * @see flush()
 *
 * @internal
 */
final class FlushTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $this->parser->getStream()->expect(
            /* Token::BLOCK_END_TYPE */
            3
        );
        return new FlushNode($token->getLine(), $this->getTag());
    }
    public function getTag() : string
    {
        return 'flush';
    }
}
