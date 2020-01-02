<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

defined('_JEXEC') or die;

/**
 * @var $field stdClass
 */

if ($field->type === 'heading') {
    $level = strtolower($field->heading_level);
    echo "<$level>{$field->title}</$level>";
} elseif ($field->type === 'html') {
    echo "<div class=\"html-field-content\">{$field->html}</div>";
}

$field->instance->setup($field->element, $field->value);
echo $field->instance->renderField();