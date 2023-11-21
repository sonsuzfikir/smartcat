<?php

namespace Drupal\tmgmt_smartcat\Models;

class ExportedItem
{
    private string $id;

    private ?string $context;

    private string $source;

    private string $translation;

    private ?array $properties;

    public function __construct(string $id, ?string $context, string $source, string $translation, ?array $properties)
    {
        $this->id = $id;
        $this->context = $context;
        $this->source = $source;
        $this->translation = $translation;
        $this->properties = $properties;
    }

    public static function create(array $data): ExportedItem
    {
        return new self(
            $data['id'],
            $data['context'] ?? null,
            $data['source'],
            $data['translation'],
            $data['properties'] ?? null
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): ExportedItem
    {
        $this->id = $id;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): ExportedItem
    {
        $this->context = $context;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): ExportedItem
    {
        $this->source = $source;

        return $this;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): ExportedItem
    {
        $this->translation = $translation;

        return $this;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): ExportedItem
    {
        $this->properties = $properties;

        return $this;
    }
}
