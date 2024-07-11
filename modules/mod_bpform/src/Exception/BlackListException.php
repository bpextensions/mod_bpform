<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Exception;

use BPExtensions\Module\BPForm\Site\Validator\SpamValidator;
use Joomla\CMS\Language\Text;

class BlackListException extends \RuntimeException
{
    public function __construct($ip = null, \Throwable $previous = null)
    {
        if (is_null($ip)) {
            parent::__construct(Text::sprintf('MOD_BPFORM_FIELD_IP_BLACKLIST_ERROR_S', SpamValidator::getClientIp()), 0,
                $previous);
        } else {
            parent::__construct(Text::_('MOD_BPFORM_FIELD_CAPTCHA_ERROR', SpamValidator::getClientIp()), 0, $previous);
        }

    }
}