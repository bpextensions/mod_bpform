<?php

/**
 * @package     BPExtensions.Module
 * @subpackage  BPForm
 *
 * @copyright   Copyright (C) 2024 Grupa Best Sp. z o.o., All rights reserved.
 * @license     GNU GPL 3.0; see https://www.gnu.org/licenses/gpl-3.0.txt
 */

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;

defined('_JEXEC') or die;

/**
 * @var FieldPrototype $field
 */

$value = $field->value;

// Not a file
if ($field->type !== 'file') {
    $value = is_array($value) ? '<ul><li>' . implode('</li><li>', $value) . '</li></ul>' : $value;

// File or files
} elseif (!empty($value)) {
    $value = '<ul>';
    foreach ($field->value as $file) {
        $value .= '<li>' . $file['name'] . '</li>';
    }
    $value .= '</ul>';
}

$title = $field->title;
?>
<?php if ($field->type === 'heading'): ?>
    <th colspan="2"><?php echo $title ?></th>
<?php elseif ($field->type === 'html') : ?>
    <td colspan="2">
        <?php echo $value ?>
    </td>
<?php else: ?>
    <th class="col-12"><?php echo $title ?></th>
    <td>
        <?php echo $value ?>
    </td>
<?php endif ?>
