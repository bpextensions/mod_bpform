<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use BPExtensions\Module\BPForm\Site\Helper\BPFormHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var string           $formPrefix
 * @var string           $moduleclass_sfx
 * @var BPFormHelper     $helper
 * @var string           $layout
 * @var Registry $params
 * @var bool             $captchaEnabled
 * @var FieldPrototype[] $fields
 */

$form = new Form($formPrefix);
$current_uri = Uri::getInstance()->toString();
?>
<div class="modbpform<?php echo $moduleclass_sfx ?>">

    <form name="<?php echo $formPrefix ?>" class="form-vertical row" method="post"
          action="<?php
          echo $current_uri ?>"<?php
    if ($helper->hasFilesUpload()): ?> enctype="multipart/form-data"<?php
    endif ?>>
        <?php foreach ($fields as $entry): ?>
            <?php if ($entry->type === 'group'):
                $group = &$entry;
                ?>
                <?php require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_group') ?>
            <?php else:
                $field = &$entry;
                ?>
                <?php require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_field') ?>
            <?php endif ?>
        <?php endforeach ?>
        <?php if ($captchaEnabled): ?>
            <div class="captcha col-12">
                <?php echo $helper->getSpamValidator()->getCaptcha() ?>
            </div>
        <?php endif ?>
        <div class="form-actions col-12 d-flex justify-content-end mt-3">
            <?php if ($params->get('show_reset', false)): ?>
                <button class="btn btn-outline-primary d-flex align-items-center me-3" type="reset">
                    <i class="icon-times icon-fw" aria-hidden="true"></i>
                    <span class="visually-hidden">
                    <?php echo Text::_('MOD_BPFORM_BUTTON_RESET_LABEL') ?>
                </span>
            </button>
            <?php endif ?>
            <button class="btn btn-primary px-5" type="submit">
                <?php echo Text::_('MOD_BPFORM_BUTTON_SEND_LABEL') ?>
            </button>
        </div>
    </form>
</div>
