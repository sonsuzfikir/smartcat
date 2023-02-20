<?php

/**
 * @file
 * Contains Drupal\tmgmt_smartcat\SmartcatTranslatorUi.
 */

namespace Drupal\tmgmt_smartcat;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use GuzzleHttp\Exception\ClientException;

/**
 * Smartcat translator UI.
 */
class SmartcatTranslatorUi extends TranslatorPluginUiBase
{
    /**
     * Configuration form for Smartcat
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $translator = $form_state->getFormObject()->getEntity();

        $form['registration_link'] = [
            '#type' => 'markup',
            '#markup' => t('You can find Account ID and API Key in your Smartcat account'),
        ];
        $form['server'] = [
            '#type' => 'select',
            '#default_value' => $translator->getSetting('server'),
            '#title' => t('Smartcat server'),
            '#options' => [
                'EU' => t('Europe'),
                'US' => t('USA'),
                'EA' => t('Asia'),
            ],
        ];
        $form['account_id'] = array(
            '#type' => 'textfield',
            '#title' => t('Smartcat Account ID'),
            '#default_value' => $translator->getSetting('account_id'),
        );
        $form['api_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Smartcat API Secret Key'),
            '#default_value' => $translator->getSetting('api_key'),
        );

        return $form;
    }

    /**
     * Validation for the Smartcat configuration form
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return void
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateConfigurationForm($form, $form_state);
        $form_state->cleanValues();
        $form_state->getValues();

        $wrapperSettings = $form['plugin_wrapper']['settings'];
        $settings = $form_state->getValues()['settings'];

        $server = $settings['server'];
        $accountId = $settings['account_id'];
        $apiKey = $settings['api_key'];

        try {
            (new SmartcatApi($accountId, $apiKey, $server))->accountDetails();
        } catch (ClientException $e) {
            \Drupal::logger('tmgmt_smartcat')->error(t('The Account ID or API Key is invalid. Please, check the credentials and try again.'));
            $form_state->setError($wrapperSettings['api_key'], t('The "Account ID" or "API Secret Key" is not valid.'));
        }
    }

    /**
     * Form for uploading completed translations from Smartcat
     *
     * @param JobInterface $job
     * @return array[]
     */
    public function checkoutInfo(JobInterface $job)
    {
        return [
            'job' => [
                '#type' => 'value',
                '#value' => $job,
            ],
            'download' => [
                '#type' => 'submit',
                '#value' => t('Download translations'),
                '#submit' => [[$this, 'download']],
            ],
        ];
    }

    /**
     * Action for downloading translated documents from Smartcat
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return void
     */
    public function download(array $form, FormStateInterface $form_state)
    {
        $form_state->cleanValues();
        $job = $form_state->getValue('job');

        \Drupal::service('tmgmt_smartcat.document')->import($job);
    }

    public function checkoutSettingsForm(array $form, FormStateInterface $form_state, JobInterface $job)
    {
        $form['workflow_stage'] = [
            '#type' => 'select',
            '#default_value' => 'mt',
            '#title' => t('Workflow stages'),
            '#options' => [
                'mt' => t('AI translation'),
                'mt-postediting' => t('AI translation + human review'),
                'manual-translation' => t('Manual translation'),
            ],
            '#description' => t('Please select the appropriate workflow stages for your Smartcat project'),
        ];

        return parent::checkoutSettingsForm($form, $form_state, $job);
    }
}
