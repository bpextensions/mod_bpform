<?php

/**
 * @package     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

namespace BPExtensions\Module\BPForm\Site\Helper;

use Joomla\Registry\Registry;

/**
 * SPAM detection helper.
 */
class SpamHelper
{
    /**
     * List of words blacklist.
     *
     * @var string[]
     */
    protected $words_blacklist;

    /**
     * List of IP or IP ranges to be blocked.
     *
     * @var string[]
     */
    protected $ip_blacklist;

    /**
     * The result of anti-spam pass.
     *
     * @var bool
     */
    protected $spamPassResult = true;

    public function __construct(Registry $params)
    {

        // Prepare words blacklist
        $words                 = $params->get('words_blacklist');
        $this->words_blacklist = explode(',', $words);
        array_walk($this->words_blacklist, 'trim');
        $this->words_blacklist = array_filter($this->words_blacklist);

        // Prepare IP blacklist
        $addresses          = $params->get('ip_blacklist');
        $addresses          = str_ireplace([',', ';'], "\n", $addresses);
        $this->ip_blacklist = explode("\n", $addresses);
        array_walk($this->ip_blacklist, 'trim');
        $this->ip_blacklist = array_filter($this->ip_blacklist);
    }

    /**
     * Filter text for blacklisted words.
     *
     * @param   string  $text  Text to search in.
     *
     * @return bool
     */
    public function filterText(string $text): bool
    {
        $text = str_ireplace(['.', ',', ';', '?', '!', '"', '\'', '*', '(', ')', '[', ']', ':'], ' ', $text);

        // Nothing to filter
        if (empty($text)) {
            return true;
        }

        // Check every word
        foreach ($this->words_blacklist as $words) {
            if (stripos($text, $words) !== false) {
                $this->spamPassResult = false;

                return false;
            }
        }

        // Nothing found, return
        return true;
    }

    /**
     * Check client IP against IP blacklist.
     *
     * @return bool
     */
    public function clientInBlacklist(): bool
    {
        $clientIp = $this->getClientIp();

        // Check in entry in block list
        foreach ($this->ip_blacklist as $set) {

            // If IP is in range or black list entry or matches it
            if ($this->ipInRange($clientIp, $set)) {

                // Invalidate enquiry
                $this->spamPassResult = false;
                break;
            }
        }

        return $this->spamPassResult;
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    public function getClientIp(): string
    {
        return isset($_SERVER['HTTP_CLIENT_IP'])
            ? $_SERVER['HTTP_CLIENT_IP']
            : (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                ? $_SERVER['HTTP_X_FORWARDED_FOR']
                : $_SERVER['REMOTE_ADDR']);
    }

    /**
     * @param   string  $ip     IPv4 or IPv6 client address.
     * @param   string  $range  IP range to check (separated with a dash e.g. 127.0.0.1-127.0.0.10)
     *
     * @return bool
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '-') !== false) {
            [$start, $end] = explode('-', $range, 2);

            return (inet_pton($ip) <= inet_pton($end) && inet_pton($start) <= inet_pton($ip));
        }

        return $ip === $range;
    }

    /**
     * Get the result of spam tests.
     *
     * @return bool
     */
    public function successfullyPassedTests(): bool
    {
        return $this->spamPassResult;
    }
}