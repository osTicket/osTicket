<?php

namespace osTicket\Twig\TokenParser;

use osTicket\Twig\Node\TransNode;

class TransTokenParser extends \Twig_TokenParser
{
    /**
     * @param  \Twig_Token $token
     * @return \Twig_NodeInterface
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $vars   = new \Twig_Node_Expression_Array(array(), $lineno);
        $locale = null;

        if (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
            if ($stream->test('with')) {
                $stream->next();
                $vars = $this->parser->getExpressionParser()->parseExpression();
            }

            if ($stream->test('into')) {
                $stream->next();
                $locale = $this->parser->getExpressionParser()->parseExpression();

            } elseif (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
                throw new \Twig_Error_Syntax('Unexpected token. Twig was looking for the "with" or "into" keyword.');
            }
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'decideTransFork'), true);

        if (!($body instanceof \Twig_Node_Text) and !($body instanceof \Twig_Node_Expression)) {
            throw new \Twig_Error_Syntax('A message inside a trans tag must be a simple text');
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TransNode($body, null, $vars, $locale, $lineno, $this->getTag());
    }

    /**
     * @param  \Twig_Token $token
     * @return boolean
     */
    public function decideTransFork(\Twig_Token $token)
    {
        return $token->test(array('endtrans'));
    }

    /**
     * @param string
     */
    public function getTag()
    {
        return 'trans';
    }
}
