<?php

namespace Drupal\tmgmt_smartcat\Models;

class ProjectWorkflowStage
{
    /** @var string */
    private $id;

    /** @var int|float */
    private $progress;

    /** @var string */
    private $type;

    public static function create(array $data): ProjectWorkflowStage
    {
        $stage = new self();

        $stage->setId($data['id'] ?? null)
            ->setProgress($data['progress'] ?? null)
            ->setType($data['stageType'] ?? null);

        return $stage;
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

    /**
     * @return float|int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param  float|int  $progress
     */
    public function setProgress($progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isTranslation(): bool
    {
        return $this->type === 'translation';
    }
}
