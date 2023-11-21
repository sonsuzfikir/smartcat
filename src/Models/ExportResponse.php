<?php

namespace Drupal\tmgmt_smartcat\Models;

class ExportResponse
{
    /** @var array<ExportedItem>|null */
    private ?array $items;

    private ?array $properties;

    private ExportInfoData $exportInfo;

    public function __construct(?array $items, ?array $properties, ExportInfoData $exportInfo)
    {
        $this->items = $items;
        $this->properties = $properties;
        $this->exportInfo = $exportInfo;
    }

    public static function create(array $data): ExportResponse
    {
        return new self(
            ! empty($data['items']) ? array_map(fn ($item) => ExportedItem::create($item), $data['items']) : null,
            $data['properties'] ?? null,
            ExportInfoData::create($data['exportInfo'])
        );
    }

    public function getItems(): ?array
    {
        return $this->items;
    }

    public function setItems(?array $items): ExportResponse
    {
        $this->items = $items;

        return $this;
    }

    public function getProperties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): ExportResponse
    {
        $this->properties = $properties;

        return $this;
    }

    public function getExportInfo(): ExportInfoData
    {
        return $this->exportInfo;
    }

    public function setExportInfo(ExportInfoData $exportInfo): ExportResponse
    {
        $this->exportInfo = $exportInfo;

        return $this;
    }

    public function hasItems(): bool
    {
        return ! empty($this->items);
    }
}
