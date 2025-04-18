<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Validator;

use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\Registry\Registry;

class FormValidator
{

    /**
     * @var Registry
     */
    protected $params;
    /**
     * @var CMSApplication
     *
     */
    protected $app;

    protected $config;

    public function __construct(ApplicationInterface $app, array &$config)
    {
        $this->params = $config['params'];
        $this->app    = $app;
        $this->config = $config;
    }


    /**
     * Validate file using is size
     *
     * @param   array   $input  Files input array.
     * @param   object  $field  Field object.
     *
     * @return array
     *
     * @since 1.2.0
     */
    public function validateFiles(array $input, object $field): array
    {
        $errors = [];

        // Calculate files size
        $totalsize = 0;
        foreach ($input as $file) {
            $totalsize += $file['size'];
            if ((!empty($file['name']) || $field->required) && !$this->validateFile($file, $field)) {
                $errors[] = Text::sprintf('MOD_BPFORM_INPUT_INVALID_FILE_FORMAT_S', $file['name'], $field->title);
            }
        }

        // If files size limit exceeded
        if ($field->maxtotalfilesize < ($totalsize / 1024 / 1024)) {
            $errors[] = Text::sprintf('MOD_BPFORM_INPUT_MAXTOTALFILESIZE_EXCEEDED_S', $field->title,
                $field->maxtotalfilesize);
        }

        return $errors;
    }

    /**
     * Validate input file against field "accept" attribute.
     *
     * @param   array   $file   Input file array.
     * @param   object  $field  Field object
     *
     * @return bool
     */
    protected function validateFile(array $file, object $field): bool
    {
        $result = true;

        // Get types
        $types = $this->getFileTypes($field->mimeaccept);

        // Check the file against each type
        if (!empty($types)) {
            $result    = false;
            $extension = '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            foreach ($types as $type) {
                // It is an extension and it's on the list
                if (str_starts_with($type, '.') && strtolower($type) === strtolower($extension)) {
                    return true;
                }

                // It is a mime
                if (str_contains($type, '/') && fnmatch($type, $file['type'])) {
                    return true;
                }
            }
        }

        return $result;
    }

    /**
     * Get a list of file types and mimes.
     *
     * @param   string  $accept  The content of accept attribute.
     *
     * @return array
     */
    protected function getFileTypes(string $accept): array
    {
        $parts = explode(',', $accept);
        $parts = array_map("trim", $parts);

        return array_filter($parts);
    }

    /**
     * Check if this e-mail is valid.
     *
     * @param   string  $email  E-mail address to validate.
     *
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return Mail::validateAddress($email, 'php');
    }
}