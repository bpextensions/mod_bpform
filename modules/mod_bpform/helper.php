<?php

/**
 * @package     ${package}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 * @author      ${author.name}
 */

use Joomla\CMS\Captcha\Captcha;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Helper for mod_bpform
 */
final class ModBPFormHelper
{

    /**
     * Form fields.
     *
     * @var array|null
     */
    protected $fields;

    /**
     * Module parameters.
     *
     * @var Registry
     */
    protected $params;

    /**
     * Module instance.
     *
     * @var
     */
    protected $module;

    /**
     * Form prefix used in name attribute of its fields.
     *
     * @var string
     */
    protected $formPrefix;

    public function __construct(Registry $params, stdClass $module)
    {
        $this->params = $params;
        $this->module = $module;
        $this->formPrefix = 'modbpform' . $module->id;
    }

    /**
     * Process form input.
     *
     * @param array $input Form input data array.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function processForm(array $input): bool
    {

        // Application instance
        $app = Factory::getApplication();

        // Submission result
        $result = true;

        // There is nothing to process, exit method
        if (empty($input)) {
            return true;
        }

        // Prepare data table
        $data = $this->prepareAndValidateData($input);

        // Check if every field that is required was filled
        if (in_array(false, $data, true)) {
            return false;
        }

        // Create inquiry html table
        $table = $this->createTable($data);

        // Load recipients list from parameters and input
        $recipients = $this->getRecipients($input);
        if (empty($recipients)) {
            throw new Exception(Text::_('MOD_BPFORM_EXCEPTION_NO_RECIPIENTS'));
        }

        // Admin message subject
        $subject = $this->params->get('admin_subject', Text::_('MOD_BPFORM_DEFAULT_SUBJECT_EMAIL_ADMIN'));

        // Look for client email and set a reply to field on the admin email
        $client_email = $this->getClientEmail($input);
        $reply_to = '';
        if (!empty($client_email)) {
            $reply_to = $client_email;
        }

        // If user did not provided e-mail addresses and debug is enabled
        if (empty($recipients) and $app->get('debug', 0)) {
            $app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_NO_ADMIN_RECIPIENTS'), 'error');
            $result = false;

            // If user did not provided e-mail addresses
        } elseif (empty($recipients)) {
            $app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_EMAIL_CLIENT'), 'error');
            $result = false;

            // If we failed to send email
        } elseif (!$this->sendEmail($table, $subject, $recipients, $reply_to)) {
            $app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_EMAIL_CLIENT'), 'error');
            $result = false;
        }


        // Send email to client if there is an email address in form
        if ($result and !empty($client_email)) {
            $intro = $this->params->get('intro');
            $intro = empty(trim(strip_tags($intro))) ? Text::_('MOD_BPFORM_DEFAULT_INTRO_EMAIL_VISITOR') : $intro;
            $body = $this->prepareBody($intro, $table);

            // Set reply too so user can answer the copy
            $reply_to = '';
            if (!empty($recipients)) {
                $reply_to = current($recipients);
            }

            $client_subject = $this->params->get('client_subject', Text::_('MOD_BPFORM_DEFAULT_SUBJECT_EMAIL_VISITOR'));
            if (!$this->sendEmail($body, $client_subject, [$client_email], $reply_to)) {
                $app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_EMAIL_CLIENT'), 'error');
                $result = false;
            }
        }

        // If everything went fine
        if ($result) {
            $success_message = $this->params->get('success_message', Text::_('MOD_BPFORM_DEFAULT_SUCCESS_MESSAGE'));
            $app->enqueueMessage($success_message, 'message');
        }

        return $result;
    }

    /**
     * Convert input to array and validate data.
     *
     * @param array $input Input data array.
     *
     * @return array
     *
     * @throws Exception
     */
    protected function prepareAndValidateData(array $input): array
    {

        // Application
        $app = Factory::getApplication();

        // Validate each field input
        $fields = $this->getFields($input);

        $data = [];

        // Process each field
        foreach ($fields as $name => $field) {

            $data_record = (object)[
                'title' => $field->title,
                'value' => key_exists($name, $input) ? $input[$name] : '',
            ];

            // If this field is required and i was not filled
            if ($field->required and (!key_exists($name, $input) or empty($input[$name]))) {
                $app->enqueueMessage(Text::sprintf('MOD_BPFORM_FIELD_S_IS_REQUIRED', $field->title), 'warning');
                $data[$name] = false;
            }

            // This field was set, so map it to data array using field title
            if (key_exists($name, $input)) {
                $data = array_merge($data, [$name => $data_record]);
            }

            // This is a checkbox so change value
            if (in_array($field->type, ['checkbox'])) {

                // If field was checked, change value to YES
                if (key_exists($name, $input)) {
                    $data_record->value = Text::_('JYES');

                    // Field wasn't check, chagne value to NO
                } else {
                    $data_record->value = Text::_('JNO');
                }

                $data[$name] = $data_record;
            }
        }

        // If captcha is enabled, validate it
        if ($this->isCaptchaEnabled() !== false) {
            if (!$this->validateCaptcha()) {
                $data['captcha'] = false;
                $app->enqueueMessage(Text::sprintf('MOD_BPFORM_FIELD_CAPTCHA_ERROR'), 'warning');
            }
        }

        return $data;
    }

    /**
     * Get a list of module form fields.
     *
     * @param array $input Values from last form posting.
     * @param bool $forceUpdate Force update of the fields values.
     *
     * @return array
     */
    public function getFields(array $input = [], bool $forceUpdate = false): array
    {
        $show_labels = (bool)$this->params->get('show_labels', 1);

        // If fields was not processed yet
        $fields = [];
        if (is_null($this->fields) or $forceUpdate) {

            $fields_params = (array)$this->params->get('fields', []);

            foreach ($fields_params as $field) {

                // Default field value
                $field->value = key_exists($field->name, $input) ? $input[$field->name] : '';

                // Create field instance
                if (in_array($field->type, ['heading', 'html'])) {
                    $field->instance = FormHelper::loadFieldType('hidden');
                } elseif ($field->type === 'recipient') {
                    $field->instance = FormHelper::loadFieldType('list');
                } else {
                    $field->instance = FormHelper::loadFieldType($field->type);
                }

                // Setup XML field element
                switch ($field->type) {
                    case 'text':
                        $field->element = new SimpleXMLElement('<field type="text" />');
                        $field->element->addAttribute('hint', $field->hint);
                        break;
                    case 'email':
                        $field->element = new SimpleXMLElement('<field type="email" />');
                        $field->element->addAttribute('hint', $field->hint);
                        break;
                    case 'calendar':
                        $field->element = new SimpleXMLElement('<field type="calendar" />');
                        $field->element->addAttribute('hint', $field->hint);

                        if (empty($field->calendarformat)) {
                            $field->calendarformat = '%Y-%m-%d';
                        }
                        if ($field->calendarhours) {
                            $field->calendarformat .= ' %H:%M';
                            $field->element->addAttribute('showtime', 'true');
                            $field->element->addAttribute('timeformat', $field->calendarhours);
                        }
                        $field->element->addAttribute('format', $field->calendarformat);
                        $field->element->addAttribute('singleheader', 'true');
                        break;
                    case 'tel':
                        $field->element = new SimpleXMLElement('<field type="tel" />');
                        $field->element->addAttribute('hint', $field->hint);
                        break;
                    case 'list':
                        $field->value = $this->getOptionsFieldValue($field, $field->value);
                        $xml = '<field type="list">';
                        $xml .= $this->prepareFieldXMLOptions($field, $field->value);
                        $xml .= '</field>';
                        $field->value = implode(',', $field->value);
                        $field->element = new SimpleXMLElement($xml);
                        break;
                    case 'recipient':
                        $field->value = $this->getOptionsFieldValue($field, $field->value);
                        $xml = '<field type="list">';
                        $xml .= $this->prepareRecipientXMLOptions($field, $field->value);
                        $xml .= '</field>';
                        $field->value = implode(',', $field->value);
                        $field->element = new SimpleXMLElement($xml);
                        $field->element->addAttribute('required', 'required');
                        break;
                    case 'radio':
                        $field->value = $this->getOptionsFieldValue($field, $field->value);
                        $xml = '<field type="radio">';
                        $xml .= $this->prepareFieldXMLOptions($field, $field->value);
                        $xml .= '</field>';
                        $field->value = implode(',', $field->value);
                        $field->element = new SimpleXMLElement($xml);
                        break;
                    case 'checkboxes':
                        $xml = '<field type="checkboxes">';
                        $xml .= $this->prepareFieldXMLOptions($field, $this->getOptionsFieldValue($field, $field->value));
                        $xml .= '</field>';
                        $field->element = new SimpleXMLElement($xml);
                        break;
                    case 'textarea':
                        $field->element = new SimpleXMLElement('<field type="textarea" />');
                        $field->element->addAttribute('hint', $field->hint);
                        break;
                    case 'heading':
                    case 'html':
                        $field->element = new SimpleXMLElement('<field type="hidden" />');
                        break;
                    case 'checkbox':
                        $field->element = new SimpleXMLElement('<field type="checkbox" />');
                        if ($field->checked) {
                            $field->element->addAttribute('checked', 'true');
                        }
                        break;
                }

                // Set last parameters
                if (isset($field->instance) and isset($field->element)) {
                    if (!$show_labels and !in_array($field->type, ['checkbox'])) {
                        $field->element->addAttribute('labelclass', 'sr-only');
                    }
                    $field->element->addAttribute('name', $this->formPrefix . '[' . $field->name . ']');
                    $field->element->addAttribute('id', $this->formPrefix . '_' . $field->name);
                    $field->element->addAttribute('label', $field->title);
                    if ($field->required) {
                        $field->element->addAttribute('required', 'true');
                    }
                }

                $fields = array_merge($fields, [$field->name => $field]);
            }
        }

        return $fields;
    }

    /**
     * Check if captcha is enabled.
     *
     * @param Registry $params Module params.
     *
     * @return mixed    Returns string if captcha is enabled or false if not.
     *
     * @throws Exception
     */
    public function isCaptchaEnabled()
    {
        $app = Factory::getApplication();
        $plugin = $app->get('captcha');
        if ($app->isClient('site')) {
            $plugin = $app->getParams()->get('captcha', $plugin);
        }

        // Check if captcha is enabled
        if ($plugin === 0 || $plugin === '0' || $plugin === '' || $plugin === null || !$this->params->get('captcha', 0)) {
            return false;
        }

        return $plugin;
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
        PluginHelper::importPlugin('captcha', $this->isCaptchaEnabled());
        $dispatcher = JEventDispatcher::getInstance();
        try {
            $response = $dispatcher->trigger('onCheckAnswer');
        } catch (Exception $e) {
            $app = Factory::getApplication();
            if ($app->get('debug')) {
                $app->enqueueMessage($e->getMessage(), 'error');
            }
            $response = false;
        }

        return ($response === true) or ($response === [true]);
    }

    /**
     * Prepare data html table.
     *
     * @param array $data Form data.
     *
     * @return string
     */
    protected function createTable(array $data): string
    {
        ob_start();

        require ModuleHelper::getLayoutPath('mod_bpform', $this->params->get('layout', 'default') . '_table');

        return ob_get_clean();
    }

    /**
     * Check if this e-mail is valid.
     *
     * @param string $email E-mail address to validate.
     *
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return Mail::validateAddress($email, 'php');
    }

    /**
     * Look for e-mail in form fields.
     *
     * @param array $input Form data.
     *
     * @return string
     */
    protected function getClientEmail(array $input): string
    {
        $fields = $this->getFields($input);
        $email = '';
        foreach ($fields as $name => $field) {

            // Look for first e-mail type field in fields list
            if ($field->type == 'email') {

                if (key_exists($field->name, $input) and !empty($input[$field->name]) and $this->isValidEmail($input[$field->name])) {
                    $email = $input[$field->name];
                    break;
                }
            }
        }

        return $email;
    }

    /**
     * Send email form.
     *
     * @param string $body E-mail body.
     * @param string $subject E-mail subject.
     * @param array $recipients Array of E-mail addresses.
     * @param string $reply_to Reply-to e-mail address
     *
     * @return bool
     */
    protected function sendEmail(string $body, string $subject, array $recipients, string $reply_to = ''): bool
    {

        // E-mail class instance
        $mail = Factory::getMailer();

        // Add recipients
        foreach ($recipients as $recipient) {
            $mail->addRecipient($recipient);
        }

        // Add reply to if exists
        if (!empty($reply_to)) {
            $mail->addReplyTo($reply_to);
        }

        // Set body
        $mail->setBody($body);
        $mail->isHtml();

        // Set subject
        $mail->setSubject($subject);

        // Send the email
        return $mail->Send();
    }

    /**
     * Prepare message body
     *
     * @param string $intro Message intro.
     * @param string $table Message data table.
     *
     * @return string
     */
    protected function prepareBody(string $intro, string $table): string
    {
        return $intro . $table;
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

        // Application instance
        $app = Factory::getApplication();

        // Get captcha plugin
        $plugin = $this->isCaptchaEnabled($this->params);
        if ($plugin === false) {
            return '';
        }

        // Prepare namespace
        $namespace = "mod_bpform.{$this->module->id}.captcha";

        // Try to create captcha field
        try {
            // Get an instance of the captcha class that we are using
            $captcha = Captcha::getInstance($plugin, ['namespace' => $namespace]);

            return $captcha->display('captcha', 'mod_bpform_captcha_' . $this->module->id);
        } catch (RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');

            return '';
        }
    }

    /**
     * Prepare XML field options for checkboxes,radios and list type fields.
     *
     * @param object $field Field object.
     * @param array $value Field value.
     *
     * @return string
     */
    public function prepareFieldXMLOptions(object $field, array $value = []): string
    {
        $xml = '';

        // For list/select type fields, add a placeholder if exists
        if (!empty($field->hint) and $field->type === 'list') {
            $xml .= '<option value="">- ' . $field->hint . ' -</option>';
        }

        // Render field options
        $options = (array)$field->options;
        $selected_attribute = $field->type === 'list' ? 'selected' : 'checked';
        foreach ($options as $option) {
            $selected = in_array($option->value, $value) ? ' ' . $selected_attribute . '="' . $selected_attribute . '"' : '';
            $xml .= '<option value="' . htmlspecialchars($option->value) . '" ' . $selected . '>' . htmlspecialchars($option->title) . '</option>';
        }

        return $xml;
    }

    /**
     * Prepare XML field options for checkboxes,radios and list type fields.
     *
     * @param object $field Field object.
     * @param array $value Field value.
     *
     * @return string
     */
    public function prepareRecipientXMLOptions(object $field, array $value = []): string
    {
        $xml = '';

        // For list/select type fields, add a placeholder if exists
        if (!empty($field->hint)) {
            $xml .= '<option value="">- ' . $field->hint . ' -</option>';
        }

        // Render field options
        $options = (array)$this->params->get('recipient_emails');

        foreach ($options as $option) {
            $selected = in_array($option->email, $value) ? ' selected="selected"' : '';
            $xml .= '<option value="' . htmlspecialchars($option->email) . '" ' . $selected . '>' . htmlspecialchars($option->name) . '</option>';
        }

        return $xml;
    }

    /**
     * Get field value using field value set by user.
     *
     * @param object $field Field object.
     * @param array $default Default field value (set by user)
     *
     * @return array
     */
    public function getOptionsFieldValue(object $field, $default = []): array
    {
        $value = (array)$default;
        $value = array_filter($value);
        if (empty($value)) {
            $options = (array)$field->options;
            foreach ($options as $option) {
                if ((bool)$option->selected) {
                    $value[] = $option->value;
                }
            }
        }

        return $value;
    }

    public function getAdministrationRecipients(Registry $params): array
    {

    }

    /**
     * Get for prefix for current module instance.
     *
     * @return string
     */
    public function getFormPrefix(): string
    {
        return $this->formPrefix;
    }

    /**
     * Get recipients from input data and parameters.
     *
     * @param array $input Input data.
     *
     * @return array
     */
    protected function getRecipients(array $input): array
    {
        $recipients = [];

        // If user selected contact as recipient
        if ($this->params->get('recipient') === 'contact') {

            // Load contact
            JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_contact/tables');
            $contact = JTable::getInstance('Contact', 'ContactTable');
            $contact_id = $this->get('recipient_contact');

            if ($contact_id > 0 and $contact->load($contact_id) and !empty($contact->email_to) and $this->isValidEmail($contact->email_to)) {
                $recipients[] = $contact->email_to;
            };

            // User selected list of e-mail addresses as the recipients
        } elseif ($this->params->get('recipient') == 'emails') {
            $recipients = (array)$this->params->get('recipient_emails', []);
            $recipients = array_column($recipients, 'email');

            // If user selected recipient, limit recipients to only the selected one
            if ($this->params->get('recipient') === 'emails') {

                // Look for recipient field input
                $fields = $this->getFields($input);
                foreach ($fields as $field) {
                    if ($field->type === 'recipient' and !empty($input[$field->name])) {

                        // Leave only recipients that exists in input
                        $recipients = array_intersect($recipients, [$input[$field->name]]);

                        break;
                    }
                }

            }

        }

        // Clear recipients from empty strings
        $recipients = array_filter($recipients);

        return $recipients;
    }

}