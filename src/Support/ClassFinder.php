<?php

declare(strict_types=1);

namespace Strux\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionException;

class ClassFinder
{
    /**
     * Recursively find all PHP classes in a directory.
     * * @param string $directory The full path to the directory to scan.
     * @param string $baseNamespace The base namespace mapping (e.g., 'App').
     * @param string|null $attributeClass Optional attribute class to filter by.
     * @return array<string> List of fully qualified class names.
     */
    public static function findClasses(string $directory, string $baseNamespace = 'App', ?string $attributeClass = null): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $classes = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();

                // We need to determine the class name from the file path relative to the "src" or root namespace directory.
                // This logic assumes PSR-4 structure where the directory matches the namespace.
                // We'll try to deduce the relative class path by finding where the base directory logic aligns.

                // Strategy: We assume $directory corresponds to some namespace segment.
                // However, without a fixed map, we usually rely on finding the 'src' folder or known root.
                // Let's use a simpler heuristic based on your examples: removing the base path.

                // Get path relative to the scanned directory
                // If scanning /var/www/src/Domain, and file is /var/www/src/Domain/User/User.php
                // Relative is User/User.php

                // HOWEVER, your examples show mapping from 'src' root.
                // Let's deduce the full namespace.

                $className = self::getClassNameFromFile($filePath);

                if ($className && class_exists($className)) {
                    if ($attributeClass) {
                        try {
                            $reflection = new ReflectionClass($className);
                            if (!$reflection->isAbstract() && !empty($reflection->getAttributes($attributeClass))) {
                                $classes[] = $className;
                            }
                        } catch (ReflectionException $e) {
                            continue;
                        }
                    } else {
                        $classes[] = $className;
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Heuristic to determine class name from file path.
     * Assumes standard PSR-4 'src/' -> 'App\' mapping.
     */
    private static function getClassNameFromFile(string $filePath): ?string
    {
        // 1. Find where 'src' starts
        $srcPos = strpos($filePath, 'src' . DIRECTORY_SEPARATOR);

        if ($srcPos !== false) {
            // 2. Extract relative path after src/
            // +4 length of "src" + separator
            $relativePath = substr($filePath, $srcPos + 4);

            // 3. Remove extension
            $relativePath = str_replace('.php', '', $relativePath);

            // 4. Convert slashes to namespace backslashes
            $classPath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            // 5. Prepend App base namespace
            return "App\\" . $classPath;
        }

        return null;
    }
}