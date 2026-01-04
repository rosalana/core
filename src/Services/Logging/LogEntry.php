<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Services\Logging\Node\Flag;
use Rosalana\Core\Services\Logging\Node\Message;
use Rosalana\Core\Services\Logging\Node\Actor;
use Rosalana\Core\Services\Logging\Node\Status;
use Rosalana\Core\Services\Trace\Trace;

class LogEntry
{
    /** @var LogNode[] */
    protected array $nodes = [];

    protected Trace $trace;

    protected int $timestamp = 0;
    protected int $sequence = 0;

    public function __construct(array $nodes)
    {
        $nodes = array_filter($nodes, function ($node) {
            return $node instanceof LogNode;
        });

        $this->addNodes($nodes);
    }

    /**
     * Create a new log entry.
     * 
     * @param string|null $actor
     * @param array|null $flags
     * @param string|null $message
     * @param string|null $status available statuses: info, warning, error, debug (default: info)
     * @param LogNode ...$nodes
     * @return self
     */
    public static function make(
        ?string $actor = null,
        ?array $flags = null,
        ?string $message = null,
        ?string $status = 'info',
        ...$nodes,
    ): self {
        $instance = new self($nodes);

        if ($actor) {
            $instance->addActor($actor);
        }

        if ($flags) {
            foreach ($flags as $name => $flag) {
                $instance->addFlag($name, $flag);
            }
        }

        if ($message) {
            $instance->addMessage($message);
        }

        if ($status) {
            $instance->addStatus($status);
        }

        return $instance;
    }

    public function trace(): Trace
    {
        return $this->trace;
    }

    /**
     * Get all nodes of the entry.
     * 
     * @param class-string|null $type
     * 
     * @return LogNode[]
     */
    public function getNodes(?string $type = null): array
    {
        if (!$type) return $this->nodes;

        return array_filter($this->nodes, function ($node) use ($type) {
            return $node instanceof $type;
        });
    }

    /**
     * Get a single node of the entry.
     * 
     * @param class-string $type
     * @return LogNode|null
     */
    public function getNode(string $type): ?LogNode
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof $type) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get the actor node.
     * 
     * @return LogNode|null
     */
    public function getActor(): ?LogNode
    {
        return $this->getNode(Actor::class);
    }

    /**
     * Get the message node.
     * 
     * @return LogNode|null
     */
    public function getMessage(): ?LogNode
    {
        return $this->getNode(Message::class);
    }

    /**
     * Get the flag nodes.
     * 
     * @return LogNode[]
     */
    public function getFlags(): array
    {
        return $this->getNodes(Flag::class);
    }

    /**
     * Get a specific flag node by name.
     * 
     * @param string $name
     * @return LogNode|null
     */
    public function getFlag(string $name): ?LogNode
    {
        $flags = $this->getFlags();

        foreach ($flags as $flag) {
            if ($flag->getName() === $name) {
                return $flag;
            }
        }

        return null;
    }

    /**
     * Get the status node.
     * 
     * @return LogNode|null
     */
    public function getStatus(): ?LogNode
    {
        return $this->getNode(Status::class);
    }

    /**
     * Add a log node.
     * 
     * @param LogNode $node
     * @return self
     */
    public function addNode(LogNode $node): self
    {
        if ($node->isStandAlone()) {
            $this->nodes = array_filter($this->nodes, function ($n) use ($node) {
                $class = get_class($node);
                return !($n instanceof $class);
            });
        }

        $this->nodes[] = $node;

        return $this;
    }

    /**
     * Add multiple log nodes.
     * 
     * @param LogNode[] $nodes
     */
    public function addNodes(array $nodes): self
    {
        foreach ($nodes as $node) {
            if ($node instanceof LogNode) {
                $this->addNode($node);
            }
        }

        return $this;
    }

    /**
     * Add an actor node.
     * 
     * @param string $actor
     * @return self
     */
    public function addActor(string $actor): self
    {
        return $this->addNode(new Actor($actor));
    }

    /**
     * Add a message node.
     * 
     * @param string $message
     * @return self
     */
    public function addMessage(string $message): self
    {
        return $this->addNode(new Message($message));
    }

    /**
     * Add a flag node.
     * 
     * @param string $name
     * @param string $flag
     * @return self
     */
    public function addFlag(string $name, string $flag): self
    {
        return $this->addNode(new Flag($flag, $name));
    }

    /**
     * Add a status node.
     * 
     * @param string $status
     * @return self
     */
    public function addStatus(string $status): self
    {
        return $this->addNode(new Status($status));
    }

    /**
     * Remove a log node.
     * 
     * @param LogNode $node
     * @return self
     */
    public function removeNode(LogNode $node): self
    {
        $this->nodes = array_filter($this->nodes, function ($n) use ($node) {
            return $n !== $node;
        });

        return $this;
    }

    /**
     * Get the timestamp of the entry.
     * 
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * Set the timestamp of the entry.
     * 
     * @param int $timestamp
     * @return self
     */
    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function setTrace(Trace $trace): self
    {
        $this->trace = $trace;
        return $this;
    }

    /**
     * Get the sequence number of the entry.
     * 
     * @return int
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Set the sequence number of the entry.
     * 
     * @param int $sequence
     * @return self
     */
    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;
        return $this;
    }

    /**
     * Remove all log nodes.
     * 
     * @return self
     */
    public function flush(): self
    {
        $this->nodes = [];

        return $this;
    }
}
