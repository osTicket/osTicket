<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Exception;

use DomainException;

use function sprintf;

class ContainerModificationsNotAllowedException extends DomainException implements ExceptionInterface
{
    /**
     * @param string $service Name of service that already exists.
     */
    public static function fromExistingService(string $service): self
    {
        return new self(sprintf(
            'The container does not allow replacing or updating a service'
            . ' with existing instances; the following service'
            . ' already exists in the container: %s',
            $service
        ));
    }
}
