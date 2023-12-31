<?php

namespace Drupal\tmgmt_smartcat;

class SmartcatEndpoints
{
    public const ACCOUNT = '/api/integration/v1/account';

    public const CREATE_PROJECT = '/api/integration/v1/project/create';

    public const UPLOAD_FILE_TO_PROJECT = '/api/integration/v1/project/document';

    public const PROJECT = '/api/integration/v1/project/';

    public const DOCUMENT_TASK_ID = '/api/integration/v1/document/export';

    public const DOCUMENT_EXPORT = '/api/integration/v1/document/export/';

    public const PROJECT_AVAILABLE_MT = '/api/integration/v1/project/{project}/mt/available';

    public const PROJECT_SETUP_MT = '/api/integration/v1/project/{project}/mt';

    public const PROJECT_SETUP_PRE_TRANSLATION_RULES = '/api/integration/v1/project/{project}/pretranslation-rules';
}
