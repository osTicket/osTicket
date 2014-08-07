<?php

class HashBlock {
    var $hash;

    var $original;
    var $offset;

    function __construct($original, $offset=null) {
        $this->original = $original;
        $this->offset = $offset;
    }

    function join(HashBlock $block) {
        // These are assumed to be neighbors
        return new HashBlock($this->original . $block->original, $this->offset);
    }

    function getHash($length=3) {
        if (!isset($this->hash))
            $this->hash = base64_encode(substr(md5($this->original, true), -$length));

        return $this->hash;
    }

    function getLength() {
        return strlen($this->original);
    }
}

class FuzzyDiff {
    const ADDED     = 0x100;
    const PREPENDED = 0x101;
    const APPENDED  = 0x102;
    const INSERTED  = 0x103;
    const MODIFIED  = 0x104;
    const DELETED   = 0x005;
    const COPIED    = 0x006;    // No difference

    var $disposition;
    var $blocks;

    function __construct($block, $disposition) {
        $this->blocks = array($block);
        $this->disposition = $disposition;
    }

    function merge(FuzzyDiff $diff) {
        if (!$this->disposition == $diff->disposition)
            throw new Exception('Cannot merge diffs with differing dispositions');
        $this->blocks = array_merge($this->blocks, $diff->blocks);
        return $this;
    }

    function getOffset() {
        return $this->blocks[0]->offset;
    }

    function getLength() {
        foreach ($this->blocks as $B);
        return $B->offset - $this->blocks[0]->offset + $B->getLength();
    }
}

class FuzzyDiffList extends ArrayObject {
    function analyze() {
        $this->compress();

        // TODO: Correlate changes based on proximity of ADDED, DELETED, and
        //       COPIED differences

        $copy = array();
        $prev = $left = null;
        $reversed = array_reverse($this->getArrayCopy());
        for (;;) {
            $right = $left;
            if (!(list($i, $left) = each($reversed)))
                break;
            if (!$right)
                continue;

            // Walk the two lists backwards, looking at the blocks offset
            // like this:
            // [E, D, C, B, A] => $right
            // [D, C, B, A] => $left
            //
            // ** $left will preceed $right in the list

            // ADD + DELETE => MODIFIED
            if ($left->disposition == FuzzyDiff::ADDED
                && $right->disposition == FuzzyDiff::DELETED
            ) {
                $left->disposition = FuzzyDiff::MODIFIED;
                // Drop the DELETED block
                continue;
            }
            // COPIED + ADDED => INSERTED
            elseif ($left->disposition == FuzzyDiff::COPIED
                && $right->disposition == FuzzyDiff::ADDED
            ) {
                $right->disposition = FuzzyDiff::INSERTED;
            }
            $copy[] = $right;
        }
        $copy[] = $right;
        $this->exchangeArray(array_reverse($copy));
    }

    /**
     * Function: compress
     *
     * Consolidates adjacent blocks with the same disposition. Normally,
     * there will be several adjacent diffs with a disposition of COPIED,
     * which means the text was in the original was copied between the two
     * texts.
     *
     * This method will consolidate all the neighboring COPIED blocks into
     * one diff with several blocks.
     */
    function compress() {
        $current = null;
        while (list($i, $B) = each($this)) {
            if ($current && $current->disposition == $B->disposition) {
                $current->merge($B);
                unset($this[$i]);
            }
            else {
                $current = $B;
            }
        }
    }

    function relevance() {
        $this->analyze();

        // Determine a score based on the number and size of the
        // COPIED blocks when compared to the other blocks.
        $copied = 0;
        foreach ($this as $D) {
            if ($D->disposition == FuzzyDiff::COPIED)
                $copied++;
        }
        return $copied / count($this);
    }
}

class FuzzyHash {
    var $blocks=array();
    var $length=3;
    var $original;

    function compareTo($hash) {
        $base64_length = (int)(($this->length * 4) / 3);
        if (is_string($hash))
            $foreign = str_split($hash, $base64_length);
        elseif ($hash instanceof FuzzyHash)
            ; // Punt
        else
            throw new InvalidArgumentException('Unexpected hash type');

        $diffs = new FuzzyDiffList();
        foreach ($this->blocks as $k=>$B) {
            $h = $B->getHash($this->length);
            if (!in_array($h, $foreign)) {
                // Last chance check. See if adding the next hash bucket
                // content to this buck would help make a match
                if (!isset($this->blocks[$k+1])) {
                    // Definitely not in the foreign hash — added locally
                    $diffs[] = new FuzzyDiff($B, FuzzyDiff::ADDED);
                    continue;
                }
                $B2 = $B->join($this->blocks[$k+1]);
                $h2 = $B2->getHash($this->length);
                if (!in_array($h2, $foreign)) {
                    // Definitely not in the foreign hash
                    $diffs[] = new FuzzyDiff($B, FuzzyDiff::ADDED);
                    continue;
                }
                // Second chance match. It's in the original
                $h = $h2;
                $B = $B2;

                // Fallthrough to common logic below
            }
            // This hash is in the list (match).

            // Drop all hashes between the current location and the
            // matched hash from the foreign list
            while (($del = array_shift($foreign)) != $h)
                $diffs[] = new FuzzyDiff($del, FuzzyDiff::DELETED);

            // Indicate the copied blocks in the diff list
            $diffs[] = new FuzzyDiff($B, FuzzyDiff::COPIED);
        }
        return $diffs;
    }

    /**
     * Use the digest (string value) from another fuzzy hash to remove data
     * from the original text used to create this hash.
     *
     * Parameters:
     * $hash - (string) digest value from a FuzzyHash object. @see
     *      ::toString()
     * $window - (int|default:0) number of sections to keep from a removed
     *      section preceeding a kept section. In other words, if text is to
     *      be removed between two kept sections, keep this number of blocks
     *      at the end of the section to-be-removed.
     */
    function remove($hash, $window=0) {
        $diffs = $this->compareTo($hash);
        $diffs->analyze();

        $cleaned = '';
        foreach ($diffs as $D) {
            // XXX: It would be cleaner to drop the COPIED segments
            if ($D->disposition != FuzzyDiff::COPIED) {
                $cleaned .= substr($this->original, $D->getOffset(), $D->getLength());
            }
        }
        return $cleaned;
    }

    function toString() {
        $hashes = '';
        foreach ($this->blocks as $B)
            $hashes .= $B->getHash($this->length);

        return $hashes;
    }
    function __toString() {
        return $this->toString();
    }

    static function fromTokens(Tokens $tokens, $length=3) {
        $fuzzy = new static();
        do {
            $block = null;
            do {
                list($i, $next) = each($tokens);
                if (!$next)
                    break;
                elseif (!isset($block))
                    $block = $next;
                else
                    $block = $block->join($next);
            // Ensure that block meets minimum length
            } while ($block->getLength() < $length);
            if ($block)
                $fuzzy->blocks[] = $block;
        } while ($next);
        $fuzzy->original = $tokens->original;
        $fuzzy->tokens = $tokens;
        $fuzzy->length = $length;
        return $fuzzy;
    }
}

class AdaptiveFuzzyHash {
    var $hashes = array();

    function addText($text) {
        $tokens = new TextTokenizer();
        return $this->addTokens($tokens->load($text));
    }

    function addHtml($html, $flags=0) {
        $tokens = new HtmlTokenizer();
        $this->addHash(HtmlFuzzyHash::fromTokens(
            $tokens->load($html, $flags)));
    }

    function addTokens(Tokens $tokens) {
        $this->hashes[] = FuzzyHash::fromTokens($tokens);
    }

    function addHash(FuzzyHash $hash) {
        $this->hashes[] = $hash;
    }

    function remove(array $hashList) {
        $best = null;
        foreach ($hashList as $foreign) {
            foreach ($this->hashes as $local) {
                $diffs = $local->compareTo($foreign);
                $score = $diffs->relevance();
                if (!isset($best) || $best[0] < $score)
                    $best = array($score, $local, $foreign);
            }
        }
        list(, $local, $foreign) = $best;
        return $local->remove((string) $foreign);
    }
}

abstract class Tokens extends ArrayObject {
    var $original = '';
}

class TextTokenizer extends Tokens {
    function load($text, $regex='`\n|>`u') {
        // TODO: Standardize line endings in $text
        $text = str_replace("\r", "", $text);
        $sections = preg_split($regex, $text, -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
        foreach ($sections as $s) {
            if (!($trimmed = trim($s[0])))
                continue;
            $this[] = new HashBlock($trimmed, max(0, $s[1]));
        }
        $this->original .= $text;
        return $this;
    }
}

class HtmlFuzzyHash extends FuzzyHash {

    private function _remove($node) {
        $p = $node->parentNode;

        // Drop neighboring <br/> elements
        $drop = array('img'=>1, 'br'=>1, 'hr'=>1);
        if ($node->previousSibling) {
            if (isset($drop[$node->previousSibling->nodeName]))
                $p->removeChild($node->previousSibling);
        }
        $next = $node->nextSibling;
        while ($next) {
            $current = $next;
            $next = $current->nextSibling;
            if (isset($drop[$current->nodeName])
                    // Whitespace DOMText node
                    || ($current->nodeType == XML_TEXT_NODE && !trim($current->wholeText))) {
                $p->removeChild($current);
            }
            else
                break;
        }
        // Drop the text content
        $p->removeChild($node);

        // Drop elements without any text content
        while (!($T = trim($p->textContent)) || preg_match('`<br\s*/?\>`', $T)) {
            $q = $p->parentNode;
            $q->removeChild($p);
            $p = $q;
        }
    }

    function remove($hash, $window=0) {
        $diffs = $this->compareTo($hash);
        $diffs->analyze();

        $doc = $this->tokens->doc;
        foreach ($diffs as $D) {
            if ($D->disposition == FuzzyDiff::COPIED) {
                foreach ($D->blocks as $B) {
                    foreach ($B->nodes as $N)
                        // Remove it from the original doc
                        $this->_remove($N);
                }
            }
        }

        // Remove empty nodes
        $xpath = new DOMXPath($this->tokens->doc);
        static $eE = array('area'=>1, 'br'=>1, 'col'=>1, 'embed'=>1,
            'hr'=>1, 'img'=>1, 'input'=>1, 'isindex'=>1, 'param'=>1);
        do {
            $done = true;
            $nodes = $xpath->query('//*[not(text()) and not(node())]');
            foreach ($nodes as $n) {
                if (isset($eE[$n->nodeName]))
                    continue;
                $n->parentNode->removeChild($n);
                $done = false;
            }
        } while (!$done);

        return $this->tokens->doc->saveHTML();
        include_once 'class.format.php';
        define('INCLUDE_DIR', dirname(__file__).'/');
        return Format::safe_html($this->tokens->doc->saveHTML());

    }
}

class HtmlHashBlock extends HashBlock {
    var $nodes;

    function __construct($original, $node) {
        parent::__construct($original);
        $this->nodes = array($node);
    }

    function join(HashBlock $block) {
        // These are assumed to be neighbors
        $nodes = array_merge($this->nodes, $block->nodes);
        return new static($this->original . $block->original, $nodes);
    }
}

class HtmlTokenizer extends Tokens {
    const FLAG_SAFE_HTML = 1;

    function tokenizeHtml($html) {
        $tokens = new TextTokenizer();
        return $tokens->load($html, '`<[^>]+>`');
    }

    function load($html, $flags=0) {
        // Make $html consistent by calling sanitize()
        if (!$flags & self::FLAG_SAFE_HTML)
            $html = Format::safe_html($html);

        if (!extension_loaded('xml'))
            return self::tokenizeHtml($html);

        $this->doc = new DOMDocument('1.0', 'utf-8');
        if (strpos($html, '<?xml ') === false)
            $html = '<?xml encoding="utf-8"?>'.$html; # <?php (4vim)
        if (!@$this->doc->loadHTML($html))
            return self::tokenizeHtml($html);

        $this->original = $html;
        foreach ($this->doc->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $this->doc->removeChild($item); // remove hack
                break;
            }
        }

        // Find text segments
        $nodes = array();
        $this->getTextNodes($this->doc, $nodes);
        foreach ($nodes as $i=>$node) {
            $text = trim($node->wholeText);
            if (!$text)
                continue;
            $this[] = new HtmlHashBlock($text, $node);
        }
        return $this;
    }

    private function getTextNodes($node, &$texts=array()) {
        if ($node->nodeType == XML_TEXT_NODE) {
            $texts[] = $node;
        }
        elseif ($node->hasChildNodes()) {
            foreach ($node->childNodes as $n)
                $this->getTextNodes($n, $texts);
        }
    }
}
