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

// Prepare tel field
} elseif( $field->type ==='tel' ){
    $element = new SimpleXMLElement('<field type="tel" />');
    $element->addAttribute('hint', $field->hint);

// Prepare select/list field
} elseif( $field->type ==='list' ){
    $xml = '<field type="list">';
    $value = ModBPFormHelper::getOptionsFieldValue($field, $value);
    $xml.= ModBPFormHelper::prepareFieldXMLOptions($field, $value);
    $value = implode(',', $value);
    $xml.= '</field>';
    $element = new SimpleXMLElement($xml);

// Prepare radio field
} elseif( $field->type ==='radio' ){
    $xml = '<field type="radio">';
    $value = ModBPFormHelper::getOptionsFieldValue($field, $value);
    $xml.= ModBPFormHelper::prepareFieldXMLOptions($field, $value);
    $value = implode(',', $value);
    $xml.= '</field>';
    $element = new SimpleXMLElement($xml);

// Prepare checkboxes field
} elseif( $field->type ==='checkboxes' ){
    $xml = '<field type="checkboxes">';
    $xml.= ModBPFormHelper::prepareFieldXMLOptions($field, ModBPFormHelper::getOptionsFieldValue($field, $value));
    $xml.= '</field>';
    $element = new SimpleXMLElement($xml);

// Prepare textarea field
}elseif( $field->type ==='textarea' ){
    $element = new SimpleXMLElement('<field type="textarea" />');
    $element->addAttribute('hint', $field->hint);

// Prepare textarea field
}elseif( $field->type ==='heading' ){

    $level = strtolower($field->heading_level);
    echo "<$level>{$field->title}</$level>";

// Prepare HTML field
}elseif( $field->type ==='html' ){

    echo "<div class=\"html-field-content\">{$field->html}</div>";

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
    $element->addAttribute('name', $formPrefix.'['.$field->name.']');
    $element->addAttribute('id', $formPrefix.'_'.$field->name);
    $element->addAttribute('label', $field->title);
    if( $field->required ) {
        $element->addAttribute('required', 'true');
    }

    $instance->setup($element, $value);

    echo $instance->renderField();
}

// Clear memory
unset($element, $instance);

