<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */


namespace BPExtensions\Plugin\System\BPForm\Extension;

use BPExtensions\Module\BPForm\Site\Event\StoreMessageEvent;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * System plugin for BP Form module.
 */
final class BPForm extends CMSPlugin implements SubscriberInterface
{

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            StoreMessageEvent::NAME => 'onStoreMessage',
        ];
    }

    public function onStoreMessage(StoreMessageEvent $event): void
    {
        $arguments = $event->getArguments();
        $data      = $arguments['data'];
        $body      = $arguments['body'];
        $params    = $arguments['params'];

        /**
         * @var CMSApplication $app
         */
        $app = Factory::getApplication();
        /**
         * @var MVCFactoryInterface $mvc
         */
        $messages_mvc  = $app->bootComponent('messages')->getMVCFactory();
        $message_model = $messages_mvc->createModel('Message', 'Administrator', ['ignore_request' => true]);


        $recipient_user_id = $this->params->get('recipient');

        // No recipient so skip next the part
        if (empty($recipient_user_id)) {
            return;
        }

        $result = $message_model->save([
            'user_id_from' => $recipient_user_id,
            'user_id_to'   => $recipient_user_id,
            'subject'      => $params->get('admin_subject', Text::_('MOD_BPFORM_DEFAULT_SUBJECT_EMAIL_ADMIN')),
            'message'      => $body,
        ]);
    }

}
