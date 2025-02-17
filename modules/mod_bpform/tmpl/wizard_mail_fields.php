<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var FieldPrototype $field
 * @var Registry       $params
 */

$value = $field->value;

// Not a file
if ($field->type !== 'file') {
    $value = is_array($value) ? '<ul style="margin-bottom:0"><li>' . implode(
            '</li><li>',
            $value
        ) . '</li></ul>' : $value;

// File or files
} elseif (!empty($value)) {
    $value = '<ul>';
    foreach ($field->value as $file) {
        $value .= '<li>' . $file['name'] . '</li>';
    }
    $value .= '</ul>';
} else {
    $value = '';
}

$title = $field->title;
?>
<?php
if ($field->type === 'heading'): ?>
    <th colspan="2"><?php
        echo $title ?></th>
<?php
elseif ($field->type === 'html') : ?>
    <td colspan="2">
        <?php
        echo $value ?>
    </td>
<?php
else: ?>
    <th class="col-12"><?php
        echo $title ?></th>
    <td>
        <?php
        echo $value ?>
    </td>
<?php
endif ?>
