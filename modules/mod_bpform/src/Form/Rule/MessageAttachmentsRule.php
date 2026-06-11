<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Form\Rule;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use SimpleXMLElement;
use UnexpectedValueException;

final class MessageAttachmentsRule extends FormRule
{

    /**
     * Method to test recipients field.
     *
     * @param   SimpleXMLElement  $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
     * @param   mixed             $value     The form field value to validate.
     * @param   string            $group     The field name group control value. This acts as as an array container for the field.
     *                                       For example if the field has name="foo" and the group value is set to "bar" then the
     *                                       full field name would end up being "bar[foo]".
     * @param   Registry          $input     An optional Registry object with the entire data set to validate against the entire form.
     * @param   Form              $form      The form object for which the field is being tested.
     *
     * @return  boolean  True if the value is valid, false otherwise.
     *
     * @throws  UnexpectedValueException if rule is invalid.
     */
    public function test(
        \SimpleXMLElement $element,
        $value,
        $group = null,
        Registry $input = null,
        Form $form = null
    ): bool
    {

        $attachments = (array)$input->get('params.message_attachments', []);

        // c
        $attachmentsSize = 0;
        foreach ($attachments as $attachment) {
            $path = JPATH_ROOT . '/' . $attachment->file;

            if (file_exists($path)) {
                $attachmentsSize += filesize($path);
            }
        }

        // Check the message size
        $sum_formatted = round($attachmentsSize / 1024 / 1024, 1);
        if ($sum_formatted > 15) {
            $element->addAttribute('message',
                Text::sprintf('MOD_BPFORM_ERROR_MESSAGE_ATTACHMENTS_SIZE', $sum_formatted));

            return false;
        }

        return true;
    }
}