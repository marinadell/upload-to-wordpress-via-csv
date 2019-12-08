<?php

namespace FsrImporter\Errors;

use Symfony\Component\Yaml\Yaml;

/**
 * This class reads the Skyloader error information from a YAML file and then
 * creates a searchable library of that error information.
 */
class SkyloaderErrorFactory {

    private $yamlPath = __DIR__ . '/errors.yaml';
    private $yamlHash = null;


    public function readYamlFile(): void
    {
        $this->yamlHash = Yaml::parseFile($this->yamlPath);
    }


    /**
     * This function searches the YAML has to find the error code and then
     * uses the associated information to construct a new SkyloaderError.
     */
    public function createError(string $errorCode=null): ?SkyloaderError
    {
        $errorHash = $this->yamlHash[$errorCode] ?? null;

        if (!$errorHash) {
            return null;
        }

        $errorValues = [
            'skyloaderErrorCode' => $errorCode,
            'errorTitle'         => $errorHash['title'] ?? null,
            'statusText'         => $errorHash['error-text'] ?? null,
            'ctaText'            => $errorHash['cta-text'] ?? null,
        ];

        $error = new SkyloaderError($errorValues);

        return $error;
    }


    /**
     * Is the error code provided a reportable error? Should the rebates site
     * be concerned about it?
     *
     * Only reportable errors are stored in the YAML file.
     */
    public function isReportableError(string $errorCode=null): bool
    {
        if ($this->createError($errorCode)) {
            return true;
        }

        return false;
    }
}