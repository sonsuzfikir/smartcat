<?php

namespace Drupal\tmgmt_smartcat\Database;

class Migrations
{
    public static function run()
    {
        CreateDocumentsTable::run();
    }
}
