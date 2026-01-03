<?php

namespace Rosalana\Core\Services\Logging;

use Rosalana\Core\Services\Logging\Node\Flag;
use Rosalana\Core\Services\Logging\Node\Message;
use Rosalana\Core\Services\Logging\Node\Actor;

class LogEntry
{
    /** @var LogNode[] */
    protected array $nodes = [];

    public function __construct(array $nodes)
    {
        $nodes = array_filter($nodes, function ($node) {
            return $node instanceof LogNode;
        });

        $this->addNodes($nodes);
    }

    public static function make(
        ?string $service = null,
        ?array $flags = null,
        ?string $message = null,
        ...$nodes,
    ): self {
        $instance = new self($nodes);

        if ($service) {
            $instance->addActor($service);
        }

        if ($flags) {
            foreach ($flags as $flag) {
                $instance->addFlag($flag);
            }
        }

        if ($message) {
            $instance->addMessage($message);
        }

        return $instance;
    }

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

    public function getFlags(): array
    {
        return $this->getNodes(Flag::class);
    }

    public function addNode(LogNode $node): void
    {
        if ($node->isStandAlone()) {
            $this->nodes = array_filter($this->nodes, function ($n) use ($node) {
                $class = get_class($node);
                return !($n instanceof $class);
            });
        }

        $this->nodes[] = $node;
    }

    /**
     * @param LogNode[] $nodes
     */
    public function addNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            if ($node instanceof LogNode) {
                $this->addNode($node);
            }
        }
    }

    public function addActor(string $service): void
    {
        $this->addNode(new Actor($service));
    }

    public function addMessage(string $message): void
    {
        $this->addNode(new Message($message));
    }

    public function addFlag(string $flag): void
    {
        $this->addNode(new Flag($flag));
    }

    public function removeNode(LogNode $node): void
    {
        $this->nodes = array_filter($this->nodes, function ($n) use ($node) {
            return $n !== $node;
        });
    }

    public function flush(): void
    {
        $this->nodes = [];
    }
}
