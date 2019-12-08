<?php

namespace FsrImporter\Errors;

class SkyloaderError {

    private $skyloaderErrorCode = null;
    private $errorTitle         = null;
    private $statusText         = null;
    private $ctaText            = null;
    private $DCN                = null;
    private $distributorName    = null;
    private $dcnPostId          = null;

    public function __construct(array $values)
    {
        $this->skyloaderErrorCode = $values['skyloaderErrorCode'] ?? null;
        $this->errorTitle         = $values['errorTitle'] ?? null;
        $this->statusText         = $values['statusText'] ?? null;
        $this->ctaText            = $values['ctaText'] ?? null;
        $this->DCN                = $values['DCN'] ?? null;
        $this->distributorName    = $values['distributorName'] ?? null;
        $this->dcnPostId          = $values['dcnPostId'] ?? null;
    }

    public function getSkyloaderErrorCode(): ?string
    {
        return $this->skyloaderErrorCode;
    }

    public function getErrorTitle(): ?string
    {
        return $this->errorTitle;
    }

    public function getDcnPostId(): ?string
    {
        return $this->dcnPostId;
    }

    public function setDcnPostId(string $dcnPostId): void
    {
        $this->dcnPostId = $dcnPostId;
    }

    public function getStatusText(): ?string
    {
        $result = str_replace('{dist-name}', $this->distributorName, $this->statusText);
        return $result;
    }

    public function setDCN(string $DCN): void
    {
        $this->DCN = $DCN;
    }

    public function setDistributorName(string $distributorName): void
    {
        $this->distributorName = $distributorName;
    }

    public function getDCN(): ?string
    {
        return $this->DCN;
    }

    public function getCtaText(): ?string
    {
        return $this->ctaText;
    }

    public function getDistributorName(): ?string
    {
        return $this->distributorName;
    }
}
