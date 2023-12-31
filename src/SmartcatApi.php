<?php

namespace Drupal\tmgmt_smartcat;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class SmartcatApi
{
    use SmartcatHelper;

    private const HOST = [
        'EU' => 'https://smartcat.com',
        'US' => 'https://us.smartcat.com',
        'EA' => 'https://ea.smartcat.com',
    ];

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

    /**
     * Fetch account details by credentials
     */
    public function accountDetails(): array
    {
        $response = $this->httpClient->get($this->host().SmartcatEndpoints::ACCOUNT, [
            'auth' => $this->credentials(),
        ]);

        return $this->json($response);
    }

    /**
     * Create project in Smartcat
     */
    public function createProject(string $sourceLang, string $targetLang, array $workflowStages, string $jobId = null): string
    {
        $response = $this->httpClient->post($this->host().SmartcatEndpoints::CREATE_PROJECT, [
            'auth' => $this->credentials(),
            'form_params' => $this->createProjectData($sourceLang, $targetLang, $workflowStages, $jobId),
        ]);

        return $this->getProjectId($response);
    }

    /**
     * Add new document to Smartcat project
     */
    public function addDocumentToProject(string $projectId, array $files): void
    {
        foreach ($files as $key => $file) {
            $response = $this->httpClient->post($this->host().SmartcatEndpoints::UPLOAD_FILE_TO_PROJECT."?projectId=$projectId", [
                'auth' => [$this->accountId, $this->secretKey],
                'multipart' => $this->prepareDocumentData($key, $file),
            ]);
        }
    }

    /**
     * Fetch project from Smartcat by GUID
     */
    public function getProject(string $projectId): array
    {
        $response = $this->httpClient->get($this->host().SmartcatEndpoints::PROJECT.$projectId, [
            'auth' => $this->credentials(),
        ]);

        return $this->json($response);
    }

    /**
     * Fetch documents from project data
     */
    public function getDocuments(string $project): array
    {
        $project = $this->getProject($project);

        return $project['documents'];
    }

    /**
     * Create Task ID (Export ID) for download document
     */
    public function createDocumentTaskId(string $documentId): string
    {
        $response = $this->httpClient->post($this->host().SmartcatEndpoints::DOCUMENT_TASK_ID."?documentIds=$documentId", [
            'auth' => $this->credentials(),
        ]);
        $data = $this->json($response);

        return $data['id'];
    }

    /**
     * Download document from project
     *
     * @message return will be bool|array
     *
     * @return bool|array
     */
    public function downloadDocument(string $taskId)
    {
        $response = $this->httpClient->get($this->host().SmartcatEndpoints::DOCUMENT_EXPORT.$taskId, [
            'auth' => $this->credentials(),
        ]);

        return $this->json($response);
    }

    public function availableProjectMT(string $smartcatProjectId)
    {
        $endpoint = $this->setParams(SmartcatEndpoints::PROJECT_AVAILABLE_MT, [
            'project' => $smartcatProjectId,
        ]);

        $response = $this->httpClient->get($this->host().$endpoint, [
            'auth' => $this->credentials(),
        ]);

        return $this->json($response);
    }

    public function setupMtEngines(string $smartcatProjectId, array $enginesList)
    {
        $endpoint = $this->setParams(SmartcatEndpoints::PROJECT_SETUP_MT, [
            'project' => $smartcatProjectId,
        ]);

        $response = $this->httpClient->post($this->host().$endpoint, [
            'auth' => $this->credentials(),
            'json' => $enginesList,
        ]);

        return $this->json($response);
    }

    public function setupPreTranslationRules(string $smartcatProjectId, array $rules)
    {
        $endpoint = $this->setParams(SmartcatEndpoints::PROJECT_SETUP_PRE_TRANSLATION_RULES, [
            'project' => $smartcatProjectId,
        ]);

        $response = $this->httpClient->post($this->host().$endpoint, [
            'auth' => $this->credentials(),
            'json' => $rules,
        ]);

        return $this->json($response);
    }

    private function setParams(string $endpoint, array $params)
    {
        $endpointWithParams = $endpoint;

        foreach ($params as $key => $value) {
            $endpointWithParams = str_replace('{'.$key.'}', $value, $endpointWithParams);
        }

        return $endpointWithParams;
    }

    /**
     * Api auth credentials
     */
    private function credentials(): array
    {
        return [$this->accountId, $this->secretKey];
    }

    /**
     * Api host
     */
    private function host(): string
    {
        return self::HOST[$this->server];
    }

    /**
     * Convert response to array
     *
     * @message return will be bool|array
     *
     * @return bool|array
     */
    public function json(ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();

        return empty($body) ? false : json_decode($body, true);
    }
}
