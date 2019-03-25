<?php

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
 */

$form = new Form($formPrefix);

?>
<div class="modbpform<?php echo $moduleclass_sfx ?>">

    <form name="<?php echo $formPrefix ?>" class="form-vertical" method="post" href="<?php echo JUri::current() ?>">
        <?php foreach( $fields as $field ): ?>
            <?php require ModuleHelper::getLayoutPath('mod_bpform', $layout.'_field') ?>
        <?php endforeach ?>
        <?php if( $captchaEnabled ): ?>
        <div class="captcha">
            <?php echo ModBPFormHelper::getCaptcha($params, $module) ?>
        </div>
        <?php endif ?>
        <div class="form-actions">
            <button class="btn btn-default" type="reset">
                <?php echo Text::_('MOD_BPFORM_BUTTON_RESET_LABEL') ?>
            </button>
            <button class="btn btn-primary" type="submit">
                <?php echo Text::_('MOD_BPFORM_BUTTON_SEND_LABEL') ?>
            </button>
        </div>
    </form>
</div>
