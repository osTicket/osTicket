<?php

declare(strict_types=1);

namespace Laminas\ServiceManager\Tool;

use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;

use function array_filter;
use function array_map;
use function array_merge;
use function array_shift;
use function count;
use function implode;
use function preg_replace;
use function sort;
use function sprintf;
use function str_repeat;
use function strrpos;
use function substr;

class FactoryCreator
{
    public const FACTORY_TEMPLATE = <<<'EOT'
        <?php

        declare(strict_types=1);

        namespace %s;

        %s

        class %sFactory implements FactoryInterface
        {
            /**
             * @param ContainerInterface $container
             * @param string $requestedName
             * @param null|array $options
             * @return %s
             */
            public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
            {
                return new %s(%s);
            }
        }

        EOT;

    private const IMPORT_ALWAYS = [
        FactoryInterface::class,
        ContainerInterface::class,
    ];

    /**
     * @param string $className
     * @return string
     */
    public function createFactory($className)
    {
        $class = $this->getClassName($className);

        return sprintf(
            self::FACTORY_TEMPLATE,
            preg_replace('/\\\\' . $class . '$/', '', $className),
            $this->createImportStatements($className),
            $class,
            $class,
            $class,
            $this->createArgumentString($className)
        );
    }

    private function getClassName(string $className): string
    {
        return substr($className, strrpos($className, '\\') + 1);
    }

    /**
     * @param string $className
     * @return array
     */
    private function getConstructorParameters($className)
    {
        $reflectionClass = new ReflectionClass($className);

        if (! $reflectionClass->getConstructor()) {
            return [];
        }

        $constructorParameters = $reflectionClass->getConstructor()->getParameters();

        if (empty($constructorParameters)) {
            return [];
        }

        $constructorParameters = array_filter(
            $constructorParameters,
            function (ReflectionParameter $argument): bool {
                if ($argument->isOptional()) {
                    return false;
                }

                $type  = $argument->getType();
                $class = null !== $type && ! $type->isBuiltin() ? $type->getName() : null;

                if (null === $class) {
                    throw new InvalidArgumentException(sprintf(
                        'Cannot identify type for constructor argument "%s"; '
                        . 'no type hint, or non-class/interface type hint',
                        $argument->getName()
                    ));
                }

                return true;
            }
        );

        if (empty($constructorParameters)) {
            return [];
        }

        return array_map(function (ReflectionParameter $parameter): ?string {
            $type = $parameter->getType();
            return null !== $type && ! $type->isBuiltin() ? $type->getName() : null;
        }, $constructorParameters);
    }

    /**
     * @param string $className
     * @return string
     */
    private function createArgumentString($className)
    {
        $arguments = array_map(fn(string $dependency): string
            => sprintf('$container->get(\\%s::class)', $dependency), $this->getConstructorParameters($className));

        switch (count($arguments)) {
            case 0:
                return '';
            case 1:
                return array_shift($arguments);
            default:
                $argumentPad = str_repeat(' ', 12);
                $closePad    = str_repeat(' ', 8);
                return sprintf(
                    "\n%s%s\n%s",
                    $argumentPad,
                    implode(",\n" . $argumentPad, $arguments),
                    $closePad
                );
        }
    }

    private function createImportStatements(string $className): string
    {
        $imports = array_merge(self::IMPORT_ALWAYS, [$className]);
        sort($imports);
        return implode("\n", array_map(static fn(string $import): string => sprintf('use %s;', $import), $imports));
    }
}
