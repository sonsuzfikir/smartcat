<?php

namespace Drupal\tmgmt_smartcat\Services;

use Drupal\Core\Database\Connection;
use Drupal\tmgmt\JobItemInterface;

class SmartcatDocument
{
    private const TABLE = 'tmgmt_smartcat_documents';

    private ?int $id;

    private int $jobItemId;

    private ?string $smartcatDocumentId;

    private $progress;

    private array $metadata;

    private Connection $db;

    public function __construct(
        int $jobItemId = 0,
        ?string $smartcatDocumentId = '',
        $progress = 0,
        array $metadata = [],
        ?int $id = null
    ) {
        $this->id = $id;
        $this->jobItemId = $jobItemId;
        $this->smartcatDocumentId = $smartcatDocumentId;
        $this->progress = $progress;
        $this->metadata = $metadata;

        /** @var Connection $connection */
        $this->db = \Drupal::service('database');
    }

    public static function create(array $data): self
    {
        $metadata = is_string($data['metadata']) ? json_decode($data['metadata'], true) : [];

        return new self(
            $data['job_item_id'],
            $data['smartcat_document_id'],
            $data['progress'],
            $metadata,
            $data['id'],
        );
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function jobItemId(): int
    {
        return $this->jobItemId;
    }

    public function smartcatDocumentId(): ?string
    {
        return $this->smartcatDocumentId;
    }

    public function progress()
    {
        return $this->progress;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * @throws \Exception
     */
    public function store()
    {
        $this->db->insert(self::TABLE)
            ->fields([
                'id' => $this->id,
                'job_item_id' => $this->jobItemId,
                'smartcat_document_id' => $this->smartcatDocumentId,
                'progress' => $this->progress,
                'metadata' => json_encode($this->metadata),
            ])
            ->execute();
    }

    public function save()
    {
        $this->db->update(self::TABLE)
            ->fields([
                'progress' => $this->progress,
                'metadata' => json_encode($this->metadata),
            ])
            ->condition('id', $this->id)
            ->execute();
    }

    public function findByJobItemId(int $id): ?SmartcatDocument
    {
        $query = $this->db->select(self::TABLE, 't');
        $query->fields('t');
        $query->condition('job_item_id', $id);

        $document = $query->execute()->fetch(\PDO::FETCH_ASSOC);

        return $document ? self::create($document) : null;
    }

    public function findByDocumentId(string $id): ?SmartcatDocument
    {
        $query = $this->db->select(self::TABLE, 't');
        $query->fields('t');
        $query->condition('smartcat_document_id', $id);

        $document = $query->execute()->fetch(\PDO::FETCH_ASSOC);

        return $document ? self::create($document) : null;
    }

    public function setProgress($progress): SmartcatDocument
    {
        $this->progress = $progress;

        return $this;
    }

    public function getReadyToExportJobItemIds(int $limit = 5): array
    {
        $query = $this->db->select('tmgmt_job_item', 'j')
            ->fields('j', ['tjiid', 'state']);

        $query->join('tmgmt_smartcat_documents', 'd', 'd.job_item_id = j.tjiid');

        $query->fields('d', ['progress', 'job_item_id'])
            ->condition('j.state', JobItemInterface::STATE_ACTIVE)
            ->condition('d.progress', 100)
            ->range(0, $limit);

        $items = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $ids = [];

        foreach ($items as $item) {
            $ids[$item['tjiid']] = $item['tjiid'];
        }

        return $ids;
    }
}
