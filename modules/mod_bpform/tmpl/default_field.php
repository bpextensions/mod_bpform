<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use BPExtensions\Module\BPForm\Site\Helper\FormFieldPrototype;
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

/**
 * @var $field FormFieldPrototype
 */

if ($field->type === 'heading') {
    $level = strtolower($field->heading_level);
    echo "<$level>{$field->title}</$level>";

    return;
}

if ($field->type === 'html') {
    echo "<div class=\"html-field-content\">{$field->html}</div>";

    return;
}


$field->instance->setup($field->element, $field->value);
$field->instance->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

echo $field->instance->renderField();