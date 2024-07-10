<?php

/*
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights}, All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Validator;

use Exception;
use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\Event;
use Joomla\Registry\Registry;
use RuntimeException;

/**
 * Validate input data against SPAM rules.
 */
class SpamValidator
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

    /**
     * @var CMSApplication
     */
    protected $app;

    /**
     * @var Registry
     */
    protected $params;

    /**
     * @var null|string
     */
    protected static $captcha;

    /**
     * A captcha field input name.
     *
     * @var string
     */
    protected $captcha_field_name;

    protected $config;

    public function __construct(ApplicationInterface $app, array &$config)
    {

        $this->params = $config['params'];
        $this->app    = $app;

        // Prepare words blacklist
        $words = $this->params->get('words_blacklist', '');
        $this->words_blacklist = explode(',', $words);
        array_walk($this->words_blacklist, 'trim');
        $this->words_blacklist = array_filter($this->words_blacklist);

        // Prepare IP blacklist
        $addresses = $this->params->get('ip_blacklist', '');
        $addresses          = str_ireplace([',', ';'], "\n", $addresses);
        $this->ip_blacklist = explode("\n", $addresses);
        array_walk($this->ip_blacklist, 'trim');
        $this->ip_blacklist = array_filter($this->ip_blacklist);

        $this->config             = $config;
        $this->captcha_field_name = $config['formPrefix'] . '_captcha';
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
        $clientIp = self::getClientIp();

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
    public static function getClientIp(): string
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

    /**
     * Check if captcha is enabled.
     *
     * @param   Registry  $params  Module params.
     *
     * @return string|bool    Returns string if captcha is enabled or false if not.
     *
     * @throws Exception
     */
    public static function isCaptchaEnabled(Registry $params): bool
    {
        $plugin = self::getCaptchaName();

        // Check if captcha is enabled
        return !(empty($plugin) || is_numeric($plugin) || !$params->get('captcha', 0));
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getCaptchaName(): string
    {
        if (self::$captcha === null) {
            $app           = Factory::getApplication();
            self::$captcha = $app->get('captcha', '');
            if ($app->isClient('site')) {
                self::$captcha = $app->getParams()->get('captcha', self::$captcha);
            }
        }

        return self::$captcha;
    }

    /**
     * Validate captcha response.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function validateCaptcha(): bool
    {
        PluginHelper::importPlugin('captcha', self::isCaptchaEnabled($this->params));

        $event = new Event('onCheckAnswer');
        $event->addArgument('0', $this->app->input->get($this->captcha_field_name));
        $dispatcher = $this->app->getDispatcher();

        try {
            $dispatcher->dispatch($event->getName(), $event);
            $response = $event->getArgument('result');

        } catch (Exception $e) {
            $response = false;

            if ($this->app->get('debug')) {
                $this->app->enqueueMessage($e->getMessage(), 'error');
            }
        }

        return ($response === true) or ($response === [true]);
    }

    /**
     * Return captcha code
     *
     * @return string
     *
     * @throws Exception
     */
    public function getCaptcha(): string
    {

        // Get captcha plugin
        $plugin = self::isCaptchaEnabled($this->params);
        if ($plugin === false) {
            return '';
        }

        // Prepare namespace
        $module_id = $this->config['module']->id;
        $namespace = "mod_bpform.$module_id.captcha";

        // Try to create captcha field
        try {
            // Get an instance of the captcha class that we are using
            $captcha = Captcha::getInstance($plugin, ['namespace' => $namespace]);

            return $captcha->display($this->captcha_field_name, 'mod_bpform_captcha_' . $module_id);
        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');

            return '';
        }
    }
}