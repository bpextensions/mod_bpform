<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Dispatcher;

use BPExtensions\Module\BPForm\Site\Exception\CaptchaException;
use BPExtensions\Module\BPForm\Site\Helper\BPFormHelper;
use BPExtensions\Module\BPForm\Site\Validator\FilesValidator;
use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

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

    protected $data = [];

    /**
     * Returns the layout data.
     *
     * @return  array|bool
     *
     * @throws Exception
     */
    protected function getLayoutData()
    {
        if (!empty($this->data)) {
            return $this->data;
        }

        /**
         * @var BPFormHelper $helper
         */

        $data               = parent::getLayoutData();
        $data['formPrefix'] = 'modbpform' . $this->module->id;

        $helperConfig = [
            'params'     => $data['params'],
            'module'     => $this->module,
            'formPrefix' => $data['formPrefix']
        ];

        // Create helper instance
        $helper = $this->getHelperFactory()->getHelper('BPFormHelper', $helperConfig);
        if (!($helper instanceof BPFormHelper)) {
            return false;
        }

        $app = $this->getApplication();

        $data['moduleclass_sfx'] = htmlspecialchars($data['params']->get('moduleclass_sfx', ''));
        $data['show_labels']     = (bool)$data['params']->get('show_labels', 1);
        $data['input']           = $app->input->post;
        $data['helper']          = $helper;
        $data['inputFiles']      = $app->input->files;
        $files = FilesValidator::filterFiles($data['inputFiles']->get($data['formPrefix'], []));
        $data['values']          = $data['input']->get($data['formPrefix'], [], 'array');
        $data['values']          = array_merge($data['values'], $files);
        $data['captchaEnabled'] = $helper->getSpamValidator()->isCaptchaEnabled($data['params']) !== false;
        $data['fields']          = $helper->getFields($data['values']);
        $data['layout']          = $data['params']->get('layout', '');

        $this->data = $data;

        return $this->data;
    }

    /**
     * @throws Exception
     */
    public function dispatch(): void
    {

        // Prepare and get layout data
        $data = $this->getLayoutData();
        $this->loadLanguage();
        $inputMethod = Factory::getApplication()->input->getMethod();

        try {
            if ($inputMethod === 'POST' && $data['helper']->submit($data['values']) === true) {
                $data['app']->redirect(Uri::current(), 302);
                $data['app']->close();
            }
        } catch (Exception $e) {
            if ($data['app']->get('debug', false)) {
                $data['app']->enqueueMessage($e->getMessage(), CMSApplicationInterface::MSG_ERROR);
            } elseif ($e instanceof CaptchaException) {
                $data['app']->enqueueMessage($e->getMessage(), CMSApplicationInterface::MSG_WARNING);
            } else {
                $data['app']->enqueueMessage(Text::_('MOD_BPFORM_EXCEPTION_DEFAULT'),
                    CMSApplicationInterface::MSG_WARNING);
            }
        }

        // Process form input
        parent::dispatch();
    }

}
