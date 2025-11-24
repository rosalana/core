<?php

namespace Rosalana\Core\Support\Configure\Node;

use Illuminate\Support\Collection;

class RichComment extends Node
{
    public function __construct(
        protected int $start,
        protected int $end,
        protected array $raw,
        protected ?string $label,
        protected ?string $description,
    ) {}

    public static function parse(array $content): Collection
    {
        $nodes = collect();

        $start = null;
        $buffer = [];

        foreach ($content as $index => $line) {

            $trim = trim($line);

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

                    $nodes->push(RichComment::make(
                        start: $start,
                        end: $index,
                        raw: $buffer,
                        label: $label,
                        description: $desc
                    ));

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

    public function render(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'label' => $this->label,
            'description' => $this->description,
        ]);
    }
}
