<?php

namespace Drupal\tmgmt_smartcat\Service;

use Drupal\tmgmt\Data;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartcat\SmartcatApi;

class Document
{
  /**
   * @var \Drupal\tmgmt\Data
   */
  private Data $data;

  public function __construct(Data $data)
  {
    $this->data = $data;
  }

  /**
   * Import documents from Smartcat to Job
   *
   * @param JobInterface $job
   * @return void
   * @throws \Drupal\tmgmt\TMGMTException
   */
  public function import(JobInterface $job)
  {
    $projectId = $job->getReference();

    $api = new SmartcatApi(
      $job->getTranslator()->getSetting('account_id'),
      $job->getTranslator()->getSetting('api_key'),
      $job->getTranslator()->getSetting('server'),
    );

    $documents = $api->getDocuments($projectId);
    foreach ($documents as $document) {
      $taskId = $api->createDocumentTaskId($document['id']);
      while (true) {
        $data = $api->downloadDocument($taskId);
        if ($data) {
          $preparedData = $this->prepareData($data);
          $job->addTranslatedData($preparedData);
          break;
        }
      }
    }
  }

  /**
   * Prepare data for import to job
   *
   * @param array $data
   * @return array
   */
  private function prepareData(array $data): array
  {
    $document = [];
    foreach ($data as $key => $content) {
      $document[$key] = [];
      $document[$key]['#text'] = $content;
    }
    return $this->data->unflatten($document);
  }
}
