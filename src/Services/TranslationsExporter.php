<?php

namespace Drupal\tmgmt_smartcat\Services;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\tmgmt\Data;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt_smartcat\IntegrationHubClient;
use Drupal\tmgmt_smartcat\Models\ExportBeginData;
use Drupal\tmgmt_smartcat\Models\ExportedItem;
use Drupal\tmgmt_smartcat\SmartcatClient;
use GuzzleHttp\Exception\RequestException;

class TranslationsExporter
{
    private Data $data;

    private IntegrationHubClient $hub;

    private SmartcatClient $cat;

    private Connection $db;

    private ImmutableConfig $tmgmtConfig;

    public function __construct(Data $data)
    {
        $this->data = $data;

        /** @var Connection $connection */
        $this->db = \Drupal::service('database');

        $this->tmgmtConfig = \Drupal::config('tmgmt.settings');

        $this->initApi();
    }

    /**
     * @throws TMGMTException
     */
    public function continuousExport()
    {
        $ids = (new SmartcatDocument())->getReadyToExportJobItemIds($this->limit());

        if (empty($ids)) {
            $this->updateDocumentsProgress();

            return;
        }

        $jobItemsByJob = [];

        foreach (JobItem::loadMultiple($ids) as $item) {
            $jobItemsByJob[$item->getJobId()][] = $item;
        }

        foreach ($jobItemsByJob as $items) {
            foreach ($items as $item) {
                $this->continuousExportItem($item);
            }
        }
    }

    /**
     * @throws TMGMTException
     */
    public function continuousExportItem(JobItemInterface $jobItem)
    {
        $job = $jobItem->getJob();

        try {
            $beginData = ExportBeginData::create(
                $jobItem->id(),
                $this->hub->accountId(),
                $job->getReference(),
                $job->getTargetLangcode()
            );

            $exportBeginResponse = $this->hub->exportBegin($beginData);

            $exportInfo = $exportBeginResponse->getExportInfo();

            if (! $exportInfo->isFailed()) {
                $attempt = 1;

                while (true) {
                    if ($attempt > 10) {
                        $this->exportingError($job, $jobItem, 'The number of attempts to obtain the translation result has been exceeded (max 10)');
                        break;
                    }

                    $exportResultResponse = $this->hub->exportResult($exportInfo);

                    if ($exportResultResponse->hasItems()) {
                        $segments = $exportResultResponse->getItems();
                        $translations = $this->mapSegments($segments);
                        $job->addTranslatedData($translations);
                        $jobItem->acceptTranslation();

                        break;
                    }

                    sleep(1);

                    $attempt++;
                }
            } else {
                $this->exportingError($job, $jobItem, $exportInfo->getFailureReason());
            }
        } catch (RequestException $e) {
            $this->exportingError($job, $jobItem, $e->getResponse()->getBody()->getContents());
        }
    }

    private function updateDocumentsProgress()
    {
        $projectIds = $this->getSmartcatProjectIds();

        foreach ($projectIds as $projectId) {
            $project = $this->cat->getProject($projectId);

            $project->updateDocumentsProgress();
        }
    }

    private function limit()
    {
        return $this->tmgmtConfig->get('job_items_cron_limit');
    }

    private function getSmartcatProjectIds(): array
    {
        $query = $this->db->select('tmgmt_job', 'p');
        $query->fields('p', ['reference']);
        $query->condition('translator', 'smartcat');
        $query->condition('reference', null, 'IS NOT NULL');

        return array_unique($query->execute()->fetchCol());
    }

    /**
     * @param  array<ExportedItem>  $segments
     */
    private function mapSegments(array $segments): array
    {
        $document = [];

        foreach ($segments as $segment) {
            $document[$segment->getId()] = [];
            $document[$segment->getId()]['#text'] = $segment->getTranslation();
        }

        return $this->data->unflatten($document);
    }

    private function exportingError(JobInterface $job, JobItemInterface $jobItem, string $failureReason)
    {
        \Drupal::logger('tmgmt_smartcat')->error('@message | Job ID: @job_id | Job Item ID: @job_item_id | Project ID: @project_id | Failure reason: @failure_reason', [
            '@message' => 'An error occurred while exporting translations from Smartcat',
            '@job_id' => $job->id(),
            '@job_item_id' => $jobItem->id(),
            '@project_id' => $job->getReference(),
            '@failure_reason' => $failureReason,
        ]);
    }

    private function initApi()
    {
        $translator = Translator::load('smartcat');

        $credentials = $this->getApiCredentials($translator);

        $this->hub = new IntegrationHubClient(...$credentials);
        $this->cat = new SmartcatClient(...$credentials);
    }

    private function getApiCredentials(Translator $translator): array
    {
        return $this->localApiCredentials() ?? [
            'accountId' => $translator->getSetting('account_id'),
            'secretKey' => $translator->getSetting('api_key'),
            'server' => $translator->getSetting('server'),
        ];
    }

    private function localApiCredentials(): ?array
    {
        return null;
    }
}
