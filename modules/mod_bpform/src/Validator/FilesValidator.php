<?php

/*
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Validator;


class FilesValidator
{

    /**
     * Remove empty files entries.
     *
     * @param   array  $files  Files array.
     *
     * @return array
     */
    public static function filterFiles(array $files): array
    {
        $files = array_map(static function ($file) {
            if (!is_array($file)) {
                return null;
            }

            // a multi-file field
            if (array_key_exists(0, $file)) {
                foreach ($file as $idx => $entry) {
                    $file[$idx] = self::filterFilesArray($entry);
                }

                return $file;
            }

            return self::filterFilesArray($file);
        }, $files);

        return array_filter($files);
    }

    /**
     * Check single file entry.
     *
     * @param   array  $file
     *
     * @return array
     */
    private static function filterFilesArray(array $file): array
    {
        if (!array_key_exists('name', $file) || empty($file['name'])) {
            return [];
        }
        if (!array_key_exists('tmp_name', $file) || empty($file['tmp_name'])) {
            return [];
        }
        if (!array_key_exists('size', $file) || (int)$file['size'] === 0) {
            return [];
        }

        return $file;
    }
}