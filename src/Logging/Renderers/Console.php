<?php

namespace Rosalana\Core\Logging\Renderers;

use Rosalana\Core\Services\Logging\LogRenderer;
use Rosalana\Core\Services\Trace\Trace;

class Console extends LogRenderer
{
    public function render(Trace $trace, array $logs): void
    {
        foreach ($logs as $entry) {
            $this->line(sprintf(
                '[%s] %s: %s',
                date('H:i:s', $entry->getTimestamp()),
                $entry->getActor()?->value,
                $entry->getMessage()?->value,
            ));
        }
    }

    public function publish(array $rendered): void
    {
        foreach ($rendered as $line) {
            echo $line->output . PHP_EOL;
        }
    }

    protected function color(string $text, string $color): string
    {
        $c = fn(string $code) => "\033[{$code}m";
        $reset = $c('0');

        $gray   = $c('90');
        $green  = $c('32');
        $red    = $c('31');
        $blue   = $c('34');
        $cyan   = $c('36');
        $yellow = $c('33');

        return match ($color) {
            'gray'   => "{$gray}{$text}{$reset}",
            'green'  => "{$green}{$text}{$reset}",
            'red'    => "{$red}{$text}{$reset}",
            'blue'   => "{$blue}{$text}{$reset}",
            'cyan'   => "{$cyan}{$text}{$reset}",
            'yellow' => "{$yellow}{$text}{$reset}",
            default  => $text,
        };
    }

    protected function styleTime(float $time): string
    {
        return match (true) {
            $time >= 50 => $this->color(number_format($time, 2) . 'ms', 'red'),
            $time >= 10 => $this->color(number_format($time, 2) . 'ms', 'yellow'),
            default     => number_format($time, 2) . 'ms',
        };
    }

    protected function separator(): void
    {
        $this->line($this->color(str_repeat('-', 80), 'gray'));
    }
}
