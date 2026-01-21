<?php

declare(strict_types=1);

namespace WordpressStarter\Exceptions;

/**
 * Exception thrown when a template file cannot be found.
 */
class TemplateNotFoundException extends ThemeException
{
    public function __construct(string $templatePath, int $code = 0, ?\Throwable $previous = null)
    {
        $message = "Template not found: {$templatePath}";
        parent::__construct($message, $code, $previous);
    }
}
