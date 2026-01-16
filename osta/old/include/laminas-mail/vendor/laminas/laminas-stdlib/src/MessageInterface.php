<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

use Traversable;

interface MessageInterface
{
    /**
     * Set metadata
     *
     * @param string|int|array|Traversable $spec
     * @param  mixed $value
     */
    public function setMetadata($spec, $value = null);

    /**
     * Get metadata
     *
     * @param  null|string|int $key
     * @return mixed
     */
    public function getMetadata($key = null);

    /**
     * Set content
     *
     * @param  mixed $content
     * @return mixed
     */
    public function setContent($content);

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent();
}
