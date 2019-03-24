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
        max-width:600px;
        text-align:center;
    }
    .data-table {
        border:1px solid gray; border-collapse: collapse;
        font-size:15px;font-family:Arial,Helvetica,Sans;
        width:100%;
    }
    .data-table th,td {
        border:1px solid gray;border-collapse: collapse;
        padding:15px 20px;text-align:left;
    }
    .data-table th {
        color:black;font-weight:400;
    }
    .data-table td {
        color:#555;font-weight:400
    }
</style>
<div class="container">
    <table class="data-table">
        <tbody>
        <?php foreach ($data as $title=>$value): ?>
            <tr>
                <th>
                    <?php echo $title ?>
                </th>
                <td>
                    <?php echo $value ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
