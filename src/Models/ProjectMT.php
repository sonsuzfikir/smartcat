<?php

namespace Drupal\tmgmt_smartcat\Models;

class ProjectMT
{
    private string $id;

    private string $name;

    private array $languages;

    public function __construct(string $id, string $name, array $languages)
    {
        $this->id = $id;
        $this->name = $name;
        $this->languages = $languages;
    }

    public static function create(array $data): ProjectMT
    {
        return new self($data['Id'], $data['Name'], $data['Languages']);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function isIntelligentRouting(): bool
    {
        return $this->getId() === 'engine:Intelligent Routing';
    }

    public function isGoogle(): bool
    {
        return $this->getId() === 'engine:Google NMT';
    }

    public function languagesCount(): int
    {
        return count($this->languages);
    }

    public function toArray(): array
    {
        return [
            'Id' => $this->getId(),
            'Name' => $this->getName(),
            'Languages' => $this->getLanguages(),
        ];
    }
}
