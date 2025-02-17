<?php

namespace Laminas\Validator;

use Laminas\Stdlib\ArrayUtils;
use Laminas\Validator\Barcode\AdapterInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use Traversable;

use function assert;
use function class_exists;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;
use function strtolower;
use function substr;
use function ucfirst;

class Barcode extends AbstractValidator
{
    public const INVALID        = 'barcodeInvalid';
    public const FAILED         = 'barcodeFailed';
    public const INVALID_CHARS  = 'barcodeInvalidChars';
    public const INVALID_LENGTH = 'barcodeInvalidLength';

    /** @var array<string, string> */
    protected $messageTemplates = [
        self::FAILED         => 'The input failed checksum validation',
        self::INVALID_CHARS  => 'The input contains invalid characters',
        self::INVALID_LENGTH => 'The input should have a length of %length% characters',
        self::INVALID        => 'Invalid type given. String expected',
    ];

    /**
     * Additional variables available for validation failure messages
     *
     * @var array<string, array<string, string>>
     */
    protected $messageVariables = [
        'length' => ['options' => 'length'],
    ];
    /**
     * @var array{
     *     adapter: null|AdapterInterface,
     *     options: null|array<string, mixed>,
     *     length: null|int|array,
     *     useChecksum: null|bool,
     * }
     */
    protected $options = [
        'adapter'     => null, // Barcode adapter Laminas\Validator\Barcode\AbstractAdapter
        'options'     => null, // Options for this adapter
        'length'      => null,
        'useChecksum' => null,
    ];

    /**
     * Constructor for barcodes
     *
     * @param iterable<string, mixed>|null|string|AdapterInterface $options Options to use
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if ($options === null) {
            $options = [];
        }

        if (is_string($options) || $options instanceof AdapterInterface) {
            $options = ['adapter' => $options];
        }

        if (! is_array($options)) {
            throw new InvalidArgumentException(sprintf(
                'Options should be an array, a string representing the name of an adapter, or an adapter instance. '
                . 'Received "%s"',
                get_debug_type($options),
            ));
        }

        parent::__construct($options);
    }

    /**
     * Returns the set adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        if (! $this->options['adapter'] instanceof Barcode\AdapterInterface) {
            $this->setAdapter('Ean13');
        }

        assert($this->options['adapter'] instanceof Barcode\AdapterInterface);

        return $this->options['adapter'];
    }

    /**
     * Sets a new barcode adapter
     *
     * @param  string|AdapterInterface $adapter Barcode adapter to use
     * @param  array  $options Options for this adapter
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setAdapter($adapter, $options = null)
    {
        if (is_string($adapter)) {
            $adapter = ucfirst(strtolower($adapter));
            $adapter = 'Laminas\\Validator\\Barcode\\' . $adapter;

            if (! class_exists($adapter)) {
                throw new InvalidArgumentException('Barcode adapter matching "' . $adapter . '" not found');
            }

            $adapter = new $adapter($options);
        }

        if (! $adapter instanceof Barcode\AdapterInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Adapter %s does not implement Laminas\\Validator\\Barcode\\AdapterInterface',
                    get_debug_type($adapter)
                )
            );
        }

        $this->options['adapter'] = $adapter;

        return $this;
    }

    /**
     * Returns the checksum option
     *
     * @return string|null
     */
    public function getChecksum()
    {
        return $this->getAdapter()->getChecksum();
    }

    /**
     * Sets if checksum should be validated, if no value is given the actual setting is returned
     *
     * @param null|bool $checksum
     * @return AdapterInterface|bool
     */
    public function useChecksum($checksum = null)
    {
        return $this->getAdapter()->useChecksum($checksum);
    }

    /**
     * Defined by Laminas\Validator\ValidatorInterface
     *
     * Returns true if and only if $value contains a valid barcode
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (! is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);
        $adapter                 = $this->getAdapter();
        $this->options['length'] = $adapter->getLength();
        $result                  = $adapter->hasValidLength($value);
        if (! $result) {
            if (is_array($this->options['length'])) {
                $temp                    = $this->options['length'];
                $this->options['length'] = '';
                foreach ($temp as $length) {
                    $this->options['length'] .= '/';
                    $this->options['length'] .= $length;
                }

                $this->options['length'] = substr($this->options['length'], 1);
            }

            $this->error(self::INVALID_LENGTH);
            return false;
        }

        $result = $adapter->hasValidCharacters($value);
        if (! $result) {
            $this->error(self::INVALID_CHARS);
            return false;
        }

        if ($this->useChecksum(null)) {
            $result = $adapter->hasValidChecksum($value);
            if (! $result) {
                $this->error(self::FAILED);
                return false;
            }
        }

        return true;
    }
}
