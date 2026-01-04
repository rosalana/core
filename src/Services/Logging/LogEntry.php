<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Services\Logging\Node\Flag;
use Rosalana\Core\Services\Logging\Node\Message;
use Rosalana\Core\Services\Logging\Node\Actor;

class LogEntry
{
    /** @var LogNode[] */
    protected array $nodes = [];

    protected int $timestamp = 0;
    protected int $sequence = 0;

    public function __construct(array $nodes)
    {
        $nodes = array_filter($nodes, function ($node) {
            return $node instanceof LogNode;
        });

        $this->addNodes($nodes);
    }

    public static function make(
        ?string $actor = null,
        ?array $flags = null,
        ?string $message = null,
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

        return $instance;
    }

    /**
     * @return LogNode[]
     */
    public function getNodes(?string $type = null): array
    {
        if (!$type) return $this->nodes;

        return array_filter($this->nodes, function ($node) use ($type) {
            return $node instanceof $type;
        });
    }

    public function getNode(string $type): ?LogNode
    {
        foreach ($this->nodes as $node) {
            if ($node instanceof $type) {
                return $node;
            }
        }

        return null;
    }

    public function getActor(): ?LogNode
    {
        return $this->getNode(Actor::class);
    }

    public function getMessage(): ?LogNode
    {
        return $this->getNode(Message::class);
    }

    /**
     * @return LogNode[]
     */
    public function getFlags(): array
    {
        return $this->getNodes(Flag::class);
    }

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

    public function addActor(string $service): self
    {
        return $this->addNode(new Actor($service));
    }

    public function addMessage(string $message): self
    {
        return $this->addNode(new Message($message));
    }

    public function addFlag(string $name, string $flag): self
    {
        return $this->addNode(new Flag($flag, $name));
    }

    public function removeNode(LogNode $node): self
    {
        $this->nodes = array_filter($this->nodes, function ($n) use ($node) {
            return $n !== $node;
        });

        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;
        return $this;
    }

    public function flush(): self
    {
        $this->nodes = [];

        return $this;
    }
}
