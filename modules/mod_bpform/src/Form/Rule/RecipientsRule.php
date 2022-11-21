<?php
/*
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Form\Rule;

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

final class RecipientsRule extends FormRule
{


    /**
     * Method to test recipients field.
     *
     * @param   SimpleXMLElement  $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value     The form field value to validate.
     * @param   string            $group     The field name group control value. This acts as as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     * @param Registry            $input     An optional Registry object with the entire data set to validate against the entire form.
     * @param Form                $form      The form object for which the field is being tested.
     *
     * @return  boolean  True if the value is valid, false otherwise.
     *
     * @throws  UnexpectedValueException if rule is invalid.
     * @since   1.0
     */
    public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)
    {

        // We select emails as recipient but we didnt provided the recipients.
        if ($input->get('params.recipient', 'contact') === 'emails' && empty($value)) {
            $element->addAttribute('message', 'MOD_BPFORM_BASIC_FIELD_RECIPIENT_EMAILS');

            return false;
        }

    }
}