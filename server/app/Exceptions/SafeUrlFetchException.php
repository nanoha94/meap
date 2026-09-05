<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class SafeUrlFetchException extends RuntimeException
{
    public const TYPE_VALIDATION = 'validation';

    public const TYPE_REQUEST = 'request';

    public const TYPE_RESPONSE = 'response';

    public const TYPE_BODY = 'body';

    public function __construct(
        public readonly string $type,
        public readonly ?string $userMessage = null,
        public readonly ?int $httpStatus = null,
        public readonly ?int $bodyLength = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, 0, $previous);
    }

    public static function validationFailed(string $message): self
    {
        return new self(self::TYPE_VALIDATION, $message);
    }

    public static function requestFailed(Throwable $previous): self
    {
        return new self(self::TYPE_REQUEST, previous: $previous);
    }

    public static function badResponse(Response $response): self
    {
        return new self(self::TYPE_RESPONSE, httpStatus: $response->status());
    }

    public static function invalidBody(int $bodyLength, int $maxBytes): self
    {
        return new self(self::TYPE_BODY, bodyLength: $bodyLength);
    }

    /**
     * logWarning 用の統一コンテキストを返す。
     *
     * @return array<string, mixed>
     */
    public function toLogContext(string $url): array
    {
        $context = [
            'url' => $url,
            'reason' => $this->type,
            'exception_message' => $this->getPrevious()?->getMessage(),
            'status' => $this->httpStatus,
            'body_length' => $this->bodyLength,
        ];

        return array_filter(
            $context,
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }
}
