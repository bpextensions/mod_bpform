<?php

/*
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Event;

use Joomla\CMS\Event\AbstractImmutableEvent;

class StoreMessageEvent extends AbstractImmutableEvent
{

    public function __construct(string $name, array $arguments = [])
    {
        if (!isset($arguments['params'])) {
            throw new \BadMethodCallException("Argument 'params' of event $this->name is required but has not been provided");
        }

        parent::__construct($name, $arguments);
    }

}