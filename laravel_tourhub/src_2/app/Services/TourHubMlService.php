<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TourHubMlService
{
    private string $baseUrl;

    private int $timeout;

    private string $apiKey;

    public function __construct(?string $baseUrl = null, ?int $timeout = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: (string) config('tourhub.ml_base_url'), '/');
        $this->timeout = $timeout ?: (int) config('tourhub.ml_timeout', 30);
        $this->apiKey = $apiKey ?: (string) config('tourhub.ml_api_key', '123');
    }

    public function health(): array
    {
        return $this->request('get', '/');
    }

    public function metadata(): array
    {
        return $this->request('get', '/metadata');
    }

    public function destinations(array $query = []): array
    {
        return $this->request('get', '/destinations', $query);
    }

    public function recommend(array $payload): array
    {
        return $this->request('post', '/recommend', $payload);
    }

    public function reloadDataset(): array
    {
        return $this->request('post', '/reload-dataset');
    }

    public function reloadDatasetSilently(): void
    {
        try {
            $this->reloadDataset();
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function client(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout(5)
            ->retry(2, 300)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
            ]);
    }

    private function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = $this->baseUrl.$endpoint;
            $client = $this->client();

            $response = match (strtolower($method)) {
                'get' => $client->get($url, $data),
                'post' => $client->post($url, $data),
                default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                $body = Str::limit($response->body(), 500);
                throw new RuntimeException("ML API error HTTP {$response->status()}: {$body}");
            }

            return $response->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('Gagal terhubung ke ML API: '.$e->getMessage(), 0, $e);
        }
    }
}
