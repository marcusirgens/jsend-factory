<?php

declare(strict_types=1);

namespace MarcusIrgens\JSendFactory;

use Psr\Http\Message\ResponseInterface;

/**
 * Factory for JSend responses
 *
 * @see https://github.com/omniti-labs/jsend
 * @see https://www.php-fig.org/psr/psr-7/
 */
interface JSendFactoryInterface
{
    /**
     * Create a success response from a JsonSerializable object or an array
     *
     * @param \JsonSerializable|array $data If \JsonSerializable, must serialize to an array
     * @return ResponseInterface
     */
    public function getSuccess($data): ResponseInterface;

    /**
     * Create an error response from a JsonSerializable object or an array
     *
     * @param string $message A non-empty error message
     * @param int|null $code An optional error code
     * @param \JsonSerializable|array|null $data An optional data field.  If \JsonSerializable, must serialize to an
     *                                           array
     * @return ResponseInterface
     */
    public function getError(string $message, ?int $code = null, $data = null): ResponseInterface;

    /**
     * Create an error response from a Throwable
     *
     * @param \Throwable $throwable
     * @return ResponseInterface
     */
    public function getErrorFromThrowable(\Throwable $throwable): ResponseInterface;

    /**
     * Create an error response from a JsonSerializable object or an array
     *
     * @param \JsonSerializable|array $data If \JsonSerializable, must serialize to an array
     * @return ResponseInterface
     */
    public function getFail($data): ResponseInterface;
}
