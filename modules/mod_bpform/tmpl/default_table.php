<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

defined('_JEXEC') or die;

?>
<style>
    .container {
        width: 100%;
        text-align:center;
    }
    .data-table {
        border:1px solid gray; border-collapse: collapse;
        font-size: 15px;
        font-family: Arial, Helvetica, sans-serif;
        width:100%;
    }
    .data-table th,td {
        border:1px solid gray;border-collapse: collapse;
        padding:15px 20px;text-align:left;
    }
    .data-table th {
        color: black;
        font-weight: 400;
        max-width: 33%;
    }
    .data-table td {
        color:#555;font-weight:400
    }
</style>
<div class="container">
    <table class="data-table">
        <tbody>
        <?php foreach ($data as $name => $data_record):
            $value = $data_record->value;

            // Not a file
            if ($data_record->type !== 'file') {
                $value = is_array($value) ? '<ul><li>' . implode('</li><li>', $value) . '</li></ul>' : $value;

                // File or files
            } elseif ($data_record->type === 'file' and !empty($value)) {
                $value = '<ul>';
                foreach ($data_record->value as $file) {
                    $value = '<li>' . $file['name'] . '</li>';
                }
                $value .= '</ul>';
            }

            $title = $data_record->title;
            ?>
            <tr>
                <?php if (empty($value)): ?>
                    <th style="font-weight: bold;width:33%;" colspan="2">
                        <?php echo $title ?>
                    </th>
                <?php else: ?>
                    <th style="font-weight: bold;width:33%;">
                        <?php echo $title ?>
                    </th>
                    <td>
                        <?php echo $value ?>
                    </td>
                <?php endif ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
