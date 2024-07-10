<?php

/**
 * @package     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

use Joomla\CMS\Language\Text;

class NoRecipientsException extends RuntimeException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct(Text::_('MOD_BPFORM_EXCEPTION_NO_RECIPIENTS'), $code, $previous);
    }
}