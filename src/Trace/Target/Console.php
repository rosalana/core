<?php

namespace Rosalana\Core\Trace\Target;

use Rosalana\Core\Services\Trace\Rendering\Target;
use Rosalana\Core\Services\Trace\Trace;

abstract class Console extends Target
{
    abstract public function render(Trace $trace): void;

    public function publish(array $lines): void
    {
        foreach ($lines as $line) {
            echo implode('', $line) . PHP_EOL;
        }
    }

    public function renderException(Trace $trace): void
    {
        $record = $trace->getException();
        $exception = $record['exception'];

        $this->time($record['timestamp']);
        $this->space();
        $this->token('Exception: ', 'red');
        $this->token(get_class($exception), 'red');

        $this->newLine();
        $this->token($exception->getMessage());

        $this->newLine();
        $this->token('in', 'gray');
        $this->space();
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

    protected function space(int $count = 1): void
    {
        $this->token(str_repeat(' ', $count));
    }

    protected function dot(int $count = 1): void
    {
        $this->token(str_repeat('.', $count));
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

    protected function memory(int $bytes): void
    {
        if ($bytes >= 1073741824) {
            $this->token(number_format($bytes / 1073741824, 2) . ' GB');
        } elseif ($bytes >= 1048576) {
            $this->token(number_format($bytes / 1048576, 2) . ' MB');
        } elseif ($bytes >= 1024) {
            $this->token(number_format($bytes / 1024, 2) . ' KB');
        } else {
            $this->token($bytes . ' B');
        }
    }

    protected function time(?int $timestamp = null): void
    {
        $this->token('[' . date('H:i:s', $timestamp) . ']', 'gray');
    }

    protected function httpStatus(int $status): void
    {
        match (true) {
            $status >= 200 && $status < 300 => $this->token($status, 'green'),
            $status >= 300 && $status < 400 => $this->token($status, 'blue'),
            $status >= 400 && $status < 500 => $this->token($status, 'yellow'),
            $status >= 500 => $this->token($status, 'red'),
            default => $this->token($status, 'gray'),
        };
    }

    protected function httpMethod(string $method): void
    {
        match (strtoupper($method)) {
            'GET' => $this->token('GET', 'green'),
            'POST' => $this->token('POST', 'green'),
            'PUT' => $this->token('PUT', 'cyan'),
            'PATCH' => $this->token('PATCH', 'cyan'),
            'DELETE' => $this->token('DELETE', 'yellow'),
            'OPTIONS' => $this->token('OPTIONS', 'gray'),
            default => $this->token($method, 'gray'),
        };
    }

    protected function outpostMethod(string $method): void
    {
        match (strtolower($method)) {
            'request' => $this->color($method, 'blue'),
            'confirmed' => $this->color($method, 'green'),
            'failed' => $this->color($method, 'red'),
            'unreachable' => $this->color($method, 'yellow'),
            default => $method,
        };
    }

    protected function arrow(string $direction = 'right'): void
    {
        match ($direction) {
            'right' => $this->token('→'),
            'r' => $this->token('→'),
            'left' => $this->token('←'),
            'l' => $this->token('←'),
            'up' => $this->token('↑'),
            'u' => $this->token('↑'),
            'down' => $this->token('↓'),
            'd' => $this->token('↓'),
            'right-up' => $this->token('↗'),
            'ru' => $this->token('↗'),
            'right-down' => $this->token('↘'),
            'rd' => $this->token('↘'),
            'left-up' => $this->token('↖'),
            'lu' => $this->token('↖'),
            'left-down' => $this->token('↙'),
            'ld' => $this->token('↙'),
            'right-left' => $this->token('↔'),
            'rl' => $this->token('↔'),
            'up-down' => $this->token('↕'),
            'ud' => $this->token('↕'),
            'none' => $this->token('•'),
            'n' => $this->token('•'),
            'down-right' => $this->token('↪'),
            'dr' => $this->token('↪'),
            'down-left' => $this->token('↩'),
            'dl' => $this->token('↩'),
            default => $this->token('→'),
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
