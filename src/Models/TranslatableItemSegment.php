<?php

namespace Drupal\tmgmt_smartcat\Models;

class TranslatableItemSegment
{
    private string $key;

    private string $sourceValue;

    public function __construct(string $key, string $sourceValue)
    {
        $this->key = $key;
        $this->sourceValue = $sourceValue;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): TranslatableItemSegment
    {
        $this->key = $key;

        return $this;
    }

    public function getSourceValue(): string
    {
        return $this->sourceValue;
    }

    public function setSourceValue(string $sourceValue): TranslatableItemSegment
    {
        $this->sourceValue = $sourceValue;

        return $this;
    }

    public function toLocJson(): array
    {
        return [
            'id' => $this->key,
            'sourceText' => $this->sourceValue,
            'format' => 'auto',
            'existingTranslation' => '',
        ];
    }
}
