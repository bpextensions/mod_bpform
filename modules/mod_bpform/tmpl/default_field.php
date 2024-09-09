<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var FieldPrototype $field       Field type.
 * @var bool           $show_labels Show field labels?
 * @var Registry $params
 */

if ($field->type === 'heading') {
    $level = strtolower($field->heading_level);
    echo "<div class=\"col-12\">";
    echo "<$level class=\"\">{$field->title}</$level>";
    echo "</div>";

    return;
}

if ($field->type === 'html') {
    echo "<div class=\"col-12 html-field-content\">{$field->html}</div>";

    return;
}

$field->instance->setup($field->element, $field->value);
$field->instance->setDatabase(Factory::getContainer()->get('DatabaseDriver'));

// Rendering options
$renderOptions = [];
if (!$show_labels && !in_array($field->type, ['checkbox', 'checkboxes'])) {
    $renderOptions['hiddenLabel'] = true;
}

$renderOptions['class'] = 'col-12 col-lg-' . $field->columns;

echo $field->instance->renderField($renderOptions);