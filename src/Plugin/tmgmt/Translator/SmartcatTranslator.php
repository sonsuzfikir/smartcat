<?php

/**
 * @file
 * Contains \Drupal\tmgmt_smartcat\Plugin\tmgmt\Translator\SmartcatTranslator.
 */

namespace Drupal\tmgmt_smartcat\Plugin\tmgmt\Translator;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_smartcat\IntegrationHubClient;
use Drupal\tmgmt_smartcat\Models\Project;
use Drupal\tmgmt_smartcat\Models\ProjectMT;
use Drupal\tmgmt_smartcat\Models\TranslatableItem;
use Drupal\tmgmt_smartcat\Models\TranslatableItemSegment;
use Drupal\tmgmt_smartcat\Services\SmartcatDocument;
use Drupal\tmgmt_smartcat\SmartcatClient;
use GuzzleHttp\Exception\RequestException;

/**
 * Smartcat translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "smartcat",
 *   label = @Translation("Smartcat"),
 *   description = @Translation("..."),
 *   ui = "Drupal\tmgmt_smartcat\SmartcatTranslatorUi",
 *   logo = "icons/smartcat.png",
 * )
 */
class SmartcatTranslator extends TranslatorPluginBase implements ContinuousTranslatorInterface
{
    private SmartcatClient $cat;

    private IntegrationHubClient $hub;

    /**
     * Sending documents to Smartcat
     *
     * @return JobInterface
     *
     * @throws \Drupal\tmgmt\TMGMTException
     * @throws EntityStorageException
     */
    public function requestTranslation(JobInterface $job)
    {
        $this->initApiClients($job->getTranslator());

        return $this->requestJobItemsTranslation($job->getItems());
    }

    /**
     * Create a Smartcat project and submit items from a job
     *
     * @param  JobItemInterface[]  $job_items
     * @return JobInterface
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws TMGMTException
     */
    public function requestJobItemsTranslation(array $job_items)
    {
        /** @var Job $job */
        $job = reset($job_items)->getJob();

        $this->initApiClients($job->getTranslator());

        try {
            $project = $this->getOrCreateProject($job);

            $job->reference = $project->getId();
            $job->save();
            $job->addMessage('Smartcat project has been initialized. Project ID: @id', ['@id' => $project->getId()], 'debug');
        } catch (RequestException $e) {
            $job->rejected('Failed to initialize a project in Smartcat. Contact us at support@smartcat.com');
            \Drupal::logger('tmgmt_smartcat')->error('@message | Job ID: @job_id | Response: @response', [
                '@message' => 'An error occurred while initializing a project in Smartcat',
                '@job_id' => $job->id(),
                '@response' => $e->getResponse()->getBody()->getContents(),
            ]);

            return $job;
        }

        $translatableItems = $this->prepareDataForSending($job_items);

        foreach ($translatableItems as $translatableItem) {
            try {
                $response = $this->hub->import(
                    $translatableItem,
                    $project->getId(),
                    $job->getSourceLangcode(),
                    $job->getTargetLangcode()
                );

                $jobItem = $translatableItem->getJobItem();

                if (! is_null($response)) {
                    $jobItem->active();
                    $jobItem->save();

                    $document = (new SmartcatDocument())->findByJobItemId($jobItem->id());

                    if (is_null($document)) {
                        $document = new SmartcatDocument($jobItem->id(), $response->getDocumentId());
                        $document->store();
                    }
                }
            } catch (RequestException $e) {
                \Drupal::logger('tmgmt_smartcat')->error('@message | Job ID: @job_id | Job Item ID: @job_item_id | Project ID: @project_id | Message: @message | Response: @response', [
                    '@message' => 'An error occurred while importing a document into Smartcat',
                    '@job_id' => $job->id(),
                    '@job_item_id' => $translatableItem->getJobItem()->id(),
                    '@project_id' => $project->getId(),
                    '@response' => $e->getResponse()->getBody()->getContents(),
                ]);
            }
        }

        return $job;
    }

    /**
     * Checking the connection with Smartcat
     *
     * @return AvailableResult
     */
    public function checkAvailable(TranslatorInterface $translator)
    {
        if ($translator->getSetting('account_id') && $translator->getSetting('api_key')) {
            return AvailableResult::yes();
        }

        return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
            '@translator' => $translator->label(),
            // ':configured' => $translator->url(),
        ]));
    }

    /**
     * Prepare data for sending
     *
     * @param  JobItemInterface[]  $jobItems
     * @return array<TranslatableItem>
     */
    protected function prepareDataForSending(array $jobItems): array
    {
        $translatableItems = [];

        foreach ($jobItems as $jobItem) {
            $translatableItem = new TranslatableItem();

            $translatableItem->setId($jobItem->id());
            $translatableItem->setName($jobItem->getSourceLabel());
            $translatableItem->setJobItem($jobItem);

            $items = $this->prepareSingleDataForSending($jobItem->getJob(), 'json', $jobItem->id());

            $segments = [];

            foreach ($items as $itemKey => $value) {
                $segments[] = new TranslatableItemSegment($itemKey, $value);
            }

            $translatableItem->setSegments($segments);

            $translatableItems[] = $translatableItem;
        }

        return $translatableItems;
    }

    /**
     * Prepare single data for sending
     *
     * @return false|string
     */
    protected function prepareSingleDataForSending(JobInterface $job, $type = 'json', $keepOnlyThisIndex = null)
    {
        $data_service = \Drupal::service('tmgmt.data');

        $data = array_filter($data_service->flatten($job->getData()), function ($value) {
            return ! (empty($value['#text']) || (isset($value['#translate']) && $value['#translate'] === false));
        });

        if ($type === 'json') {
            $items = [];
        } else {
            $items = '';
        }

        foreach ($data as $key => $value) {
            // when we are in multiple source files mode,
            // for proper translation return,
            // we need to keep keys like this: "13][title][0][value"
            // they need to start with the job item ID.
            // for this, for each item, we flatten the whole job (instead of job item)
            // and then remove the other job items from the flattened data, so that the file will contain
            // only the current job item.
            if ($keepOnlyThisIndex !== null) {
                if (strpos($key, $keepOnlyThisIndex.$data_service::TMGMT_ARRAY_DELIMITER) !== 0) {
                    continue;
                }
            }

            if ($type === 'json') {
                $items[$key] = $value['#text'];
            } else {
                $items .= str_replace(
                    ['@key', '@text'],
                    [$key, $value['#text']],
                    '<item key="@key"><text type="text/html"><![CDATA[@text]]></text></item>'
                );
            }
        }

        if ($type === 'json') {
            return $items;
        } else {
            return '<items>'.$items.'</items>';
        }
    }

    private function getOrCreateProject(JobInterface $job): ?Project
    {
        $workflowStage = $job->getSetting('workflow_stage');

        if ($job->getReference()) {
            $projectId = $job->getReference();
            $project = $this->cat->getProject($projectId);
        } else {
            $project = $this->hub->getOrCreateProject(
                null, 'Drupal TMGMT',
                $job->getSourceLangcode(),
                [$job->getTargetLangcode()],
                $this->getWorkflowStage($workflowStage),
            );

            if ($workflowStage !== 'manual-translation') {
                $this->setupMtEngineToSmartcatProject($project);
            }
        }

        return $project;
    }

    private function setupMtEngineToSmartcatProject(Project $project)
    {
        $mts = $this->cat->getAvailableProjectMT($project->getId());

        $selectedMT = null;

        $intelligentRouting = array_filter($mts, function ($mt) {
            return $mt->isIntelligentRouting();
        });

        /** @var ProjectMT|null $intelligentRouting */
        $intelligentRouting = $intelligentRouting[0] ?? null;

        if (
            ! is_null($intelligentRouting) &&
            $intelligentRouting->languagesCount() === $project->targetLocalesCount()
        ) {
            $selectedMT = $intelligentRouting;
        }

        if (is_null($selectedMT)) {
            $google = array_filter($mts, function ($mt) {
                return $mt->isGoogle();
            });

            /** @var ProjectMT|null $google */
            $google = $google[0] ?? null;

            if (
                ! is_null($google) &&
                $google->languagesCount() === $project->targetLocalesCount()
            ) {
                $selectedMT = $google;
            }
        }

        if (is_null($selectedMT)) {
            foreach ($mts as $mt) {
                if ($mt->languagesCount() === $project->targetLocalesCount()) {
                    $selectedMT = $mt;
                    break;
                }
            }
        }

        try {
            $this->cat->setupMtEngine($project->getId(), [$selectedMT->toArray()]);
        } catch (RequestException $exception) {
            \Drupal::logger('tmgmt_smartcat')->error("Failed to setup MT engine to project {$project->getId()}. Response: @response", [
                '@response' => $exception->getResponse()->getBody()->getContents(),
            ]);
        }

        $rule = ['ruleType' => 'MT', 'order' => 1];

        $translationStage = array_filter($project->getWorkflowStages(), function ($stage) {
            return $stage->isTranslation();
        });

        $translationStage = $translationStage[0] ?? null;

        if (! is_null($translationStage)) {
            $rule['confirmAtWorkflowStep'] = $translationStage->getId();
        }

        try {
            $this->cat->setupPreTranslationRules($project->getId(), [$rule]);
        } catch (RequestException $exception) {
            \Drupal::logger('tmgmt_smartcat')->error('Failed finding translation workflow step. Pretransaltion rule will be without automatic confirmation. Response: @response', [
                '@response' => $exception->getResponse()->getBody()->getContents(),
            ]);
        }
    }

    private function getWorkflowStage(string $name): array
    {
        $stages = [
            'mt' => [1],
            'mt-postediting' => [1, 7],
            'manual-translation' => [],
        ];

        return $stages[$name] ?? [];
    }

    private function initApiClients(Translator $translator)
    {
        $credentials = $this->getApiCredentials($translator);

        $this->cat = new SmartcatClient(...$credentials);

        $this->hub = new IntegrationHubClient(...$credentials);
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
