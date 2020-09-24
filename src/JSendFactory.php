<?php

declare(strict_types=1);

namespace MarcusIrgens\JSendFactory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Simple implementation of the JSend spec using PSR-17 factories
 *
 * @see https://github.com/omniti-labs/jsend
 * @see https://www.php-fig.org/psr/psr-17/
 * @api
 */
class JSendFactory implements JSendFactoryInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function getSuccess($data): ResponseInterface
    {
        $payload = ["status" => "success"];
        $this->validateData($data);
        $payload = $this->withData($payload, $data);

        $response = $this->getSuccessResponse();
        return $this->withPayload($response, $payload);
    }

    /**
     * @inheritDoc
     */
    public function getError(string $message, ?int $code = null, $data = null): ResponseInterface
    {
        $payload = ["status" => "error"];
        if ($data !== null) {
            $this->validateData($data);
            $payload = $this->withData($payload, $data);
        }

        if (!is_null($code)) {
            $payload["code"] = $code;
        }

        if (strlen(trim($message)) === 0) {
            throw new \InvalidArgumentException('$message must be a non-empty string');
        }

        $payload["message"] = $message;

        $response = $this->getErrorResponse();
        return $this->withPayload($response, $payload);
    }

    /**
     * @inheritDoc
     */
    public function getErrorFromThrowable(\Throwable $throwable): ResponseInterface
    {
        return $this->getError($throwable->getMessage(), (int)$throwable->getCode());
    }

    /**
     * @inheritDoc
     */
    public function getFail($data): ResponseInterface
    {
        $payload = ["status" => "fail"];
        $this->validateData($data);

        $payload = $this->withData($payload, $data);

        return $this->withPayload($this->getFailResponse(), $payload);
    }

    /**
     * @param mixed $data
     * @psalm-assert \JsonSerializable|array $data
     */
    private function validateData($data): void
    {
        if (is_array($data)) {
            return;
        }

        if (is_object($data) && $data instanceof \JsonSerializable) {
            return;
        }

        throw new \TypeError(
            sprintf(
                "Data passed to %s must be of type %s or %s, %s provided",
                __CLASS__,
                \JsonSerializable::class,
                "array",
                is_object($data) ? gettype($data) . " of type " . get_class($data) : gettype($data)
            )
        );
    }

    /**
     * @param array $payload
     * @param \JsonSerializable|array $data
     * @return array
     */
    private function withData(array $payload, $data): array
    {
        if ($data instanceof \JsonSerializable) {
            /** @var mixed $data */
            $data = $data->jsonSerialize();
            if (!is_array($data)) {
                throw new \TypeError(
                    sprintf(
                        "%s data must be of type %s",
                        \JsonSerializable::class,
                        "array"
                    )
                );
            }
        }

        /** @var mixed $data */
        $data = $this->serializeData($data);
        if (!is_array($data)) {
            throw new \LogicException("Serializing the data did not return an array");
        }

        $payload["data"] = $data;
        return $payload;
    }

    /**
     * Get the base response object for JSend responses
     *
     * @return ResponseInterface
     */
    private function getBaseResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader("content-type", "application/json; charset=utf-8");
    }

    /**
     * Get the response object for success responses
     *
     * @return ResponseInterface
     */
    private function getSuccessResponse(): ResponseInterface
    {
        return $this->getBaseResponse()
            ->withStatus(200);
    }

    /**
     * Add the payload
     *
     * @param ResponseInterface $response
     * @param array $payload
     * @return ResponseInterface
     * @throws \JsonException
     */
    private function withPayload(ResponseInterface $response, array $payload): ResponseInterface
    {
        $payload = $this->encodePayload($payload);
        $body = $this->streamFactory->createStream($payload);
        return $response->withBody($body);
    }

    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function getErrorResponse(): ResponseInterface
    {
        return $this->getBaseResponse()
            ->withStatus(500);
    }

    private function getFailResponse(): ResponseInterface
    {
        return $this->getBaseResponse()
            ->withStatus(400);
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function serializeData($data)
    {
        if ($data instanceof \DateTimeInterface) {
            $data = $data->format(\DateTimeInterface::RFC3339_EXTENDED);
        }

        if (is_array($data)) {
            $newArr = [];
             /** @psalm-var mixed $k */
             /** @psalm-var mixed $v */
            foreach ($data as $k => $v) {
                /** @psalm-suppress MixedAssignment */
                $newArr[$k] = $this->serializeData($v);
            }
            $data = $newArr;
        }

        return $data;
    }
}
