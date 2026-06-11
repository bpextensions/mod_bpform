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
 * @var FieldPrototype $group       Group of fields.
 * @var string         $layout
 * @var bool           $show_labels Show field labels?
 * @var bool           $first       Is this a first step
 * @var Registry       $params
 * @var string         $formPrefix
 * @var int            $step
 */
?>
<div class="col-12" style="<?php
echo($first ? 'display:block' : 'display:none') ?>" id="<?php
echo $formPrefix ?>-step-<?php
echo $group->name ?>" data-form-step="<?php
echo $step ?>">
    <div class="card my-3">
        <h5 class="card-header">
            <?php
            echo $group->title ?>
        </h5>
        <div class="card-body row g-3">
            <?php
            foreach ($group->subfields as $field): ?>
                <?php
                require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_field') ?>
            <?php
            endforeach ?>
        </div>
    </div>
</div>
