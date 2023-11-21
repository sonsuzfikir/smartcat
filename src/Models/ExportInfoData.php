<?php

namespace Drupal\tmgmt_smartcat\Models;

class ExportInfoData implements DataInterface
{
    public const NotDisassembled = 1;

    public const ExportStarted = 2;

    public const Complete = 3;

    public const Failed = 4;

    private ?string $accountId;

    private ?string $documentId;

    private ?string $exportId;

    private string $mode;

    private int $exportType;

    private int $exportStatus;

    private ?string $failureReason;

    public function __construct(?string $accountId, ?string $documentId, ?string $exportId, string $mode, int $exportType, int $exportStatus, ?string $failureReason)
    {
        $this->accountId = $accountId;
        $this->documentId = $documentId;
        $this->exportId = $exportId;
        $this->mode = $mode;
        $this->exportType = $exportType;
        $this->exportStatus = $exportStatus;
        $this->failureReason = $failureReason;
    }

    public static function create(array $data): ExportInfoData
    {
        return new self(
            $data['accountId'],
            $data['smartcatDocumentId'],
            $data['exportId'],
            $data['mode'],
            $data['exportType'],
            $data['exportStatus'],
            $data['failureReason']
        );
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function setAccountId(string $accountId): ExportInfoData
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getDocumentId(): ?string
    {
        return $this->documentId;
    }

    public function setDocumentId(?string $documentId): ExportInfoData
    {
        $this->documentId = $documentId;

        return $this;
    }

    public function getExportId(): ?string
    {
        return $this->exportId;
    }

    public function setExportId(?string $exportId): ExportInfoData
    {
        $this->exportId = $exportId;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): ExportInfoData
    {
        $this->mode = $mode;

        return $this;
    }

    public function getExportType(): int
    {
        return $this->exportType;
    }

    public function setExportType(int $exportType): ExportInfoData
    {
        $this->exportType = $exportType;

        return $this;
    }

    public function getExportStatus(): int
    {
        return $this->exportStatus;
    }

    public function setExportStatus(int $exportStatus): ExportInfoData
    {
        $this->exportStatus = $exportStatus;

        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): ExportInfoData
    {
        $this->failureReason = $failureReason;

        return $this;
    }

    public function isFailed(): bool
    {
        return $this->exportStatus === self::Failed;
    }

    public function toArray(): array
    {
        return [
            'accountId' => $this->accountId,
            'smartcatDocumentId' => $this->documentId,
            'exportId' => $this->exportId,
            'mode' => $this->mode,
            'exportType' => $this->exportType,
            'exportStatus' => $this->exportStatus,
            'failureReason' => $this->failureReason,
        ];
    }
}
