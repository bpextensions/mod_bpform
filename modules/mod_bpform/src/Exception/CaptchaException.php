<?php

use Joomla\CMS\Language\Text;

/**
 * @package     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */
class CaptchaException extends RuntimeException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct(Text::_('MOD_BPFORM_FIELD_CAPTCHA_ERROR'), $code, $previous);
    }
}