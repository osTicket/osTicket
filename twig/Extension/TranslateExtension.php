<?php

namespace osTicket\Twig\Extension;

use osTicket\Twig\TokenParser\TransChoiceTokenParser;
use osTicket\Twig\TokenParser\TransTokenParser;

class TranslateExtension extends \Twig_Extension
{

    /**
     * @var array
     */
    protected $pluralRules = array();

    /**
     * @return string
     */
    public function getName()
    {
        return 'translate';
    }

    /**
     * @return array
     */
    public function getTokenParsers()
    {
        return array(
            new TransTokenParser(),
            new TransChoiceTokenParser(),
        );
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('trans', array($this, 'trans')),
            new \Twig_SimpleFilter('transchoice', array($this, 'transchoice'))
        );
    }

    /**
     * @param  string $msg
     * @param  array  $args
     * @param  string $locale
     * @return string
     */
    public function trans($msg, array $args = array(), $locale = null)
    {
        $msg = __($msg);

        // BC, rename all %s to %0$s.. etc
        preg_match_all('/%s/', $msg, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $i => $match) {
                $msg = preg_replace('/%s/', '%' . $i . '$s', $msg, 1);
            }
        }

        $msg  = strtr($msg, $args);

        return $msg;
    }

    /**
     * @param  string  $msg
     * @param  integer $count
     * @param  array   $args
     * @param  string  $locale
     * @return string
     */
    public function transchoice($msg, $count, array $args = array(), $locale = null)
    {
        $msg  = explode('|', $msg);
        $args = array_merge(array('count' => $count), $args);

        $rule   = $this->getPluralRule($locale);
        $plural = $rule($count);

        return $this->trans($msg[$plural], $args, $locale);
    }

    /**
     * @param  string   $locale
     * @param  callable $rule
     * @return void
     */
    public function setPluralRule($locale, $rule)
    {
        $locale = (null === $locale) ? '__default__' : $locale;

        $this->pluralRules[$locale] = $rule;
    }

    /**
     * @param  string $locale
     * @return callable
     */
    protected function getPluralRule($locale)
    {
        $locale = (null === $locale) ? '__default__' : $locale;

        if (!isset($this->pluralRules[$locale])) {
            if ('__default__' === $locale) {
                $this->pluralRules[$locale] = function ($count) {
                    return ($count == 1) ? 0 : 1;
                };
            } else {
                $this->pluralRules[$locale] = $this->getPluralRule('__default__');
            }
        }

        return $this->pluralRules[$locale];
    }

}
