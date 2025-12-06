<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class RichComment extends Node
{
    protected string $label;
    protected ?string $description = null;

    public function __construct(int $start, int $end, array $raw)
    {
        parent::__construct($start, $end, $raw);
    }

    public static function parse(array $content): Collection
    {
        $nodes = collect();

        $start = null;
        $buffer = [];
        $stack = [];

        $arrayStartRegex = '/^\s*([\'"])(?<key>[^\'"]+)\1\s*=>\s*\[\s*$/';

        foreach ($content as $index => $line) {

            $trim = trim($line);

            if (preg_match($arrayStartRegex, $line, $match)) {
                $stack[] = $match['key'];
            }

            if ($trim === '],' || $trim === ']') {
                array_pop($stack);
            }

            if (str_starts_with($trim, '/*')) {
                $start = $index;
                $buffer = [$index => $line];

                if (str_ends_with($trim, '*/')) {
                    [$label, $desc] = static::parseLabelDescription($buffer);
                    $nodes->push(RichComment::make($start, $index, $buffer, $label, $desc));
                    $start = null;
                    $buffer = [];
                }

                continue;
            }

            if ($start !== null) {

                $buffer[$index] = $line;

                if (str_contains($trim, '*/')) {

                    [$label, $desc] = static::parseLabelDescription($buffer);

                    $key = 'richcomment_' . bin2hex(random_bytes(4));

                    $nodes->push(
                        RichComment::make(
                            start: $start,
                            end: $index,
                            raw: $buffer,
                        )->setLabel($label)->setDescription($desc)->setKey(
                            $stack ? implode('.', $stack) . '.' . $key : $key
                        )
                    );

                    $start = null;
                    $buffer = [];
                }

                continue;
            }
        }

        return $nodes;
    }

    protected static function parseLabelDescription(array $raw): array
    {
        $label = null;
        $description = [];

        $insideLabel = false;
        $insideDescription = false;

        foreach ($raw as $line) {
            $trim = trim($line);

            if (preg_match('/^\|\-{3,}/', $trim)) {
                if ($label === null) {
                    $insideLabel = true;
                    continue;
                } else {
                    $insideLabel = false;
                    $insideDescription = true;
                    continue;
                }
            }

            if ($insideLabel && preg_match('/^\|\s*(.*)$/', $trim, $m)) {
                $text = trim($m[1]);
                if ($text !== '') {
                    $label = $text;
                }
                continue;
            }

            if ($insideDescription && preg_match('/^\|\s*(.*)$/', $trim, $m)) {
                $text = trim($m[1]);
                if ($text !== '') {
                    $description[] = $text;
                }
                continue;
            }
        }

        return [
            $label,
            $description ? implode("\n", $description) : null,
        ];
    }

    public function padding(): int
    {
        return 1;
    }

    public function render(): array
    {
        $result = collect();

        $result->push('/*');
        $result->push('|--------------------------------------------------------------------------');
        $result->push('| ' . $this->label());
        $result->push('|--------------------------------------------------------------------------');
        if ($this->description()) {
            $result->push('|');
            foreach (explode("\n", $this->description()) as $line) {
                $result->push('| ' . $line);
            }
            $result->push('|');
        }
        $result->push('*/');

        return $this->indexRender($result);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'label' => $this->label,
            'description' => $this->description,
        ]);
    }
}
