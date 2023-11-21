<?php

namespace Drupal\tmgmt_smartcat;

trait SmartcatHelper
{
    /**
     * Preparing data for creating a project in Smartcat
     */
    protected function createProjectData(string $sourceLang, string $targetLang, array $workflowStages, string $jobId = null): array
    {
        $uid = $jobId ?? uniqid();

        return [
            'value' => json_encode([
                'name' => "Drupal TMGMT - $uid",
                'sourceLanguage' => $sourceLang,
                'targetLanguages' => [$targetLang],
                'workflowStages' => $workflowStages,
                'externalTag' => 'smartcat-drupal-tmgmt',
            ]),
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
                'contents' => json_encode($this->modelForAddDocumentToProjectRequest()),
            ],
        ];
    }

    /**
     * Preparing array for document
     *
     * @return string[]
     */
    protected function prepareDocument(string $filename, string $content): array
    {
        return [
            'name' => 'files',
            'filename' => $filename,
            'contents' => $content,
        ];
    }

    /**
     * Preparing documents list
     */
    protected function prepareDocumentData(string $filename, string $content): array
    {
        $data = $this->getAddDocumentBodyWithModel();
        $data[] = $this->prepareDocument($filename, $content);

        return $data;
    }

    /**
     * Fetching Smartcat project ID from response
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
                'ExternalId' => null,
                'MetaInfo' => null,
                'DisassembleAlgorithmName' => null,
                'PresetDisassembleAlgorithm' => null,
                'DisassembleSettings' => null,
                'BilingualFileImportSetings' => [
                    'targetSubstitutionMode' => 'All',
                    'lockMode' => 'None',
                    'confirmMode' => 'AtFirstStage',
                ],
                'TargetLanguages' => null,
                'EnablePlaceholders' => null,
                'EnableOcr' => null,
            ],
        ];
    }
}
