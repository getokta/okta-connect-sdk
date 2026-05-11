<?php

declare(strict_types=1);

namespace Okta\Connect\WhatsApp\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as Psr7Request;
use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\Exceptions\AuthenticationException;
use Okta\Connect\WhatsApp\Exceptions\AuthorizationException;
use Okta\Connect\WhatsApp\Exceptions\NotFoundException;
use Okta\Connect\WhatsApp\Exceptions\RateLimitException;
use Okta\Connect\WhatsApp\Exceptions\ServerException;
use Okta\Connect\WhatsApp\Exceptions\ValidationException;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Default Guzzle-backed transport with retry + status-to-exception mapping.
 *
 * Any PSR-18 ClientInterface can be injected via Config::$httpClient — the
 * SDK never reaches for the global HTTP client. Retries are budgeted by
 * Config::$retries and only triggered for 429 + 5xx responses; non-2xx
 * outside that range is mapped straight to a typed exception.
 */
final class HttpClient implements HttpClientInterface
{
    private readonly ClientInterface $psrClient;

    public function __construct(private readonly Config $config)
    {
        $this->psrClient = $config->httpClient ?? new GuzzleClient([
            'timeout' => $config->timeout,
            'http_errors' => false,
        ]);
    }

    public function get(string $path, array $query = [], array $headers = []): Response
    {
        $url = $this->buildUrl($path, $query);

        return $this->send('GET', $url, null, $headers);
    }

    public function post(string $path, array $body = [], array $headers = []): Response
    {
        return $this->send('POST', $this->buildUrl($path), $body, $headers);
    }

    public function patch(string $path, array $body = [], array $headers = []): Response
    {
        return $this->send('PATCH', $this->buildUrl($path), $body, $headers);
    }

    public function put(string $path, array $body = [], array $headers = []): Response
    {
        return $this->send('PUT', $this->buildUrl($path), $body, $headers);
    }

    public function delete(string $path, array $body = [], array $headers = []): Response
    {
        return $this->send('DELETE', $this->buildUrl($path), $body !== [] ? $body : null, $headers);
    }

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>      $headers
     */
    private function send(string $method, string $url, ?array $body, array $headers): Response
    {
        $payload = $body !== null ? json_encode($body, JSON_THROW_ON_ERROR) : '';
        $merged = $this->defaultHeaders() + $headers;
        $request = new Psr7Request($method, $url, $merged, $payload === '' ? null : $payload);

        $attempt = 0;
        $lastResponse = null;
        $maxAttempts = max(1, $this->config->retries + 1);

        while ($attempt < $maxAttempts) {
            try {
                $psrResponse = $this->psrClient->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                throw new WhatsAppException('HTTP transport error: '.$e->getMessage(), 0, null, $e);
            }

            $status = $psrResponse->getStatusCode();

            if ($status < 500 && $status !== 429) {
                return $this->finalize($psrResponse);
            }

            $lastResponse = $psrResponse;
            $attempt++;

            if ($attempt >= $maxAttempts) {
                break;
            }

            $this->backoff($attempt, $psrResponse);
        }

        // All retries exhausted — throw the appropriate typed exception.
        return $this->finalize($lastResponse);
    }

    private function finalize(ResponseInterface $psrResponse): Response
    {
        $body = (string) $psrResponse->getBody();
        $response = new Response($psrResponse, $body);
        $status = $psrResponse->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return $response;
        }

        throw $this->mapException($response);
    }

    private function mapException(Response $response): WhatsAppException
    {
        $status = $response->statusCode();
        $payload = $response->json();
        $message = is_array($payload) && isset($payload['message']) && is_string($payload['message'])
            ? $payload['message']
            : 'HTTP '.$status;

        return match (true) {
            $status === 401 => new AuthenticationException($message, $status, $response),
            $status === 403 => new AuthorizationException($message, $status, $response),
            $status === 404 => new NotFoundException($message, $status, $response),
            $status === 422 => new ValidationException(
                $message,
                $status,
                $response,
                is_array($payload) && isset($payload['errors']) && is_array($payload['errors']) ? $payload['errors'] : [],
            ),
            $status === 429 => new RateLimitException(
                $message,
                $status,
                $response,
                $this->parseRetryAfter($response->header('Retry-After')),
            ),
            $status >= 500 => new ServerException($message, $status, $response),
            default => new WhatsAppException($message, $status, $response),
        };
    }

    private function backoff(int $attempt, ResponseInterface $response): void
    {
        $retryAfter = $this->parseRetryAfter($response->getHeaderLine('Retry-After'));

        if ($retryAfter !== null) {
            usleep($retryAfter * 1_000_000);

            return;
        }

        // 250 ms * 2^(attempt-1), capped at 4 s.
        $micros = (int) min(4_000_000, 250_000 * (2 ** ($attempt - 1)));
        usleep($micros);
    }

    private function parseRetryAfter(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int) $header;
        }

        $timestamp = strtotime($header);

        if ($timestamp === false) {
            return null;
        }

        return max(0, $timestamp - time());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function buildUrl(string $path, array $query = []): string
    {
        $url = $this->config->baseUrl().'/'.ltrim($path, '/');

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->config->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->config->userAgent,
        ];
    }
}
