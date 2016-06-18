<?php

namespace osTicket\Twig\TokenParser;

use osTicket\Twig\Node\TransNode;

class TransChoiceTokenParser extends \Twig_TokenParser
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
        $count  = $this->parser->getExpressionParser()->parseExpression();
        $locale = null;

        if ($stream->test('with')) {
            $stream->next();
            $vars = $this->parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test('into')) {
            $stream->next();
            $locale = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse(array($this, 'decideTransChoiceFork'), true);

        if (!($body instanceof \Twig_Node_Text) and !($body instanceof \Twig_Node_Expression)) {
            throw new \Twig_Error_Syntax('A message must be a simple text.');
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TransNode($body, $count, $vars, $locale, $lineno, $this->getTag());
    }

    /**
     * @param  \Twig_Token $token
     * @return boolean
     */
    public function decideTransChoiceFork(\Twig_Token $token)
    {
        return $token->test(array('endtranschoice'));
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return 'transchoice';
    }
}
