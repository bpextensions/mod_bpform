<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

final class JFormRuleFields extends FormRule
{


    /**
     * Method to test the fields subform.
     *
     * @param SimpleXMLElement $element The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param mixed $value The form field value to validate.
     * @param string $group The field name group control value. This acts as as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     * @param Registry $input An optional Registry object with the entire data set to validate against the entire form.
     * @param Form $form The form object for which the field is being tested.
     *
     * @return  boolean  True if the value is valid, false otherwise.
     *
     * @throws  UnexpectedValueException if rule is invalid.
     * @throws Exception
     * @since   1.0
     */
    public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
    {
        // Get form fields
        $fields_value = $input->get('params.fields');
        $fields_value = is_object($fields_value) ? (array)$fields_value : [];

        // Look for duplicated names
        $duplicates = [];
        foreach ($fields_value as $field) {
            if (!key_exists($field->name, $duplicates)) {
                $duplicates[$field->name] = [$field->title];
            } else {
                $duplicates[$field->name][] = $field->title;
            }
        }

        // Leave only duplicates
        $duplicates = array_filter($duplicates, function ($v, $k) {
            return count($v) > 1;
        }, ARRAY_FILTER_USE_BOTH);

        // Add message for each duplicate
        foreach ($duplicates as $field_name => $labels) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('MOD_BPFORM_BASIC_FIELD_NAME_DUPLICATE_S', implode(', ', $labels), $field_name),
                'warning'
            );
        }

        // TODO: If user selected Recipient field, make sure he added some Recipients e-mail addresses

        // If there are duplicates, return error.
        if (!empty($duplicates)) {
            $element->addAttribute('message', 'MOD_BPFORM_BASIC_FIELD_NAME_DUPLICATE_ERROR');
            return false;
        }

    }
}