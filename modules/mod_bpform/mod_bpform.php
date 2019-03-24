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
use Joomla\Registry\Registry;

defined('_JEXEC') or die;


// Include the syndicate functions only once
require_once __DIR__ . '/helper.php';

/**
 * @var Registry $params
 */

$moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
$layout = $params->get('layout', 'default');
$show_labels = (bool)$params->get('show_labels', 1);
$fields = (array)$params->get('fields', []);
$input = Factory::getApplication()->input->post;

// Form values set in previous rendering
$values = $input->getArray();

// Process form input
if( ModBPFormHelper::processForm($input, $params) ) {
    $values = [];
}

require ModuleHelper::getLayoutPath('mod_bpform', $layout);
