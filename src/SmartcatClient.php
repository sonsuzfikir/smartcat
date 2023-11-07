<?php

namespace Drupal\tmgmt_smartcat;

use Drupal\tmgmt_smartcat\Models\Project;
use Drupal\tmgmt_smartcat\Models\ProjectMT;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class SmartcatClient
{
    use HasJson;

    private const HOST = [
        'EU' => 'https://smartcat.com',
        'US' => 'https://us.smartcat.com',
        'EA' => 'https://ea.smartcat.com',
    ];

    private const IS_LOCAL_ENV = false;

    private const DEV_HOST = null;

    private Client $httpClient;

    private string $accountId;

    private string $secretKey;

    private string $server;

    public function __construct(string $accountId, string $secretKey, string $server = 'EU')
    {
        $this->accountId = $accountId;
        $this->secretKey = $secretKey;
        $this->server = $server;
        $this->httpClient = new Client();
    }

    public function getProject(string $id): ?Project
    {
        $response = $this->get("v1/project/$id");

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return Project::create($data);
    }

    /**
     * @return array<ProjectMT>|null
     */
    public function getAvailableProjectMT(string $projectId): ?array
    {
        $response = $this->get("v1/project/{$projectId}/mt/available");

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return array_map(function ($item) {
            return ProjectMT::create($item);
        }, $data);
    }

    public function setupMtEngine(string $projectId, array $engines)
    {
        $this->post("v1/project/{$projectId}/mt", $engines);
    }

    public function setupPreTranslationRules(string $projectId, array $rules)
    {
        $this->post("v1/project/$projectId/pretranslation-rules", $rules);
    }

    private function get(string $uri): ResponseInterface
    {
        return $this->httpClient->get($this->url($uri), [
            'headers' => $this->headers(),
            'auth' => $this->credentials(),
        ]);
    }

    private function post(string $uri, array $data): ResponseInterface
    {
        return $this->httpClient->post($this->url($uri), [
            'headers' => $this->headers(),
            'auth' => $this->credentials(),
            'json' => $data,
        ]);
    }

    private function credentials(): array
    {
        return [$this->accountId, $this->secretKey];
    }

    private function url(string $uri): string
    {
        return "{$this->host()}/api/integration/$uri";
    }

    private function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    private function host(): string
    {
        return self::IS_LOCAL_ENV ? self::DEV_HOST : self::HOST[$this->server];
    }
}
