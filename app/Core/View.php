<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Basic MVC View template compiler and renderer
 */
class View
{
    /**
     * Render a view template inside a layout
     */
    public static function render(string $viewPath, array $data = [], string $layout = 'main'): void
    {
        $viewsDir = dirname(__DIR__, 2) . '/views';
        $viewFile = "{$viewsDir}/{$viewPath}.php";

        if (!file_exists($viewFile)) {
            throw new Exception("View template file not found: {$viewFile}");
        }

        // Start output buffering for view content
        extract($data);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Check if layout is specified
        if ($layout) {
            $layoutFile = "{$viewsDir}/layouts/{$layout}.php";
            if (!file_exists($layoutFile)) {
                throw new Exception("Layout file not found: {$layoutFile}");
            }
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Safely escape values for XSS protection
     */
    public static function escape($value): string
    {
        if (null === $value) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
