<?php

namespace Drupal\tmgmt_smartcat\Models;

use Drupal\tmgmt\JobItemInterface;

class TranslatableItem
{
    private string $id;

    private string $name;

    /** @var TranslatableItemSegment[] */
    private array $segments;

    private JobItemInterface $jobItem;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): TranslatableItem
    {
        $this->name = $name;

        return $this;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setSegments(array $segments): TranslatableItem
    {
        $this->segments = $segments;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): TranslatableItem
    {
        $this->id = $id;

        return $this;
    }

    public function getLocJsonSegments(): array
    {
        return array_map(function ($segment) {
            return $segment->toLocJson();
        }, $this->segments);
    }

    public function getJobItem(): JobItemInterface
    {
        return $this->jobItem;
    }

    public function setJobItem(JobItemInterface $jobItem): self
    {
        $this->jobItem = $jobItem;

        return $this;
    }
}
