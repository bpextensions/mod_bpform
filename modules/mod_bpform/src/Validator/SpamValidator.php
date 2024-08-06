<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Validator;

use Exception;
use Joomla\Application\ApplicationInterface;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\CaptchaField;
use Joomla\CMS\Form\Form;
use Joomla\Registry\Registry;
use RuntimeException;
use SimpleXMLElement;

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
    public const CAPTCHA_FIELD_NAME = 'captcha';

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

    protected function getCaptchaFieldInstance(string $value = ''): CaptchaField
    {
        $module_id = $this->config['module']->id;
        $namespace = "modbpform{$module_id}";

        /**
         * @var CaptchaField $field
         */
        $form = new Form($namespace, ['control' => $namespace]);

        $xml = new SimpleXMLElement('<form><field name="' . self::CAPTCHA_FIELD_NAME . '" type="captcha" /></form>');
        $form->load($xml);

        $form->setFieldAttribute(self::CAPTCHA_FIELD_NAME, 'namespace', $namespace);
        $form->setFieldAttribute(self::CAPTCHA_FIELD_NAME, 'validate', 'captcha');

        if (!empty($value)) {
            $form->setValue(self::CAPTCHA_FIELD_NAME, null, $value);
        }

        /**
         * @var CaptchaField $field
         */
        $field = $form->getField(self::CAPTCHA_FIELD_NAME);

        return $field;
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
     * @param   array  $input
     *
     * @return bool
     *
     * @throws Exception
     */
    public function validateCaptcha(array $input = []): bool
    {
        $plugin = self::getCaptchaName();

        // No captcha or captcha is disabled for this form
        if (!self::isCaptchaEnabled($this->params) || empty($plugin) || (is_numeric($plugin))) {
            return true;
        }

        try {

            $value = $input[self::CAPTCHA_FIELD_NAME] ?? '';
            $field = $this->getCaptchaFieldInstance($value);

            return true === $field->validate($value, null, new Registry($input));

        } catch (Exception $e) {
            $response = false;

            if ($this->app->get('debug')) {
                $this->app->enqueueMessage($e->getMessage(), CMSApplicationInterface::MSG_ERROR);
            }
        }

        return ($response === true) || ($response === [true]);
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

        // Skip if not enabled
        if (!self::isCaptchaEnabled($this->params)) {
            return '';
        }

        // Try to create captcha field
        try {

            return $this->getCaptchaFieldInstance()->renderField();

        } catch (RuntimeException $e) {
            $this->app->enqueueMessage($e->getMessage(), CMSApplicationInterface::MSG_ERROR);

            return '';
        }
    }
}