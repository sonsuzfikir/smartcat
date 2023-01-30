<?php

namespace Drupal\tmgmt_smartcat;

trait SmartcatHelper
{
  /**
   * Preparing data for creating a project in Smartcat
   *
   * @param string $sourceLang
   * @param string $targetLang
   * @param string|NULL $jobId
   * @return array
   */
  protected function createProjectData(string $sourceLang, string $targetLang, string $jobId = NULL): array
  {
    $uid = $jobId ?? uniqid();
    return [
      'value' => json_encode([
        'name' => "Drupal TMGMT - $uid",
        'sourceLanguage' => $sourceLang,
        'targetLanguages' => [$targetLang],
        'workflowStages' => [
          'Translation'
        ],
        'externalTag' => 'smartcat-drupal-tmgmt'
      ])
    ];
  }

  /**
   * Model for add new document to Smartcat project
   *
   * @return array[]
   */
  protected function getAddDocumentBodyWithModel(): array
  {
    return [
      [
        'name' => 'model',
        'filename' => 'model.json',
        'contents' => json_encode($this->modelForAddDocumentToProjectRequest())
      ]
    ];
  }

  /**
   * Preparing array for document
   *
   * @param string $filename
   * @param string $content
   * @return string[]
   */
  protected function prepareDocument(string $filename, string $content): array
  {
    return [
      'name' => 'files',
      'filename' => $filename,
      'contents' => $content
    ];
  }

  /**
   * Preparing documents list
   *
   * @param string $filename
   * @param string $content
   * @return array
   */
  protected function prepareDocumentData(string $filename, string $content): array
  {
    $data = $this->getAddDocumentBodyWithModel();
    $data[] = $this->prepareDocument($filename, $content);
    return $data;
  }

  /**
   * Fetching Smartcat project ID from response
   *
   * @param $response
   * @return string
   */
  protected function getProjectId($response): string
  {
    $data = $this->json($response);
    return $data['id'];
  }

  /**
   * Model data
   *
   * @return array[]
   */
  private function modelForAddDocumentToProjectRequest(): array
  {
    return [
      [
        "ExternalId" => null,
        "MetaInfo" => null,
        "DisassembleAlgorithmName" => null,
        "PresetDisassembleAlgorithm" => null,
        "DisassembleSettings" => null,
        "BilingualFileImportSetings" => [
          "targetSubstitutionMode" => "All",
          "lockMode" => "None",
          "confirmMode" => "AtFirstStage"
        ],
        "TargetLanguages" => null,
        "EnablePlaceholders" => null,
        "EnableOcr" => null
      ]
    ];
  }

}
