<?php
/*********************************************************************
    class.avatar.php

    Avatar sources for users and agents

    Jared Hancock <jared@osticket.com>
    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2015 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

abstract class Avatar {
    var $user;

    function __construct($user) {
        $this->user = $user;
    }

    abstract function getUrl($size);

    function getImageTag($size=null) {
        $style = ($size)
            ? sprintf('style="max-height:%spx"', $size)
            : '';
        return "<img {$style} class=\"avatar\" alt=\""
            .__('Avatar').'" src="'.$this->getUrl($size).'" />';
    }

    function __toString() {
        return $this->getImageTag();
    }

    function isChangeable() {
        return false;
    }
    function toggle() {}
}

abstract class AvatarSource {
    static $id;
    static $name;
    var $mode;

    function __construct($mode=null) {
        if (isset($mode))
            $this->mode = $mode;
    }

    static function getName() {
        return __(static::$name);
    }

    abstract function getAvatar($user);

    static $registry = array();
    static function register($class) {
        if (!class_exists($class))
            throw new Exception($class.': Does not exist');
        if (!isset($class::$id))
            throw new Exception($class.': AvatarClass must specify $id');
        static::$registry[$class::$id] = $class;
    }

    static function lookup($id, $mode=null) {
        $class = static::$registry[$id];
        if (!isset($class))
            ; // TODO: Return built-in avatar source
        if (is_string($class))
            $class = static::$registry[$id] = new $class($mode);
        return $class;
    }

    static function allSources() {
        return static::$registry;
    }

    static function getModes() {
        return null;
    }
}

class LocalAvatarSource
extends AvatarSource {
    static $id = 'local';
    static $name = /* @trans */ 'Built-In';
    var $mode = 'ateam';

    static function getModes() {
        return array(
            'ateam' => __("Oscar's A-Team"),
        );
    }

    function getAvatar($user) {
        return new LocalAvatar($user, $this->mode);
    }
}
AvatarSource::register('LocalAvatarSource');

class LocalAvatar
extends Avatar {
    var $mode;
    var $code;
    var $size;

    function __construct($user, $mode) {
        parent::__construct($user);
        $this->mode = $mode;
    }

    function setSize($size) {
        $this->size = $size;
    }

    function getUrl($size) {
        $code = $this->code;
        if (!$code && method_exists($this->user, 'getExtraAttr'))
            $code = $this->user->getExtraAttr('avatar');

        if ($code)
            $uid = md5($code);
        else
            // Generate a random string of 0-6 chars for the avatar signature
            $uid = md5(strtolower($this->user->getEmail()));

        $args = array('uid'=>$uid, 'mode' => $this->mode);

        if ($this->size)
            $args['size'] = $this->size;

        return ROOT_PATH . 'avatar.php?' . Http::build_query($args);
    }

    function toggle() {
        $this->code = Misc::randCode(21);
        return $this->code;
    }

    function isChangeable() {
        return true;
    }
}

class RandomAvatar {
    var $mode;

    static $sprites = array(
        'ateam' => array(
            'file' => 'images/avatar-sprite-ateam.png',
            'grid' => 96,
        ),
    );

    function __construct($mode) {
        $this->mode = $mode;
    }

    function makeAvatar($uid, $size=null) {
        $sprite = self::$sprites[$this->mode];
        if (!$sprite || !is_readable(ROOT_DIR . $sprite['file']) || !extension_loaded('gd'))
            Http::redirect(ROOT_PATH.'images/mystery-oscar.png');

        $source =  imagecreatefrompng(ROOT_DIR . $sprite['file']);
        $grid = $sprite['grid'];
        $avatar = imagecreatetruecolor($grid, $grid);
        $width = imagesx($source) / $grid;
        $height = imagesy($source) / $grid;

        // Start with a white matte
        $white = imagecolorallocate($avatar, 255, 255, 255);
        imagefill($avatar, 0, 0, $white);

        for ($i=0, $k=$height; $i<$k; $i++) {
            $idx = hexdec($uid[$i]) % $width;
            imagecopy($avatar, $source, 0, 0, $idx*$grid, $i*$grid, $grid, $grid);
        }

        // Resize the avatar (if applicable)
        if ($size) {
            $avatar = imagescale($avatar, $size, $size);
        }

        return $avatar;
    }
}

class AvatarsByGravatar
extends AvatarSource {
    static $name = 'Gravatar';
    static $id = 'gravatar';
    var $mode;

    function __construct($mode=null) {
        $this->mode = $mode ?: 'retro';
    }

    static function getModes() {
        return array(
            'mm' => __('Mystery Man'),
            'identicon' => 'Identicon',
            'monsterid' => 'Monster',
            'wavatar' => 'Wavatar',
            'retro' => 'Retro',
        );
    }

    function getAvatar($user) {
        return new Gravatar($user, $this->mode);
    }
}
AvatarSource::register('AvatarsByGravatar');

class Gravatar
extends Avatar {
    var $email;
    var $d;
    var $size;

    function __construct($user, $imageset) {
        $this->email = $user->getEmail();
        $this->d = $imageset;
    }

    function setSize($size) {
        $this->size = $size;
    }

    /**
     * Get either a Gravatar URL or complete image tag for a specified email address.
     *
     * @param string $email The email address
     * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
     * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
     * @param boole $img True to return a complete IMG tag False for just the URL
     * @param array $atts Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source http://gravatar.com/site/implement/images/php/
     */
    function getUrl($size=null) {
        $size = $this->size ?: 80;
        $url = '//www.gravatar.com/avatar/';
        $url .= md5( strtolower( $this->email ) );
        $url .= "?s=$size&d={$this->d}";
        return $url;
    }
}
