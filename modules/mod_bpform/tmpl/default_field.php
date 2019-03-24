<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use Joomla\CMS\Form\FormHelper;

defined('_JEXEC') or die;

/**
 * @var $field stdClass
 */

// Default field value
$value = key_exists($field->name, $values) ? $values[$field->name] : '';

// Prepare text field
$instance = FormHelper::loadFieldType($field->type);
if( $field->type ==='text' ){
    $element = new SimpleXMLElement('<field type="text" />');
    $element->addAttribute('hint', $field->hint);

// Prepare email field
} elseif( $field->type ==='email' ){
    $element = new SimpleXMLElement('<field type="email" />');
    $element->addAttribute('hint', $field->hint);

// Prepare textarea field
}elseif( $field->type ==='textarea' ){
    $element = new SimpleXMLElement('<field type="textarea" />');
    $element->addAttribute('hint', $field->hint);

// Prepare checkbox field
} elseif( $field->type ==='checkbox' ) {
    $element = new SimpleXMLElement('<field type="checkbox" />');
    if ($field->checked) {
        $element->addAttribute('checked', 'true');
    }
}

// Finalise preparation
if( isset($instance) and isset($element) ) {
    if( !$show_labels and !in_array($field->type, ['checkbox']) ) {
        $element->addAttribute('labelclass', 'sr-only');
    }
    $element->addAttribute('name', $field->name);
    $element->addAttribute('label', $field->title);
    if( $field->required ) {
        $element->addAttribute('required', 'true');
    }
    $instance->setup($element, $value);
    echo $instance->renderField();
}

// Clear memory
unset($element, $instance);

