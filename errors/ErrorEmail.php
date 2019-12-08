<?php

namespace FsrImporter\Errors;

/**
 * This class is used to gather data for, render, and send an aggregated error message for
 * a WordPress user.
 */
class ErrorEmail {

    private $errors          = null;
    private $bodyTemplate    = '/error-email-body.twig';
    private $subjectTemplate = '/error-email-subject.twig';


    /**
     * The constructor takes an array of SkyloaderError instances.
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }


    public function send(string $emailAddress)
    {
        $subject = $this->getSubject();
        $body    = $this->getBody();
        wp_mail($emailAddress, $subject, $body);
    }


    public function getSubject(): string
    {
        $templatePath = $this->getTemplate($this->subjectTemplate);
        $subject      = \Timber::compile($templatePath);

        return $subject;
    }


    public function getBody(): string
    {
        $context['errors'] = $this->errors;
        $templatePath      = $this->getTemplate($this->bodyTemplate);
        $bodyHtml          = \Timber::compile($templatePath, $context);

        return $bodyHtml;
    }


    /**
     * Given a template name, check if there is a theme override. If there is an
     * override, return its path. Otherwise return path to plugin's default template.
     */
    private function getTemplate(string $templateName): string
    {
        $templatePath = \get_stylesheet_directory() . '/fsr-bi-account-import/templates/' . $templateName;

        if ( file_exists( $templatePath ) ) {
            return $templatePath;
        }

        return __DIR__ . '/' . $templateName;
    }
}