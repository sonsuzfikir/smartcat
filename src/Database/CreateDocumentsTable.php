<?php

namespace Drupal\tmgmt_smartcat\Database;

use Drupal\Core\Database\Schema;

class CreateDocumentsTable
{
    private Schema $schema;

    private const TABLE = 'tmgmt_smartcat_documents';

    public function __construct()
    {
        $this->schema = \Drupal::database()->schema();
    }

    public static function run()
    {
        (new self())();
    }

    public function __invoke()
    {
        if (! $this->schema->tableExists(self::TABLE)) {
            $this->schema->createTable(self::TABLE, [
                'description' => 'Smartcat documents for TMGMT Jobs',
                'fields' => [
                    'id' => [
                        'type' => 'serial',
                        'unsigned' => true,
                        'not null' => true,
                        'description' => 'Primary key',
                    ],
                    'job_item_id' => [
                        'type' => 'int',
                        'unsigned' => true,
                        'not null' => false,
                        'default' => null,
                        'description' => 'TMGMT Job Item ID',
                    ],
                    'smartcat_document_id' => [
                        'type' => 'varchar',
                        'length' => 255,
                        'not null' => false,
                        'default' => null,
                        'description' => 'Smartcat document ID',
                    ],
                    'progress' => [
                        'type' => 'varchar',
                        'length' => 255,
                        'not null' => false,
                        'default' => null,
                        'description' => 'Smartcat document progress',
                    ],
                    'metadata' => [
                        'type' => 'text',
                        'not null' => false,
                        'default' => null,
                        'description' => 'Metadata',
                    ],
                ],
                'primary key' => ['id'],
            ]);
        }
    }
}
