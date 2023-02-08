<?php

/**
 * @file
 * Contains \Drupal\tmgmt_smartcat\Plugin\tmgmt\Translator\SmartcatTranslator.
 */

namespace Drupal\tmgmt_smartcat\Plugin\tmgmt\Translator;

use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_smartcat\SmartcatApi;
use GuzzleHttp\Exception\ClientException;

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
    /**
     * @var SmartcatApi
     */
    private SmartcatApi $smartcatApi;

    /**
     * Sending documents to Smartcat
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws \Drupal\tmgmt\TMGMTException
     */
    public function requestTranslation(JobInterface $job)
    {
        $this->smartcatApi = new SmartcatApi(
            $job->getTranslator()->getSetting('account_id'),
            $job->getTranslator()->getSetting('api_key'),
            $job->getTranslator()->getSetting('server'),
        );

        return $this->requestJobItemsTranslation($job->getItems());
    }

    /**
     * Create a Smartcat project and submit items from a job
     *
     * @param array $job_items
     * @return JobInterface
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function requestJobItemsTranslation(array $job_items)
    {
        /** @var Job $job */
        $job = reset($job_items)->getJob();
        $files = $this->prepareDataForSending($job);

        try {
            $workflowStage = $job->getSetting('workflow_stage');
            $stages = ['Translation'];
            if ($workflowStage === 'mt-postediting') {
                $stages[] = 'Postediting';
            }
            $projectId = $this->smartcatApi->createProject(
                $job->getSourceLangcode(),
                $job->getTargetLangcode(),
                $stages,
                $job->id()
            );
            $this->setupMtEngineToSmartcatProject($projectId);
            $job->reference = $projectId;
            $job->save();
            $job->addMessage('A new project was created. Project ID: @id', ['@id' => $projectId], 'debug');
        } catch (ClientException $e) {
            $job->rejected('Failed to create a project in Smartcat. Contact us at support@smartcat.com');
            \Drupal::logger('tmgmt_smartcat')->error('Failed to create a project in Smartcat.');
            return $job;
        }

        try {
            $this->smartcatApi->addDocumentToProject($projectId, $files);
            $job->submitted(
                'Job has been successfully submitted for translation. Project ID is: %project_id',
                array('%project_id' => $projectId)
            );
        } catch (ClientException $e) {
            $job->rejected('Your job has been rejected. Contact us at support@smartcat.com');
            \Drupal::logger('tmgmt_smartcat')->error('Job has been rejected.');
            return $job;
        }

        return $job;
    }

    /**
     * Checking the connection with Smartcat
     *
     * @param TranslatorInterface $translator
     * @return AvailableResult
     */
    public function checkAvailable(TranslatorInterface $translator)
    {
        if ($translator->getSetting('account_id') && $translator->getSetting('api_key')) {
            return AvailableResult::yes();
        }

        return AvailableResult::no(t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
            '@translator' => $translator->label(),
            ':configured' => $translator->url(),
        ]));
    }

    /**
     * Prepare data for sending
     *
     * @param JobInterface $job
     * @param $type
     * @return array
     */
    protected function prepareDataForSending(JobInterface $job, $type = 'json')
    {
        $files = [];
        foreach ($job->getData() as $key => $jobData) {
            $fileBaseName = $key;
            if (isset($jobData['title'][0]['value']['#text']) && !empty($jobData['title'][0]['value']['#text'])) {
                $fileBaseName = $jobData['title'][0]['value']['#text'];
            }
            $fileBaseName = preg_replace("#[[:punct:]]#", "", $fileBaseName);
            $fileContent = $this->prepareSingleDataForSending($job, $type, $key);
            if (isset($files[$fileBaseName])) {
                $fileBaseName .= '-' . rand();
            }
            $fileBaseName .= '.' . $type;
            $files[$fileBaseName] = $fileContent;
        }
        return $files;
    }

    /**
     * Prepare single data for sending
     *
     * @param JobInterface $job
     * @param $type
     * @param $keepOnlyThisIndex
     * @return false|string
     */
    protected function prepareSingleDataForSending(JobInterface $job, $type = 'json', $keepOnlyThisIndex = null)
    {
        $data_service = \Drupal::service('tmgmt.data');

        $data = array_filter($data_service->flatten($job->getData()), function ($value) {
            return !(empty($value['#text']) || (isset($value['#translate']) && $value['#translate'] === FALSE));
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
                if (strpos($key, $keepOnlyThisIndex . $data_service::TMGMT_ARRAY_DELIMITER) !== 0) {
                    continue;
                }
            }

            if ($type === 'json') {
                $items[$key] = $value['#text'];
            } else {
                $items .= str_replace(
                    array('@key', '@text'),
                    array($key, $value['#text']),
                    '<item key="@key"><text type="text/html"><![CDATA[@text]]></text></item>'
                );
            }
        }

        if ($type === 'json') {
            return json_encode($items);
        } else {
            return '<items>' . $items . '</items>';
        }
    }

    private function setupMtEngineToSmartcatProject(string $smartcatProjectId)
    {
        $selectedMt = NULL;

        $project = $this->smartcatApi->getProject($smartcatProjectId);

        $availableProjectMT = $this->smartcatApi->availableProjectMT($smartcatProjectId);

        $targetLocales = $project['targetLanguages'];

        $maybeIntelligentRouting = array_filter($availableProjectMT, function ($mt) {
            return $mt['Id'] === 'engine:Intelligent Routing';
        });

        $maybeIntelligentRouting = array_shift($maybeIntelligentRouting);

        if (
            !is_null($maybeIntelligentRouting) &&
            count($maybeIntelligentRouting['Languages']) === count($targetLocales)
        ) {
            $selectedMt = $maybeIntelligentRouting;
        }

        if (is_null($selectedMt)) {
            $maybeGoogle = array_filter($availableProjectMT, function ($mt) {
                return $mt['Id'] === 'engine:Google NMT';
            });

            $maybeGoogle = array_shift($maybeGoogle);

            if (
                !is_null($maybeGoogle) &&
                count($maybeGoogle['Languages']) === count($targetLocales)
            ) {
                $selectedMt = $maybeGoogle;
            }
        }

        if (is_null($selectedMt)) {
            foreach ($availableProjectMT as $mt) {
                if (count($mt['Languages']) === count($targetLocales)) {
                    $selectedMt = $mt;
                    break;
                }
            }
        }

        if (!is_null($selectedMt)) {
            $this->smartcatApi->setupMtEngines($smartcatProjectId, [
                $selectedMt
            ]);
        } else {
            \Drupal::logger('tmgmt_smartcat')->error("Failed to setup MT engine to project $smartcatProjectId", [
                'targetLocales' => $targetLocales,
                'smartcatProjectId' => $smartcatProjectId,
                'availableProjectMT' => $availableProjectMT
            ]);
        }

        // adding pre translation rules

        $rule = [
            'ruleType' => 'MT',
            'order' => 1
        ];

        $translationStage = array_filter($project['workflowStages'], function ($stage) {
            return $stage['stageType'] === 'translation';
        });

        $translationStage = array_shift($translationStage);

        if (!is_null($translationStage)) {
            $rule['confirmAtWorkflowStep'] = $translationStage['id'];
        } else {
            \Drupal::logger('tmgmt_smartcat')->error('Failed finding translation workflow step. Pretransaltion rule will be without automatic confirmation');
        }

        $this->smartcatApi->setupPreTranslationRules($smartcatProjectId, [
            $rule
        ]);
    }
}
