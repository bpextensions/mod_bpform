<?php

/**
 * @package     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

namespace BPExtensions\Module\BPForm\Site\Storage;

use Exception;
use Joomla\CMS\Factory;

class MailStorage
{

    /**
     * Send email form.
     *
     * @param   string  $body         E-mail body.
     * @param   string  $subject      E-mail subject.
     * @param   array   $recipients   Array of E-mail addresses.
     * @param   string  $reply_to     Reply-to e-mail address.
     * @param   string  $sender       Set sender e-mail address.
     * @param   array   $attachments  A list of message attachments using PHP file array format.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function store(
        string $body,
        string $subject,
        array $recipients,
        string $reply_to = '',
        string $sender = '',
        array $attachments = []
    ): bool {

        // E-mail class instance
        $mail = Factory::getMailer();

        // Add recipients
        foreach ($recipients as $recipient) {
            $mail->addRecipient($recipient);
        }

        // Add sender if exists
        if (!empty($sender)) {
            $mail->setSender($sender);
        }

        // Add reply to if exists
        if (!empty($reply_to)) {
            $mail->addReplyTo($reply_to);
        }

        // If there are attachments to add
        foreach ($attachments as $attachment) {
            if (is_array($attachment)) {
                $path     = $attachment['tmp_name'];
                $filename = $attachment['name'];
            } else {
                $path     = $attachment;
                $filename = pathinfo($attachment, PATHINFO_BASENAME);
            }

            $mail->addAttachment($path, $filename);
        }

        // Set body
        $mail->setBody($body);
        $mail->isHtml();

        // Set subject
        $mail->setSubject($subject);

        // Send the email
        $result = false;
        try {
            $result = $mail->Send();
        } catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage($e->getMessage(), 'danger');
        }

        $result = is_bool($result) ? $result : false;

        return $result;
    }

}