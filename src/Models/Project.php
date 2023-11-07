<?php

namespace Drupal\tmgmt_smartcat\Models;

class Project
{
    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var string|null */
    private $deadline;

    /** @var string */
    private $status;

    /** @var string */
    private $sourceLocale;

    /** @var string[] */
    private $targetLocales;

    /** @var ProjectDocument[] */
    private $documents;

    /** @var ProjectWorkflowStage[] */
    private $workflowStages;

    /** @var string[] */
    private $internalTags;

    /** @var string|null */
    private $externalTag;

    public static function create(array $data): self
    {
        $project = new self();

        $documents = array_map(function ($document) {
            return ProjectDocument::create($document);
        }, $data['documents'] ?? []);

        $workflowStages = array_map(function ($stage) {
            return ProjectWorkflowStage::create($stage);
        }, $data['workflowStages'] ?? []);

        $project->setId($data['id'] ?? null)
            ->setName($data['name'] ?? null)
            ->setDeadline($data['deadline'] ?? null)
            ->setStatus($data['status'] ?? null)
            ->setSourceLocale($data['sourceLanguage'] ?? null)
            ->setTargetLocales($data['targetLanguages'] ?? [])
            ->setDocuments($documents)
            ->setWorkflowStages($workflowStages)
            ->setInternalTags($data['internalTags'] ?? [])
            ->setExternalTag($data['externalTag'] ?? null);

        return $project;
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

    public function getDeadline(): ?string
    {
        return $this->deadline;
    }

    public function setDeadline(?string $deadline): self
    {
        $this->deadline = $deadline;

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

    public function getSourceLocale(): string
    {
        return $this->sourceLocale;
    }

    public function setSourceLocale(string $sourceLocale): self
    {
        $this->sourceLocale = $sourceLocale;

        return $this;
    }

    public function getTargetLocales(): array
    {
        return $this->targetLocales;
    }

    public function setTargetLocales(array $targetLocales): self
    {
        $this->targetLocales = $targetLocales;

        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): self
    {
        $this->documents = $documents;

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

    public function getInternalTags(): array
    {
        return $this->internalTags;
    }

    public function setInternalTags(array $internalTags): self
    {
        $this->internalTags = $internalTags;

        return $this;
    }

    public function getExternalTag(): ?string
    {
        return $this->externalTag;
    }

    public function setExternalTag(?string $externalTag): self
    {
        $this->externalTag = $externalTag;

        return $this;
    }

    public function updateDocumentsProgress()
    {
        foreach ($this->getDocuments() as $document) {
            $document->updateProgress();
        }
    }

    public function targetLocalesCount(): int
    {
        return count($this->targetLocales);
    }
}
