<?php

namespace Rosalana\Core\Support;

use Rosalana\Core\Support\Configure\Node\File;
use Rosalana\Core\Support\Configure\Reader;
use Rosalana\Core\Support\Configure\Writer;

/**
 * Pravděpodobně bude potřeba udělat value a array value,
 * protože array value může být na více řádků a je potřeba trochu jinak držovat depth a nebude fungovat indexRender přesně
 */
class Configure
{
    protected File $file;

    protected Reader $reader;

    protected Writer $writer;

    public function __construct(string $file)
    {
        $this->file = File::makeEmpty($file)->setParent($this);

        $this->reader = new Reader($this->file);
        $this->writer = new Writer($this->file);
    }

    public static function file(string $name): File
    {
        return (new self($name))->reader->read();
    }

    public function save(): void
    {
        $this->writer->write();
    }

    public function toArray(): array
    {
        return $this->file->nodes()->map(fn($node) => $node->toArray())->toArray();
    }

    public function key(): string
    {
        return '';
    }

    // pozor mělo by to posunout i všechny další nadcházející uzly
    // stejně tak je potřeba upravit depth podle jeho siblings

    // udělat trait ve stylu HasChildren - pro Configure a pro Section
    // public function addNode(Node $node): self
    // {
    //     $node->setParent($this);

    //     if (! $node->isIndexed()) {
    //         $distance = abs($node->start() - $node->end());
    //         $lastNode = $this->nodes->last();

    //         if ($lastNode) {
    //             $offset = $lastNode instanceof Value ? 1 : 2;
    //             $start = $lastNode->end() + $offset;
    //         } else {
    //             $start = 0; // !!!! hodin na pozici prvního nodu
    //         }

    //         $node->setStartLine($start);
    //         $node->setEndLine($start + $distance);
    //     }

    //     $this->nodes->push($node);

    //     return $this;
    // }

    // public function section(string $path): Section
    // {
    //     $parent = $this->resolve($path);
    //     $key = $this->pathToKey($path);

    //     if ($parent->has($key)) {
    //         return $parent->findNode($key);
    //     } else {
    //         $section = Section::makeEmpty($key);
    //         $parent->addNode($section);

    //         return $section;
    //     }
    // }

    // public function value(string $path): Value
    // {
    //     $parent = $this->resolve($path);
    //     $key = $this->pathToKey($path);

    //     if ($parent->has($key)) {
    //         return $parent->findNode($key);
    //     } else {
    //         $value = Value::makeEmpty($key);
    //         $parent->addNode($value);

    //         return $value;
    //     }
    // }

    // /**
    //  * Create a rich or simple comment node.
    //  * If description is provided, a rich comment is created.
    //  * Otherwise, a simple comment is created.
    //  */
    // public function comment(string $label, ?string $description = null): Node
    // {
    //     if ($description) {
    //         return new RichComment(0, 0, [], $label, $description);
    //     }

    //     return new RichComment(0, 0, [], $label, null); // for now
    // }

    // public function add(string $path, string $value): Node
    // {
    //     return new Value(0, 0, [], '', '');
    // }

    // public function set(string $path, string $value): Node
    // {
    //     return new Value(0, 0, [], '', '');
    // }

    // public function remove(string $path): self
    // {
    //     return $this;
    // }

    // public function path(): string
    // {
    //     // return $this->file; // coud be better
    //     return '';
    // }

    // public function key(): string
    // {
    //     return ''; // je to potřeba kvůli generovaní path??
    // }

    // protected function resolve(string $path): Node
    // {
    //     $parts = explode('.', $path);
    //     array_pop($parts);

    //     $current = $this;

    //     foreach ($parts as $part) {
    //         $child = $current->findNode($part);

    //         if (! $child) {
    //             $child = Section::makeEmpty($part);
    //             $current->addNode($child);
    //         }

    //         $current = $child;
    //     }

    //     return $current;
    // }

    // public function findNode(string $key): ?Node
    // {
    //     foreach ($this->nodes as $node) {
    //         if ($node instanceof RichComment) {
    //             continue;
    //         }

    //         if ($node->key() === $key) {
    //             return $node;
    //         }
    //     }

    //     return null;
    // }

    // // public function has(string $key): bool
    // // {
    // //     return !! ($this->findNode($key));
    // // }

    // // protected function pathToKey(string $path): string
    // // {
    // //     $parts = explode('.', $path);
    // //     return $parts[array_key_last($parts)];
    // // }
}
