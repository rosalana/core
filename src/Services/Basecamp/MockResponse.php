<?php

namespace Rosalana\Core\Services\Basecamp;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MockResponse implements ResponseInterface
{
    public function __construct(protected array $payload = []) {}

    public function getStatusCode(): int
    {
        return 200;
    }

    public function getReasonPhrase(): string
    {
        return 'OK';
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function getHeaders(): array
    {
        return ['Content-Type' => ['application/json']];
    }

    public function hasHeader(string $name): bool
    {
        return strtolower($name) === 'content-type';
    }

    public function getHeader(string $name): array
    {
        return ['application/json'];
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this;
    }

    public function getHeaderLine(string $name): string
    {
        return 'application/json';
    }

    public function getBody(): StreamInterface
    {
        return new class($this->payload) implements StreamInterface {
            public function __construct(private array $data) {}

            public function __toString(): string
            {
                return json_encode($this->data);
            }

            public function getContents(): string
            {
                return json_encode($this->data);
            }

            public function close(): void {}
            public function detach(): void {}
            public function eof(): bool
            {
                return true;
            }
            public function tell(): int
            {
                return 0;
            }
            public function rewind(): void {}
            public function getSize(): ?int
            {
                return null;
            }
            public function isSeekable(): bool
            {
                return false;
            }
            public function isWritable(): bool
            {
                return false;
            }
            public function isReadable(): bool
            {
                return false;
            }
            public function seek(int $offset, int $whence = SEEK_SET): void {}
            public function write(string $string): int
            {
                return 0;
            }
            public function read(int $length): string
            {
                return '';
            }
        };
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        return $this;
    }
    public function withProtocolVersion(string $version): static
    {
        return $this;
    }
    public function withHeader(string $name, $value): static
    {
        return $this;
    }
    public function withAddedHeader(string $name, $value): static
    {
        return $this;
    }
    public function withBody(StreamInterface $body): static
    {
        return $this;
    }
}
