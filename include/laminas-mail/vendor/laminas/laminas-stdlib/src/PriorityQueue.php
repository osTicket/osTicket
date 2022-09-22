<?php

declare(strict_types=1);

namespace Laminas\Stdlib;

use Countable;
use IteratorAggregate;
use ReturnTypeWillChange;
use Serializable;
use UnexpectedValueException;

use function array_map;
use function count;
use function get_class;
use function is_array;
use function serialize;
use function sprintf;
use function unserialize;

/**
 * Re-usable, serializable priority queue implementation
 *
 * SplPriorityQueue acts as a heap; on iteration, each item is removed from the
 * queue. If you wish to re-use such a queue, you need to clone it first. This
 * makes for some interesting issues if you wish to delete items from the queue,
 * or, as already stated, iterate over it multiple times.
 *
 * This class aggregates items for the queue itself, but also composes an
 * "inner" iterator in the form of an SplPriorityQueue object for performing
 * the actual iteration.
 *
 * @template TValue
 * @template TPriority of int
 * @implements IteratorAggregate<array-key, TValue>
 */
class PriorityQueue implements Countable, IteratorAggregate, Serializable
{
    public const EXTR_DATA     = 0x00000001;
    public const EXTR_PRIORITY = 0x00000002;
    public const EXTR_BOTH     = 0x00000003;

    /**
     * Inner queue class to use for iteration
     *
     * @var class-string<\SplPriorityQueue>
     */
    protected $queueClass = SplPriorityQueue::class;

    /**
     * Actual items aggregated in the priority queue. Each item is an array
     * with keys "data" and "priority".
     *
     * @var list<array{data: TValue, priority: TPriority}>
     */
    protected $items = [];

    /**
     * Inner queue object
     *
     * @var \SplPriorityQueue<TPriority, TValue>|null
     */
    protected $queue;

    /**
     * Insert an item into the queue
     *
     * Priority defaults to 1 (low priority) if none provided.
     *
     * @param  TValue    $data
     * @param  TPriority $priority
     * @return $this
     */
    public function insert($data, $priority = 1)
    {
        /** @psalm-var TPriority $priority */
        $priority      = (int) $priority;
        $this->items[] = [
            'data'     => $data,
            'priority' => $priority,
        ];
        $this->getQueue()->insert($data, $priority);
        return $this;
    }

    /**
     * Remove an item from the queue
     *
     * This is different than {@link extract()}; its purpose is to dequeue an
     * item.
     *
     * This operation is potentially expensive, as it requires
     * re-initialization and re-population of the inner queue.
     *
     * Note: this removes the first item matching the provided item found. If
     * the same item has been added multiple times, it will not remove other
     * instances.
     *
     * @param  mixed $datum
     * @return bool False if the item was not found, true otherwise.
     */
    public function remove($datum)
    {
        $found = false;
        $key   = null;
        foreach ($this->items as $key => $item) {
            if ($item['data'] === $datum) {
                $found = true;
                break;
            }
        }
        if ($found && $key !== null) {
            unset($this->items[$key]);
            $this->queue = null;

            if (! $this->isEmpty()) {
                $queue = $this->getQueue();
                foreach ($this->items as $item) {
                    $queue->insert($item['data'], $item['priority']);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Is the queue empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }

    /**
     * How many items are in the queue?
     *
     * @return int
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    /**
     * Peek at the top node in the queue, based on priority.
     *
     * @return TValue
     */
    public function top()
    {
        $queue = clone $this->getQueue();

        return $queue->top();
    }

    /**
     * Extract a node from the inner queue and sift up
     *
     * @return TValue
     */
    public function extract()
    {
        $value = $this->getQueue()->extract();

        $keyToRemove     = null;
        $highestPriority = null;
        foreach ($this->items as $key => $item) {
            if ($item['data'] !== $value) {
                continue;
            }

            if (null === $highestPriority) {
                $highestPriority = $item['priority'];
                $keyToRemove     = $key;
                continue;
            }

            if ($highestPriority >= $item['priority']) {
                continue;
            }

            $highestPriority = $item['priority'];
            $keyToRemove     = $key;
        }

        if ($keyToRemove !== null) {
            unset($this->items[$keyToRemove]);
        }

        return $value;
    }

    /**
     * Retrieve the inner iterator
     *
     * SplPriorityQueue acts as a heap, which typically implies that as items
     * are iterated, they are also removed. This does not work for situations
     * where the queue may be iterated multiple times. As such, this class
     * aggregates the values, and also injects an SplPriorityQueue. This method
     * retrieves the inner queue object, and clones it for purposes of
     * iteration.
     *
     * @return \SplPriorityQueue<TPriority, TValue>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        $queue = $this->getQueue();
        return clone $queue;
    }

    /**
     * Serialize the data structure
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->__serialize());
    }

    /**
     * Magic method used for serializing of an instance.
     *
     * @return list<array{data: TValue, priority: TPriority}>
     */
    public function __serialize()
    {
        return $this->items;
    }

    /**
     * Unserialize a string into a PriorityQueue object
     *
     * Serialization format is compatible with {@link SplPriorityQueue}
     *
     * @param  string $data
     * @return void
     */
    public function unserialize($data)
    {
        $toUnserialize = unserialize($data);
        if (! is_array($toUnserialize)) {
            throw new UnexpectedValueException(sprintf(
                'Cannot deserialize %s instance; corrupt serialization data',
                self::class
            ));
        }

        /** @psalm-var list<array{data: TValue, priority: TPriority}> $toUnserialize */

        $this->__unserialize($toUnserialize);
    }

   /**
    * Magic method used to rebuild an instance.
    *
    * @param list<array{data: TValue, priority: TPriority}> $data Data array.
    * @return void
    */
    public function __unserialize($data)
    {
        foreach ($data as $item) {
            $this->insert($item['data'], $item['priority']);
        }
    }

    /**
     * Serialize to an array
     * By default, returns only the item data, and in the order registered (not
     * sorted). You may provide one of the EXTR_* flags as an argument, allowing
     * the ability to return priorities or both data and priority.
     *
     * @param  int $flag
     * @return array<array-key, mixed>
     * @psalm-return ($flag is self::EXTR_BOTH
     *                      ? list<array{data: TValue, priority: TPriority}>
     *                      : $flag is self::EXTR_PRIORITY
     *                          ? list<TPriority>
     *                          : list<TValue>
     *               )
     */
    public function toArray($flag = self::EXTR_DATA)
    {
        switch ($flag) {
            case self::EXTR_BOTH:
                return $this->items;
            case self::EXTR_PRIORITY:
                return array_map(static fn($item) => $item['priority'], $this->items);
            case self::EXTR_DATA:
            default:
                return array_map(static fn($item) => $item['data'], $this->items);
        }
    }

    /**
     * Specify the internal queue class
     *
     * Please see {@link getIterator()} for details on the necessity of an
     * internal queue class. The class provided should extend SplPriorityQueue.
     *
     * @param  class-string<\SplPriorityQueue> $class
     * @return $this
     */
    public function setInternalQueueClass($class)
    {
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $this->queueClass = (string) $class;
        return $this;
    }

    /**
     * Does the queue contain the given datum?
     *
     * @param  TValue $datum
     * @return bool
     */
    public function contains($datum)
    {
        foreach ($this->items as $item) {
            if ($item['data'] === $datum) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does the queue have an item with the given priority?
     *
     * @param  TPriority $priority
     * @return bool
     */
    public function hasPriority($priority)
    {
        foreach ($this->items as $item) {
            if ($item['priority'] === $priority) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the inner priority queue instance
     *
     * @throws Exception\DomainException
     * @return \SplPriorityQueue<TPriority, TValue>
     * @psalm-assert !null $this->queue
     */
    protected function getQueue()
    {
        if (null === $this->queue) {
            /** @psalm-suppress UnsafeInstantiation */
            $queue = new $this->queueClass();
            /** @psalm-var \SplPriorityQueue<TPriority, TValue> $queue */
            $this->queue = $queue;
            /** @psalm-suppress DocblockTypeContradiction, MixedArgument */
            if (! $this->queue instanceof \SplPriorityQueue) {
                throw new Exception\DomainException(sprintf(
                    'PriorityQueue expects an internal queue of type SplPriorityQueue; received "%s"',
                    get_class($this->queue)
                ));
            }
        }

        return $this->queue;
    }

    /**
     * Add support for deep cloning
     *
     * @return void
     */
    public function __clone()
    {
        if (null !== $this->queue) {
            $this->queue = clone $this->queue;
        }
    }
}
