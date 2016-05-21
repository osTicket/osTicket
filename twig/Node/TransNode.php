<?php

namespace osTicket\Twig\Node;

class TransNode extends \Twig_Node
{

    /**
     * @param \Twig_NodeInterface   $body
     * @param \Twig_Node_Expression $count
     * @param \Twig_Node_Expression $vars
     * @param \Twig_Node_Expression $locale
     * @param integer              $lineno
     * @param string               $tag
     */
    public function __construct(\Twig_NodeInterface $body, \Twig_Node_Expression $count = null, \Twig_Node_Expression $vars = null, \Twig_Node_Expression $locale = null, $lineno = 0, $tag = null)
    {
        parent::__construct(array('body' => $body, 'count' => $count, 'vars' => $vars, 'locale' => $locale), array(), $lineno, $tag);
    }

    /**
     * @param \Twig_Compiler $compiler
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $vars     = $this->getNode('vars');
        $defaults = new \Twig_Node_Expression_Array(array(), -1);

        if ($vars instanceof \Twig_Node_Expression_Array) {
            $defaults = $this->getNode('vars');
            $vars     = null;
        }

        list($msg, $defaults) = $this->compileString($this->getNode('body'), $defaults);

        $method = (null === $this->getNode('count')) ? 'trans' : 'transchoice';

        $compiler
            ->write('echo $this->env->getExtension(\'translate\')->' . $method . '(')
            ->subcompile($msg);

        $compiler->raw(', ');

        if (null !== $this->getNode('count')) {
            $compiler
                ->subcompile($this->getNode('count'))
                ->raw(', ');
        }

        if (null !== $vars) {
            $compiler
                ->raw('array_merge(')
                ->subcompile($defaults)
                ->raw(', ')
                ->subcompile($this->getNode('vars'))
                ->raw(')');
        } else {
            $compiler->subcompile($defaults);
        }

        if (null !== $this->getNode('locale')) {
            $compiler
                ->raw(', ')
                ->subcompile($this->getNode('locale'));
        }

        $compiler->raw(");\n");
    }

    /**
     * @param  \Twig_NodeInterface         $body
     * @param  \Twig_Node_Expression_Array $vars
     * @return array
     */
    protected function compileString(\Twig_NodeInterface $body, \Twig_Node_Expression_Array $vars)
    {
        if ($body instanceof \Twig_Node_Expression_Constant) {
            $msg = $body->getAttribute('value');
        } elseif ($body instanceof \Twig_Node_Text) {
            $msg = $body->getAttribute('data');
        } else {
            return array($body, $vars);
        }

        return array(new \Twig_Node_Expression_Constant(str_replace('%%', '%', trim($msg)), $body->getLine()), $vars);
    }

}
