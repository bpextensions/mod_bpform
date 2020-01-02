<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * @var Registry $params
 * @var Object $module
 */

// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

/**
 * Create module helper instance.
 */
$app = Factory::getApplication();

$helper = new ModBPFormHelper($params, $module);
$formPrefix = 'modbpform' . $module->id;
$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$layout = $params->get('layout', 'default');
$show_labels = (bool)$params->get('show_labels', 1);
$input = $app->input->post;
$values = $input->get($formPrefix, [], 'array');
$captchaEnabled = $helper->isCaptchaEnabled() !== false;
$fields = $helper->getFields($values);

// Process form input
try {
    if ($helper->processForm($values)) {
        $fields = $helper->getFields([], true);
    }
} catch (Exception $e) {
    if ($app->get('debug', false)) {
        $app->enqueueMessage($e->getMessage(), 'error');
    } else {
        $app->enqueueMessage(Text::_('MOD_BPFORM_EXCEPTION_DEFAULT'));
    }
}

require ModuleHelper::getLayoutPath('mod_bpform', $layout);
