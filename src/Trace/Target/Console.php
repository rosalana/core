<?php

namespace Rosalana\Core\Trace\Target;

use Rosalana\Core\Services\Trace\Rendering\Target;
use Rosalana\Core\Services\Trace\Trace;

abstract class Console extends Target
{
    abstract public function render(Trace $trace): void;

    public function publish(array $lines): void
    {
        throw new \Exception('Not implemented');
    }

    public function renderException(Trace $trace): void
    {
        $record = $trace->getException();
        $exception = $record['exception'];

        $this->token('[' . date('H:i:s', $record['timestamp']) . ']', 'gray');
        $this->token('Exception:', 'red');
        $this->token(get_class($exception), 'red');
        $this->newLine();
        $this->token($exception->getMessage());
        $this->newLine();
        $this->token('in', 'gray');
        $this->token($exception->getFile() . ':' . $exception->getLine(), 'cyan');
        $this->newLine();
    }

    public function token(string $value = '', string $color = ''): void
    {
        if ($color) {
            $value = $this->color($value, $color);
        }

        parent::token($value);
    }

    protected function separator(): void
    {
        $this->newLine();
        $this->token(str_repeat('-', 80), 'gray');
        $this->newLine();
    }

    protected function duration(float $time): void
    {
        match (true) {
            $time >= 50 => $this->token(number_format($time, 2) . 'ms', 'red'),
            $time >= 10 => $this->token(number_format($time, 2) . 'ms', 'yellow'),
            default => $this->token(number_format($time, 2) . 'ms'),
        };
    }

    private function color(string $text, string $color): string
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
}
