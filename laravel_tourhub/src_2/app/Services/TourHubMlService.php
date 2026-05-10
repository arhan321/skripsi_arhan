<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TourHubMlService
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?int $timeout = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?: config('tourhub.ml_base_url'), '/');
        $this->timeout = $timeout ?: (int) config('tourhub.ml_timeout', 30);
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

    protected function request(string $method, string $endpoint, array $data = []): array
    {
        try {
            $client = Http::timeout($this->timeout)->acceptJson()->asJson();
            $url = $this->baseUrl . $endpoint;

            $response = match (strtolower($method)) {
                'get' => $client->get($url, $data),
                'post' => $client->post($url, $data),
                default => throw new RuntimeException("Unsupported HTTP method: {$method}"),
            };

            if ($response->failed()) {
                throw new RuntimeException("ML API error HTTP {$response->status()}: " . $response->body());
            }

            return $response->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            throw new RuntimeException('Gagal terhubung ke ML API: ' . $e->getMessage(), 0, $e);
        }
    }
}
