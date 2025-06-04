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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var string           $formPrefix
 * @var string           $moduleclass_sfx
 * @var BPFormHelper     $helper
 * @var string           $layout
 * @var Registry         $params
 * @var bool             $captchaEnabled
 * @var FieldPrototype[] $fields
 * @var WebAssetManager  $wa
 */

$form = new Form($formPrefix);
$wa   = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useScript('bootstrap.collapse');
$groups_count = count($fields);
$current_uri = Uri::getInstance()->toString();
?>
<div class="modbpform<?php
echo $moduleclass_sfx ?>">

    <form name="<?php
    echo $formPrefix ?>"
          class="form-vertical row"
          method="post"
          data-form-step="1"
          action="<?php
          echo $current_uri ?>"
          id="<?php
          echo $formPrefix ?>-form"
        <?php
        if ($helper->hasFilesUpload()): ?> enctype="multipart/form-data"<?php
        endif ?>
    >
        <div class="col-12 text-center">
            <p aria-hidden="true" class="mb-0" data-form-action="progress-counter">1 / <?php
                echo $groups_count ?></p>
            <progress class="w-100" max="<?php
            echo $groups_count ?>" value="1">
                1 / <?php
                echo $groups_count ?>
            </progress>
        </div>

        <?php
        $first = true;
        $step  = 1;
        foreach ($fields as $entry):
            $group = &$entry;
            ?>
            <?php
            require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_group') ?>
            <?php
            $first = false;
            $step++;
        endforeach ?>

        <?php
        if ($captchaEnabled): ?>
            <div class="captcha col-12">
                <?php
                echo $helper->getSpamValidator()->getCaptcha() ?>
            </div>
        <?php
        endif ?>
        <div class="form-actions col-12 d-flex justify-content-end mt-3">

            <button class="btn btn-outline-primary ms-3" data-form-action="prev" disabled style="display:none">
                <i class="icon-arrow-left icon-fw" aria-hidden="true"></i>
                <span class="ms-1">
                    <?php
                    echo Text::_('MOD_BPFORM_STEP_PREVIOUS') ?>
                </span>
            </button>
            <button class="btn btn-outline-primary ms-3" data-form-action="next">
                <span class="me-1">
                    <?php
                    echo Text::_('MOD_BPFORM_STEP_NEXT') ?>
                </span>
                <i class="icon-arrow-right icon-fw" aria-hidden="true"></i>
            </button>

            <?php
            if ($params->get('show_reset', false)): ?>
                <button class="btn btn-outline-primary ms-3" type="reset">
                    <i class="icon-times icon-fw" aria-hidden="true"></i>
                    <span class="visually-hidden">
                        <?php
                        echo Text::_('MOD_BPFORM_BUTTON_RESET_LABEL') ?>
                    </span>
                </button>
            <?php
            endif ?>
            <button class="btn btn-primary ms-3" type="submit" disabled style="display:none" data-form-action="submit">
                <span class="me-2">
                    <?php
                    echo Text::_('MOD_BPFORM_BUTTON_SEND_LABEL') ?>
                </span>
                <i class="icon-envelope icon-fw" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>

<script>
    <?php ob_start(); ?>
    jQuery(function () {
        const $form = $('#<?php echo $formPrefix ?>-form');
        const $next = $form.find('[data-form-action="next"]');
        const $prev = $form.find('[data-form-action="prev"]');
        const $submit = $form.find('[data-form-action="submit"]');
        const $steps = $form.find('[data-form-step]');
        const $progress = $form.find('progress');
        const $progress_counter = $form.find('[data-form-action="progress-counter"]');
        let step = parseInt($form.attr('data-form-step'));
        let steps_count = $steps.length;

        // Basic events
        $next.click((e) => {
            e.stopPropagation();
            e.preventDefault();
            $form.trigger('modbpform.next')
        });
        $prev.click((e) => {
            e.stopPropagation();
            e.preventDefault();
            $form.trigger('modbpform.prev')
        });
        $submit.click((e) => {
            $form.trigger('modbpform.submit')
        });

        // Update buttons
        function showButton($btn) {
            $btn.show().removeAttr('disabled');
        }

        function hideButton($btn) {
            $btn.hide().attr('disabled', '');
        }

        function validateInputs($container) {
            let result = true;

            $container.find('input,select,textarea').each(function () {
                if (result && !this.reportValidity()) {
                    result = false;
                }
            });

            return result;
        }

        // Step change event
        $form.on('modbpform.stepChange', function () {
            const currentProgress = step + ' / ' + steps_count;
            $progress.val(step).text(currentProgress);
            $progress_counter.text(currentProgress);
        });

        // Next step event
        $form.on('modbpform.next', function (e) {


            if (!validateInputs($form.find('[data-form-step="' + step + '"]'))) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }

            if (step + 1 <= steps_count) {
                step++;

                $form.attr('data-form-step', step);
                $steps.hide();
                $form.find('[data-form-step="' + step + '"]').show();

                if (step > 1) {
                    showButton($prev);
                }

                if (step === steps_count) {
                    hideButton($next);
                    showButton($submit);
                }

                $form.trigger('modbpform.stepChange');
            }
        });

        // Previous step event
        $form.on('modbpform.prev', function (e) {
            if (step - 1 > 0) {
                step--;
                $form.attr('data-form-step', step);
                $steps.hide();
                $form.find('[data-form-step="' + step + '"]').show();


                if (step < steps_count) {
                    showButton($next);
                    hideButton($submit);
                }

                if (step > 1) {
                    showButton($prev);
                }

                $form.trigger('modbpform.stepChange');
            }
        });

        // Form submit event
        $form.on('modbpform.submit', function (e) {
            if (!validateInputs($form)) {
                e.stopPropagation();
                e.preventDefault();

                return false;
            }
        });


    })
    <?php $code = ob_get_clean();
    $wa->addInlineScript($code, [], [], ['jquery']);
    ?>
</script>
