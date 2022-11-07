<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Dispatcher;

use BPExtensions\Module\BPForm\Site\Helper\BPFormHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Dispatcher class for a mod_bpform module.
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   4.2.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $data['formPrefix'] = 'modbpform' . $this->module->id;

//        $data['list'] = $this->getHelperFactory()->getHelper('ArticlesNewsHelper')->getArticles($data['params'], $this->getApplication());

        /**
         * @var BPFormHelper $helper
         */
        $helperConfig = [
            'params'     => $data['params'],
            'module'     => $this->module,
            'formPrefix' => $data['formPrefix']
        ];
        $helper       = $this->getHelperFactory()->getHelper('BPFormHelper', $helperConfig);
        $app          = $this->getApplication();

        $data['moduleclass_sfx'] = htmlspecialchars($data['params']->get('moduleclass_sfx'));
        $data['show_labels']     = (bool)$data['params']->get('show_labels', 1);
        $data['input']           = $app->input->post;
        $data['inputFiles']      = $app->input->files;
        $data['values']          = $data['input']->get($data['formPrefix'], [], 'array');
        $data['values']          = array_merge($data['values'],
            $data['inputFiles']->get($data['formPrefix'], [], 'array'));
        $data['captchaEnabled']  = $helper->isCaptchaEnabled() !== false;
        $data['fields']          = $helper->getFields($data['values']);

        return $data;
    }
}
