<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Event;

use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\Registry\Registry;

class StoreMessageEvent extends AbstractImmutableEvent
{
    public const NAME = 'bpform.store_message';

    public function __construct(string $name, array $arguments = [])
    {
        if (!array_key_exists('params', $arguments) || !($arguments['params'] instanceof Registry)) {
            throw new \BadMethodCallException(
                "Argument 'params' of event '$name' is required but has not been provided"
            );
        }

        parent::__construct($name, $arguments);
    }

}