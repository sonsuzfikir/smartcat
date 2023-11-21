<?php

namespace Drupal\tmgmt_smartcat\Models;

class ExportBeginData implements DataInterface
{
    private $jobItemId;

    private string $accountId;

    private string $projectId;

    private string $targetLocale;

    public function __construct($jobItemId, string $accountId, string $projectId, string $targetLocale)
    {
        $this->jobItemId = $jobItemId;
        $this->accountId = $accountId;
        $this->projectId = $projectId;
        $this->targetLocale = $targetLocale;
    }

    public static function create($jobItemId, string $accountId, string $projectId, string $targetLocale): ExportBeginData
    {
        return new self($jobItemId, $accountId, $projectId, $targetLocale);
    }

    /**
     * @return mixed
     */
    public function getJobItemId()
    {
        return $this->jobItemId;
    }

    public function setJobItemId(int $jobItemId): ExportBeginData
    {
        $this->jobItemId = $jobItemId;

        return $this;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function setAccountId(string $accountId): ExportBeginData
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function setProjectId(string $projectId): ExportBeginData
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function setTargetLocale(string $targetLocale): ExportBeginData
    {
        $this->targetLocale = $targetLocale;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'drupalJobItemId' => $this->jobItemId,
            'scAccountId' => $this->accountId,
            'scProjectId' => $this->projectId,
            'targetLanguage' => $this->targetLocale,
        ];
    }
}
