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

  /**
   * @var Client
   */
  private Client $httpClient;

  /**
   * @var string
   */
  private string $accountId;

  /**
   * @var string
   */
  private string $secretKey;

  /**
   * @var string
   */
  private string $server;

  /**
   * @param string $accountId
   * @param string $secretKey
   * @param string $server
   */
  public function __construct(string $accountId, string $secretKey, string $server = 'EU')
  {
    $this->accountId = $accountId;
    $this->secretKey = $secretKey;
    $this->server = $server;
    $this->httpClient = new Client();
  }

  /**
   * Fetch account details by credentials
   *
   * @return array
   */

  public function accountDetails(): array
  {
    $response = $this->httpClient->get($this->host() . SmartcatEndpoints::ACCOUNT, [
      'auth' => $this->credentials()
    ]);
    return $this->json($response);
  }

  /**
   * Create project in Smartcat
   *
   * @param string $sourceLang
   * @param string $targetLang
   * @param string|NULL $jobId
   * @return string
   */

  public function createProject(string $sourceLang, string $targetLang, string $jobId = NULL): string
  {
    $response = $this->httpClient->post($this->host() . SmartcatEndpoints::CREATE_PROJECT, [
      'auth' => $this->credentials(),
      'form_params' => $this->createProjectData($sourceLang, $targetLang, $jobId)
    ]);
    return $this->getProjectId($response);
  }

  /**
   * Add new document to Smartcat project
   *
   * @param string $projectId
   * @param array $files
   * @return void
   */

  public function addDocumentToProject(string $projectId, array $files): void
  {
    foreach ($files as $key => $file) {
      $response = $this->httpClient->post($this->host() . SmartcatEndpoints::UPLOAD_FILE_TO_PROJECT . "?projectId=$projectId", [
        'auth' => [$this->accountId, $this->secretKey],
        'multipart' => $this->prepareDocumentData($key, $file)
      ]);
    }
  }

  /**
   * Fetch project from Smartcat by GUID
   *
   * @param string $projectId
   * @return array
   */
  public function getProject(string $projectId): array
  {
    $response = $this->httpClient->get($this->host() . SmartcatEndpoints::PROJECT . $projectId, [
      'auth' => $this->credentials()
    ]);
    return $this->json($response);
  }

  /**
   * Fetch documents from project data
   *
   * @param string $project
   * @return array
   */
  public function getDocuments(string $project): array
  {
    $project = $this->getProject($project);
    return $project['documents'];
  }

  /**
   * Create Task ID (Export ID) for download document
   *
   * @param string $documentId
   * @return string
   */
  public function createDocumentTaskId(string $documentId): string
  {
    $response = $this->httpClient->post($this->host() . SmartcatEndpoints::DOCUMENT_TASK_ID . "?documentIds=$documentId", [
      'auth' => $this->credentials()
    ]);
    $data = $this->json($response);
    return $data['id'];
  }

  /**
   * Download document from project
   *
   * @param string $taskId
   * @return bool|array
   */
  public function downloadDocument(string $taskId): bool|array
  {
    $response = $this->httpClient->get($this->host() . SmartcatEndpoints::DOCUMENT_EXPORT . $taskId, [
      'auth' => $this->credentials()
    ]);
    return $this->json($response);
  }

  /**
   * Api auth credentials
   *
   * @return array
   */
  private function credentials(): array
  {
    return [$this->accountId, $this->secretKey];
  }

  /**
   * Api host
   *
   * @return string
   */
  private function host(): string
  {
    return self::HOST[$this->server];
  }

  /**
   * Convert response to array
   *
   * @param ResponseInterface $response
   * @return bool|array
   */
  public function json(ResponseInterface $response): bool|array
  {
    $body = $response->getBody()->getContents();
    return empty($body) ? false : json_decode($body, true);
  }
}
