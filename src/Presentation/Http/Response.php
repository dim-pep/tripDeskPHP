<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final readonly class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $statusCode = 200,
        public array $headers = [],
        public string $body = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function json(
        array $data,
        int $statusCode = 200,
    ): self {
        $body = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return new self(
            statusCode: $statusCode,
            headers: [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            body: $body,
        );
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
