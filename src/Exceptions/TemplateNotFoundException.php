<?php

declare(strict_types=1);

namespace WordpressStarter\Exceptions;

/**
 * Exception thrown when a template file cannot be found.
 */
class TemplateNotFoundException extends ThemeException
{
    private string $templatePath;

    public function __construct(string $templatePath, int $code = 0, ?\Throwable $previous = null)
    {
        $this->templatePath = $templatePath;
        // Only the basename is shown in the message: the full path can leak the server's
        // absolute directory structure if this message is ever surfaced (e.g. logged verbatim
        // to an error page). Callers that need the full path use getTemplatePath().
        $message = 'Template not found: ' . basename($templatePath);
        parent::__construct($message, $code, $previous);
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }
}
