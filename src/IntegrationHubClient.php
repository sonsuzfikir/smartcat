<?php

namespace Drupal\tmgmt_smartcat;

use Drupal\tmgmt_smartcat\Models\DataInterface;
use Drupal\tmgmt_smartcat\Models\ExportBeginData;
use Drupal\tmgmt_smartcat\Models\ExportInfoData;
use Drupal\tmgmt_smartcat\Models\ExportResponse;
use Drupal\tmgmt_smartcat\Models\ImportDocumentResponse;
use Drupal\tmgmt_smartcat\Models\Project;
use Drupal\tmgmt_smartcat\Models\TranslatableItem;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class IntegrationHubClient
{
    use HasJson;

    private const HOST = [
        'EU' => 'https://ihub.smartcat.com',
        'EA' => 'https://ihub-ea.smartcat.com',
        'US' => 'https://ihub-us.smartcat.com',
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

    public function getOrCreateProject(?string $id, string $name, string $sourceLocale, array $targetLocales, array $workflowStages): ?Project
    {
        $response = $this->httpClient->post($this->url('project'), [
            'headers' => $this->headers(),
            'json' => [
                'scProjectId' => $id,
                'scProjectName' => $name,
                'scAccountId' => $this->accountId,
                'sourceLanguage' => $sourceLocale,
                'targetLanguages' => $targetLocales,
                'stageTypes' => $workflowStages,
            ],
        ]);

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return Project::create($data);
    }

    public function import(TranslatableItem $item, string $projectId, string $sourceLocale, string $targetLocale): ?ImportDocumentResponse
    {
        $response = $this->httpClient->post($this->url('import'), [
            'headers' => $this->headers(),
            'json' => [
                'drupalItemName' => $item->getName(),
                'drupalJobItemId' => $item->getId(),
                'scAccountId' => $this->accountId,
                'scProjectId' => $projectId,
                'sourceLanguage' => $sourceLocale,
                'targetLanguage' => $targetLocale,
                'items' => $item->getLocJsonSegments(),
            ],
        ]);

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return ImportDocumentResponse::create($data);
    }

    public function exportBegin(ExportBeginData $beginData): ?ExportResponse
    {
        $response = $this->post('export-begin', $beginData);

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return ExportResponse::create($data);
    }

    public function exportResult(ExportInfoData $data): ?ExportResponse
    {
        $response = $this->post('export-result', $data);

        $data = $this->toJson($response);

        if (! $data) {
            return null;
        }

        return ExportResponse::create($data);
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    private function post(string $uri, DataInterface $data): ResponseInterface
    {
        return $this->httpClient->post($this->url($uri), [
            'headers' => $this->headers(),
            'json' => $data->toArray(),
        ]);
    }

    private function url(string $uri): string
    {
        return "{$this->host()}/api/drupal/$uri";
    }

    private function headers(): array
    {
        return [
            'Authorization' => "SmartcatApi {$this->token()}",
            'Content-Type' => 'application/json',
        ];
    }

    private function host(): string
    {
        return self::IS_LOCAL_ENV ? self::DEV_HOST : self::HOST[$this->server];
    }

    private function token(): string
    {
        return base64_encode("$this->accountId:$this->secretKey");
    }
}
