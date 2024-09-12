<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Form\Rule;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use SimpleXMLElement;
use UnexpectedValueException;

final class FieldsRule extends FormRule
{


    /**
     * Method to test the fields subform.
     *
     * @param   SimpleXMLElement  $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value     The form field value to validate.
     * @param   string            $group     The field name group control value. This acts as as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     * @param   Registry|null     $input     An optional Registry object with the entire data set to validate against the entire form.
     * @param   Form|null         $form      The form object for which the field is being tested.
     *
     * @return  boolean  True if the value is valid, false otherwise.
     *
     * @throws  UnexpectedValueException if rule is invalid.
     * @throws Exception
     */
    public function test(
        \SimpleXMLElement $element,
        $value,
        $group = null,
        Registry $input = null,
        Form $form = null
    ): bool {
        // Check if there is input provided
        if (!($input instanceof Registry)) {
            return false;
        }

        // Load input into the form
        $form->setValue('fields', 'params', $input);

        // Get form fields
        $fields_value = $input->get('params.fields');
        $fields_value = is_object($fields_value) ? (array)$fields_value : [];

        // Look for duplicated names
        $result = $this->hasDuplicates($fields_value, $element);

        // If user selected Recipient field, make sure he added some Recipients e-mail addresses
        $result = $result && $this->recipientsProvided($fields_value, $element, $input);

        // If Wizard layout is set, check if all fields are in the group
        if (str_ends_with($input->get('params.layout', '_:default'), 'wizard')) {
            $result = $result && $this->checkGroups($fields_value, true);
        } else {
            $result = $result && $this->checkGroups($fields_value);
        }

        return $result;
    }

    /**
     * Check if the form has duplicate field names.
     *
     * @param   array             $fields_value
     * @param   SimpleXMLElement  $element
     *
     * @return bool
     * @throws Exception
     */
    protected function hasDuplicates(array $fields_value, SimpleXMLElement $element): bool
    {
        /**
         * @var CMSApplication $app
         */
        $app = Factory::getApplication();
        $duplicates = [];

        foreach ($fields_value as $field) {
            if (!array_key_exists($field->name, $duplicates)) {
                $duplicates[$field->name] = [$field->title];
            } else {
                $duplicates[$field->name][] = $field->title;
            }

            if ($field->type === 'group') {
                foreach ($field->subfields as $subfield) {
                    if (!array_key_exists($subfield->name, $duplicates)) {
                        $duplicates[$subfield->name] = [$subfield->title];
                    } else {
                        $duplicates[$subfield->name][] = $subfield->title;
                    }
                }
            }
        }

        // Leave only duplicates
        $duplicates = array_filter($duplicates, static function ($v, $k) {
            return count($v) > 1;
        }, ARRAY_FILTER_USE_BOTH);

        // Add message for each duplicate
        foreach ($duplicates as $field_name => $labels) {
            $app->enqueueMessage(
                Text::sprintf('MOD_BPFORM_BASIC_FIELD_NAME_DUPLICATE_S', implode(', ', $labels), $field_name),
                CMSApplicationInterface::MSG_ERROR
            );
        }

        // Add error into the field element
        if ($duplicates !== []) {
            $element->addAttribute('message', 'MOD_BPFORM_BASIC_FIELD_NAME_DUPLICATE_ERROR');
        }

        return empty($duplicates);
    }

    /**
     * Make sure administrator provided an e-mail field if a recipient is provided
     *
     * @param   array             $fields_value
     * @param   SimpleXMLElement  $element
     * @param   Registry|null     $input
     *
     * @return bool
     */
    protected function recipientsProvided(array $fields_value, SimpleXMLElement $element, ?Registry $input = null): bool
    {
        // Make sure there is any input
        if (is_null($input)) {
            $input = new Registry();
        }

        foreach ($fields_value as $field) {
            if ($field->type === 'recipient') {
                $emails = $input->get('params.recipient_emails', []);
                $emails = ArrayHelper::fromObject($emails);
                $emails = array_column($emails, 'email');
                $emails = array_filter($emails, static function ($v) {
                    return Mail::validateAddress($v, 'php');
                });

                // Check if there are at least 2 valid emails to use with Recipient field
                if (count($emails) < 2) {
                    $element->addAttribute('message', 'MOD_BPFORM_RECIPIENT_EMAIL_INVALID');

                    return false;
                }

                break;
            }
        }

        return true;
    }

    /**
     * If the form is using groups, invalidate it if there is a single field outside the groups.
     *
     * @param   array  $fields_value
     * @param   bool   $requireGroups
     *
     * @return bool
     * @throws Exception
     */
    protected function checkGroups(array $fields_value, bool $requireGroups = false): bool
    {
        /**
         * @var CMSApplication $app
         */
        $types = array_column($fields_value, 'type');
        $unique_types = array_unique($types);
        $app   = Factory::getApplication();

        // If groups are required or there are groups added and there are fields outside the groups
        if ($unique_types !== ['group'] && ($requireGroups || in_array('group', $unique_types, true))) {
            $fields_outside = [];

            // Find fields outside the group
            foreach ($fields_value as $field) {
                if ($field->type !== 'group') {
                    $fields_outside[] = $field->title;
                }
            }

            // Return a proper warning
            if (!$requireGroups) {
                $message = Text::sprintf('MOD_BPFORM_ERROR_OUTSIDE_OF_GROUP_S', implode(', ', $fields_outside));
            } else {
                $message = Text::sprintf('MOD_BPFORM_ERROR_WIZARD_REQUIRES_GROUPS_S', implode(', ', $fields_outside));
            }

            $app->enqueueMessage($message, CMSApplicationInterface::MSG_ERROR);

            return false;
        }

        return true;
    }
}