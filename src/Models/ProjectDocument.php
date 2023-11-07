<?php

namespace Drupal\tmgmt_smartcat\Models;

use Drupal\tmgmt_smartcat\Services\SmartcatDocument;

class ProjectDocument
{
    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $sourceLocale;

    /** @var string */
    private $targetLocale;

    /** @var int */
    private $wordsCount;

    /** @var string */
    private $status;

    /** @var DocumentWorkflowStage[] */
    private $workflowStages;

    public static function create(array $data): ProjectDocument
    {
        $document = new self();

        $workflowStages = array_map(function ($stage) {
            return DocumentWorkflowStage::create($stage);
        }, $data['workflowStages'] ?? []);

        $document->setId($data['id'] ?? null)
            ->setName($data['name'] ?? null)
            ->setSourceLocale($data['sourceLanguage'] ?? null)
            ->setTargetLocale($data['targetLanguage'] ?? null)
            ->setWordsCount($data['wordsCount'] ?? null)
            ->setStatus($data['status'] ?? null)
            ->setWorkflowStages($workflowStages);

        return $document;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSourceLocale(): string
    {
        return $this->sourceLocale;
    }

    public function setSourceLocale(string $sourceLocale): self
    {
        $this->sourceLocale = $sourceLocale;

        return $this;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function setTargetLocale(string $targetLocale): self
    {
        $this->targetLocale = $targetLocale;

        return $this;
    }

    public function getWordsCount(): int
    {
        return $this->wordsCount;
    }

    public function setWordsCount(int $wordsCount): self
    {
        $this->wordsCount = $wordsCount;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getWorkflowStages(): array
    {
        return $this->workflowStages;
    }

    public function setWorkflowStages(array $workflowStages): self
    {
        $this->workflowStages = $workflowStages;

        return $this;
    }

    public function progress(): float
    {
        $maxProgress = count($this->workflowStages) * 100;
        $totalProgress = 0;

        foreach ($this->workflowStages as $workflowStage) {
            $totalProgress += $workflowStage->getProgress();
        }

        $progress = ($totalProgress / $maxProgress) * 100;

        return round($progress, 2);
    }

    public function updateProgress()
    {
        $document = (new SmartcatDocument())->findByDocumentId($this->id);

        if (! is_null($document)) {
            $document->setProgress($this->progress())->save();
        }
    }
}
