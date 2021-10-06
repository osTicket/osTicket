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
        $avatar="data:image/png;base64,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";

        if(isset($this->user->avatar_data)){
            $avatar=$this->user->avatar_data;
        }
        $style = ($size)
            ? sprintf('style="max-height:%spx"', $size)
            : '';
        return "<img {$style} id=\"imagenAvatar\" class=\"avatar\" alt=\""
            .__('Avatar').'" src="'.$avatar.'" / >';
    }

    function __toString() {
        return $this->getImageTag();
    }
    function setAvatar($avatar){
        $this->user->avatar_data=$avatar;
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

    function getName() {
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
