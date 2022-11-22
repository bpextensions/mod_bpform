<?php

namespace BPExtensions\Module\BPForm\Site\Helper;

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use Joomla\CMS\Form\FormField;
use SimpleXMLElement;
use stdClass;

/**
 * @property string $name
 * @property string $title
 * @property string $value
 * @property string $type
 * @property string $hint
 * @property string $calendarformat
 * @property string $calendarhours
 * @property string $mimeaccept
 * @property string $label_html
 * @property int    $label_html_enabled
 * @property string $html
 * @property string $heading_level
 * @property bool   $multiplefiles
 * @property int    $maxtotalfilesize
 * @property bool             $checked
 * @property bool             $required
 * @property FormField        $instance
 * @property SimpleXMLElement $element
 * @property stdClass         $options JSON converted object that can be converted to an array.
 */
abstract class FormFieldPrototype
{

}