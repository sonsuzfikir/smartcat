<?php

namespace Drupal\tmgmt_smartcat\Models;

class ImportDocumentResponse
{
    private string $documentId;

    public static function create(array $data): ImportDocumentResponse
    {
        $response = new ImportDocumentResponse();
        $response->setDocumentId($data['documentId']);

        return $response;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function setDocumentId(string $documentId): ImportDocumentResponse
    {
        $this->documentId = $documentId;

        return $this;
    }
}
