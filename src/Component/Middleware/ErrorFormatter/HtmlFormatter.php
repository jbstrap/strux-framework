<?php

declare(strict_types=1);

namespace Strux\Component\Middleware\ErrorFormatter;

use Throwable;

class HtmlFormatter extends AbstractFormatter
{
    protected array $contentTypes = [
        'text/html',
        'application/xhtml+xml',
    ];

    protected function format(Throwable $error): string
    {
        $type = get_class($error);
        $code = $this->determineStatusCode($error);
        $message = htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = "{$code} - {$this->getStatusPhrase($code)}";

        $details = '';
        if ($this->appDebug) {
            $file = htmlspecialchars($error->getFile() ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $line = $error->getLine();
            $trace = htmlspecialchars($error->getTraceAsString(), ENT_QUOTES, 'UTF-8');
            $details = <<<HTML
                <div class="details">
                    <p><strong>Type:</strong> {$type}</p>
                    <p><strong>File:</strong> {$file}</p>
                    <p><strong>Line:</strong> {$line}</p>
                    <h4>Stack Trace:</h4>
                    <pre class="trace">{$trace}</pre>
                </div>
            HTML;
        }

        $css = $this->getCss();

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>{$title}</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>{$css}</style>
            </head>
            <body>
                <div class="container">
                    <h1>{$code}</h1>
                    <h2>{$this->getStatusPhrase($code)}</h2>
                    <p class="message">{$message}</p>
                    {$details}
                </div>
            </body>
            </html>
        HTML;
    }

    private function getStatusPhrase(int $code): string
    {
        $phrases = [
            400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found',
            405 => 'Method Not Allowed', 415 => 'Unsupported Media Type', 419 => 'CSRF Token Mismatch', 500 => 'Internal Server Error', 503 => 'Service Unavailable',
        ];
        return $phrases[$code] ?? 'Application Error';
    }

    private function getCss(): string
    {
        return <<<CSS
            body { background-color: #f4f4f4; color: #333; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; margin: 0; padding: 20px; }
            .container { max-width: 800px; margin: 50px auto; padding: 30px; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
            h1 { font-size: 4em; margin: 0; color: #e74c3c; }
            h2 { font-size: 1.5em; margin: 0 0 20px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .message { font-size: 1.2em; color: #333; }
            .details { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
            .trace { background-color: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word; font-size: 0.9em; line-height: 1.6; }
        CSS;
    }
}