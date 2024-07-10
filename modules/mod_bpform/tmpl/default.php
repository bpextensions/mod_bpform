<?php

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use BPExtensions\Module\BPForm\Site\Helper\BPFormHelper;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/**
 * @package     ${package}
 * @subpage     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 *
 * @var string           $formPrefix
 * @var string           $moduleclass_sfx
 * @var BPFormHelper     $helper
 * @var string           $layout
 * @var bool             $captchaEnabled
 * @var FieldPrototype[] $fields
 */

$form = new Form($formPrefix);

?>
<div class="modbpform<?php echo $moduleclass_sfx ?>">

    <form name="<?php echo $formPrefix ?>" class="form-vertical row" method="post"
          action="<?php echo JUri::current() ?>"<?php if ($helper->hasFilesUpload()): ?> enctype="multipart/form-data"<?php endif ?>>
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
            <div class="captcha">
                <?php echo $helper->getSpamValidator()->getCaptcha() ?>
            </div>
        <?php endif ?>
        <div class="form-actions d-flex justify-content-between mt-3">
            <button class="btn btn-outline-primary" type="reset">
                <?php echo Text::_('MOD_BPFORM_BUTTON_RESET_LABEL') ?>
            </button>
            <button class="btn btn-primary px-5" type="submit">
                <?php echo Text::_('MOD_BPFORM_BUTTON_SEND_LABEL') ?>
            </button>
        </div>
    </form>
</div>
