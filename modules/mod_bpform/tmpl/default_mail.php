<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var string $layout
 * @var array  $data
 * @var Registry $params
 */


?>
<style type="text/css">
    .container {
        width: 100%;
        font-family: Arial, Helvetica, Roboto, SansSerif, serif;
    }

    .h5 {
        font-size: 120%;
        font-weight: 600;
        margin-bottom: .5em;
        width: max(30vw, 800px);
        margin-top: 2em;
    }

    table {
        border: 1px solid #aaaaaa;
        border-collapse: collapse;
        width: 1000px;
        max-width: 100%;
    }

    table th, table td {
        border: 1px solid #aaaaaa;
        border-collapse: collapse;
        text-align: left !important;
        padding: 0.5em 1em;
        color: black;
    }

    table th {
        background: #f0f0f0;
        font-weight: 600;
        width: 40%;
    }

    table td {
        height: 60%;
        background: white;
    }

    table th[colspan] {
        background: white !important;
        font-size: 1.1em;
        padding: 1em 2em;
    }

    table th[colspan],
    table td[colspan] {
        width: 100%;
    }

    table + table {
        margin-top: -1px;
    }
</style>

<div class="container">

    <div class="form-data">
        <?php
        /**
         * @var FieldPrototype $data_record
         */
        foreach ($data as $name => $data_record): ?>

            <?php if ($data_record->type === 'group'): ?>
                <h2 class="h5"><?php echo $data_record->title ?></h2>
                <table>
                    <tbody>
                    <?php foreach ($data_record->subfields as $field): ?>
                        <tr>
                            <?php require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_mail_fields') ?>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <div aria-hidden="true"></div>
            <?php else:
                unset($field);
                $field = $data_record;
                ?>
                <table>
                    <tbody>
                    <tr>
                        <?php require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_mail_fields') ?>
                    </tr>
                    </tbody>
                </table>
            <?php endif ?>

        <?php endforeach ?>
    </div>

</div>