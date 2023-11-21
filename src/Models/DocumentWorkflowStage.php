<?php

namespace Drupal\tmgmt_smartcat\Models;

class DocumentWorkflowStage
{
    /** @var string */
    private $id;

    /** @var int|float */
    private $progress;

    /** @var string */
    private $status;

    public static function create(array $data): self
    {
        $stage = new self();

        $stage->setId($data['id'] ?? null)
            ->setProgress($data['progress'] ?? null)
            ->setStatus($data['status'] ?? null);

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
