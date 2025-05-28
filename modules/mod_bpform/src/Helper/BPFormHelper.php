<?php

/**
 * @package     ${package}
 * @subpackage  ${subpackage}
 *
 * @copyright   Copyright (C) ${build.year} ${copyrights},  All rights reserved.
 * @license     ${license.name}; see ${license.url}
 */

namespace BPExtensions\Module\BPForm\Site\Helper;

use BPExtensions\Module\BPForm\Site\Entity\FieldPrototype;
use BPExtensions\Module\BPForm\Site\Event\StoreMessageEvent;
use BPExtensions\Module\BPForm\Site\Exception\BlackListException;
use BPExtensions\Module\BPForm\Site\Exception\CaptchaException;
use BPExtensions\Module\BPForm\Site\Exception\NoRecipientsException;
use BPExtensions\Module\BPForm\Site\Storage\MailStorage;
use BPExtensions\Module\BPForm\Site\Validator\FormValidator;
use BPExtensions\Module\BPForm\Site\Validator\SpamValidator;
use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactory;
use Joomla\CMS\User\User;
use Joomla\Component\Contact\Administrator\Table\ContactTable;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Registry\Registry;
use RuntimeException;
use SimpleXMLElement;

defined('_JEXEC') or die;

/**
 * Helper for a BP Form module.
 */
class BPFormHelper
{
    use DispatcherAwareTrait;

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

    /**
     * Current application instance.
     *
     * @var CMSApplication
     */
    protected $app;

    /**
     * @var null|SpamValidator
     */
    protected $spamValidator;

    /**
     * @var null|FormValidator
     */
    protected $formValidator;

    /**
     * @var null|User
     */
    protected $user;

    /**
     * @var MailStorage
     */
    protected $mailStorage;

    /**
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        $this->params             = $config['params'];
        $this->module             = $config['module'];
        $this->formPrefix         = $config['formPrefix'];
        $this->app                = Factory::getApplication();
        $this->spamValidator = new SpamValidator($this->app, $config);
        $this->formValidator = new FormValidator($this->app, $config);
        $this->user = $this->app->getIdentity();
        $this->mailStorage = new MailStorage();

        if (!$this->app instanceof CMSApplication) {
            throw new RuntimeException("Unable to get Application instance.");
        }
    }

    /**
     * Process form input.
     *
     * @param   array  $input  Form input data array.
     * @param   array  $files  Form input files data array.
     *
     * @return bool|null
     *
     * @throws RuntimeException
     * @throws Exception
     */
    public function submit(array $input = []): ?bool
    {

        // Submission result
        $result = true;

        // There is nothing to process, exit method
        if ($input === []) {
            return null;
        }

        // Prepare data table
        $data = $this->prepareData($input);

        // Check if every field that is required was filled
        if (!$this->isValidInput($data)) {
            return false;
        }

        // Collect attachments from validate data
        $attachments = $this->collectAttachments($data);

        // Create inquiry HTML table
        $renderedFormValues = $this->renderFormValues($data);

        // Load recipients list from parameters and input
        $recipients = $this->getRecipients($input);
        if ($recipients === []) {
            throw new NoRecipientsException();
        }

        // Admin message subject
        $subject = $this->params->get('admin_subject', Text::_('MOD_BPFORM_DEFAULT_SUBJECT_EMAIL_ADMIN'));

        // Look for client email and set a reply to field on the admin email
        $client_email = $this->getClientEmail($input, $this->getFields($input));
        $visitor_sender_mode = (int)$this->params->get('visitor_sender_mode', 1);
        $admin_sender_mode   = (int)$this->params->get('admin_sender_mode', 1);
        $reply_to            = '';
        $sender              = '';

        // If visitor sender mode is set to reply_to
        if ($admin_sender_mode === 1 && !empty($client_email)) {
            $reply_to = $client_email;

            // if visitor sender mode is set to Sender
        } elseif ($admin_sender_mode === 0 && !empty($client_email)) {
            $sender = $client_email;
        }

        // Run adds
        $dispatcher = $this->app->getDispatcher();
        $event      = new StoreMessageEvent(
            StoreMessageEvent::NAME,
            ['data' => $data, 'body' => $renderedFormValues, 'params' => $this->params]
        );
        $dispatcher->dispatch($event::NAME, $event);

        // If we failed to send the message to the administrator
        if (!$this->mailStorage->store($renderedFormValues, $subject, $recipients, $reply_to, $sender, $attachments)) {
            $this->app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_EMAIL_CLIENT'), CMSApplicationInterface::MSG_ERROR);
            $result = false;
        }

        // Send an email copy to the client if there is an email address in the form
        if ($result && !empty($client_email) && $this->params->get('send_confirmation', false)) {
            $result = $this->notifyClient($renderedFormValues, $visitor_sender_mode, $recipients, $client_email);
        }

        // If everything went fine
        if ($result) {
            $success_message = $this->params->get('success_message', Text::_('MOD_BPFORM_DEFAULT_SUCCESS_MESSAGE'));
            $this->app->enqueueMessage($success_message, 'message');
        }

        return $result;
    }

    /**
     * Notify the client about receiving the message.
     *
     * @param   string  $renderedFormValues
     * @param   int     $visitor_sender_mode
     * @param   array   $recipients
     * @param   string  $client_email
     *
     * @return bool
     * @throws Exception
     */
    protected function notifyClient(
        string $renderedFormValues,
        int $visitor_sender_mode,
        array $recipients,
        string $client_email
    ): bool {
        $intro = $this->params->get('intro');
        $intro = empty(trim(strip_tags($intro))) ? '' : $intro;

        // If there should the data copy in the message
        if ($this->params->get('send_confirmation_data', false)) {
            $body = $this->prepareBody($intro, $renderedFormValues);
        } else {
            $body = $intro;
        }

        // Set reply too so the user can answer the copy
        $reply_to = '';
        $sender   = '';

        // If visitor sender mode is set to reply_to
        if ((int)$visitor_sender_mode === 1) {
            $reply_to = current($recipients);

            // if visitor sender mode is set to Sender
        } elseif ((int)$visitor_sender_mode === 0) {
            $sender = current($recipients);
        }

        // Add message attachments to the confirmation message
        $replyAttachments = $this->params->get('message_attachments', []);
        $attachments      = [];
        foreach ($replyAttachments as $attachment) {
            $attachmentPath = realpath(JPATH_ROOT . '/' . $attachment->file);
            if ($attachment->file !== '' && file_exists($attachmentPath)) {
                $attachments[] = $attachmentPath;
            }
        }

        $client_subject = $this->params->get('client_subject', Text::_('MOD_BPFORM_DEFAULT_SUBJECT_EMAIL_VISITOR'));
        if (!$this->mailStorage->store($body, $client_subject, [$client_email], $reply_to, $sender, $attachments)) {
            $this->app->enqueueMessage(Text::_('MOD_BPFORM_ERROR_EMAIL_CLIENT'), CMSApplicationInterface::MSG_ERROR);

            return false;
        }

        return true;
    }

    /**
     * Convert input to array and validate data.
     *
     * @param   array  $input  Input data array.
     *
     * @return array
     *
     * @throws RuntimeException
     * @throws Exception
     */
    protected function prepareData(array $input): array
    {

        // Validate each field input
        /**
         * @var FieldPrototype[] $fields
         */
        $fields = $this->getFields($input);
        $data = [];

        // Check client IP Address against the black list
        if (!$this->spamValidator->clientInBlacklist()) {
            if ($this->user->authorise('core.admin')) {
                throw new BlackListException(SpamValidator::getClientIp());
            }

            // Invalidate data to stop message from being sent
            throw new BlackListException();
        }

        $data = $this->processDataList($fields, $data, $input);

        // If captcha is enabled, validate it
        if (($this->spamValidator::isCaptchaEnabled($this->params) !== false) && !$this->spamValidator->validateCaptcha($input)) {
            throw new CaptchaException();
        }

        return $data;
    }

    protected function processDataList(array &$fields, array $data, array &$input): array
    {

        // Process each field
        foreach ($fields as $name => $field) {
            // The default field value is empty
            $value = '';

            // Prepare and validate file input
            if (array_key_exists($name, $input)) {
                if ($field->type === 'file') {
                    // Prepare files input format
                    $files = $this->prepareFiles($input[$name]);

                    // Process and validate each file
                    $errors = $this->formValidator->validateFiles($files, $field);

                    // If all files in this field are ok, set them
                    if (empty($errors)) {
                        $value = $files;
                        // There are errors, so display them and invalidate input
                    } else {
                        foreach ($errors as $error) {
                            $this->app->enqueueMessage(Text::sprintf($error, $field->title), 'warning');
                        }

                        $data = array_merge($data, [$name => false]);
                    }
                } else {
                    $value = $input[$name];
                }
            }

            // If data is not an invalid file, prepare its data record
            if (!array_key_exists($name, $data)) {

                $data_record = (object)[
                    'title' => $field->title,
                    'type'  => $field->type,
                    'value' => $value,
                ];

                // This field was set, so map it to a data array using the field name
                if (array_key_exists($name, $input)) {
                    $data = array_merge($data, [$name => $data_record]);
                }

                // This is a checkbox so change the value
                if ($field->type === 'checkbox') {
                    // If the field was checked, change the value to YES
                    $data_record->value = array_key_exists($name, $input) ? Text::_('JYES') : Text::_('JNO');
                    $data[$name] = $data_record;
                } elseif ($field->type === 'group') {

                    $input[$name]           = '';
                    $data_record->value     = '';
                    $data_record->subfields = $this->processDataList($field->subfields, [], $input);
                    $data[$name] = $data_record;
                }
            }

            // If this field is required and it's blank
            if ($field->required && !in_array($field->type, ['group', 'heading', 'html']) && (!array_key_exists(
                        $name,
                        $input
                    ) || empty($input[$name]))) {
                $this->app->enqueueMessage(Text::sprintf('MOD_BPFORM_FIELD_S_IS_REQUIRED', $field->title), 'warning');
                $data[$name] = false;
            }

            // Check field value for forbidden words
            $filterValue = is_array($value) ? json_encode($value) : (string)$value;
            if (!$this->spamValidator->filterText((string)$filterValue)) {
                $this->app->enqueueMessage(Text::_('MOD_BPFORM_FIELD_CAPTCHA_ERROR'), 'warning');
                if ($this->user->authorise('core.admin')) {
                    $this->app->enqueueMessage(
                        Text::sprintf('MOD_BPFORM_FIELD_SPAM_BLACKLIST_ERROR_S', $field->title),
                        'warning'
                    );
                }
                $data[$name] = false;
            }
        }

        return $data;
    }

    /**
     * Get a list of module form fields.
     *
     * @param   array  $input        Values from last form posting.
     * @param   bool   $forceUpdate  Force update of the fields values.
     *
     * @return array
     * @throws Exception
     */
    public function getFields(array $input = [], bool $forceUpdate = false): array
    {
        $show_labels = (bool)$this->params->get('show_labels', 1);
        $form = new Form($this->getFormPrefix(), ['control' => $this->getFormPrefix()]);

        // If fields was not processed yet
        if (is_null($this->fields) || $forceUpdate) {

            $fieldsParamsArray = (array)$this->params->get('fields', []);
            $this->fields      = $this->prepareFieldsList($fieldsParamsArray, $input, $form, $show_labels);
        }

        return $this->fields;
    }

    protected function prepareFieldsList(array $fields_params, array &$input, Form $form, bool $show_labels): array
    {
        $fields = [];

        $isPosted = Factory::getApplication()->input->getMethod() === 'POST';

        foreach ($fields_params as $field) {
            /**
             * @var FieldPrototype $field
             */

            // Default field value
            $field->value = array_key_exists($field->name, $input) ? $input[$field->name] : '';

            if ($field->type === 'heading') {
                $field->value = '';
            } elseif ($field->type === 'html') {
                $field->value = $field->html;
            }

            // Create field instance
            if (in_array($field->type, ['heading', 'html'])) {
                $field->instance = FormHelper::loadFieldType('hidden');
            } elseif ($field->type === 'group') {
                $field->instance = null;
            } elseif ($field->type === 'recipient') {
                $field->instance = FormHelper::loadFieldType('list');
            } else {
                $field->instance = FormHelper::loadFieldType($field->type);
            }

            // Set form object to silence the Joomla API
            if ($field->instance !== null) {
                $field->instance->setForm($form);

                if ($isPosted) {
                    $field->instance->setValue($field->value);
                }

            }

            // Setup XML field element
            switch ($field->type) {
                case 'text':
                    $field->element = new SimpleXMLElement('<field type="text" />');
                    break;
                case 'email':
                    $field->element = new SimpleXMLElement('<field type="email" />');
                    break;
                case 'calendar':
                    $field->element = new SimpleXMLElement('<field type="calendar" />');

                    if (empty($field->calendarformat)) {
                        $field->calendarformat = '%Y-%m-%d';
                    }
                    if ($field->calendarhours) {
                        if (stripos($field->calendarformat, '%H') === false && stripos($field->calendarformat,
                                '%M') === false && stripos($field->calendarformat, '%S') === false) {
                            $field->calendarformat .= ' %H:%M';
                        }
                        $field->element->addAttribute('showtime', 'true');
                        $field->element->addAttribute('timeformat', $field->calendarhours);
                    }
                    $field->element->addAttribute('format', $field->calendarformat);
                    $field->element->addAttribute('singleheader', 'true');
                    break;
                case 'tel':
                    $field->element = new SimpleXMLElement('<field type="tel" />');
                    break;
                case 'file':
                    $field->element = new SimpleXMLElement('<field type="file" />');
                    if ($field->multiplefiles) {
                        $field->element->addAttribute('multiple', 'true');
                    }
                    if (!empty($field->mimeaccept)) {
                        $field->element->addAttribute('accept', $field->mimeaccept);
                    }
                    break;
                case 'list':
                    $field->value   = $this->getOptionsFieldValue($field, (array)$field->value);
                    $xml            = '<field type="list">';
                    $xml            .= $this->prepareFieldXMLOptions($field, $field->value);
                    $xml            .= '</field>';
                    $field->value   = implode(',', $field->value);
                    $field->element = new SimpleXMLElement($xml);
                    break;
                case 'recipient':
                    $field->value   = $this->getOptionsFieldValue($field, (array)$field->value);
                    $xml            = '<field type="list">';
                    $xml            .= $this->prepareRecipientXMLOptions($field, $field->value);
                    $xml            .= '</field>';
                    $field->value   = implode(',', $field->value);
                    $field->element = new SimpleXMLElement($xml);
                    $field->element->addAttribute('required', 'required');
                    break;
                case 'radio':
                    $field->value   = $this->getOptionsFieldValue($field, (array)$field->value);
                    $xml            = '<field type="radio">';
                    $xml            .= $this->prepareFieldXMLOptions($field, $field->value);
                    $xml            .= '</field>';
                    $field->value   = implode(',', $field->value);
                    $field->element = new SimpleXMLElement($xml);
                    break;
                case 'checkboxes':
                    $xml            = '<field type="checkboxes">';
                    $xml            .= $this->prepareFieldXMLOptions($field,
                        $this->getOptionsFieldValue($field, (array)$field->value));
                    $xml            .= '</field>';
                    $field->element = new SimpleXMLElement($xml);
                    break;
                case 'textarea':
                    $field->element = new SimpleXMLElement('<field type="textarea" />');
                    break;
                case 'heading':
                case 'html':
                    $field->element = new SimpleXMLElement('<field type="hidden" />');
                    break;
                case 'group':
                    $field->element   = null;
                    $field->subfields = $this->prepareFieldsList((array)$field->subfields, $input, $form, $show_labels);
                    break;
                case 'checkbox':
                    $field->element = new SimpleXMLElement('<field type="checkbox" />');
                    if ($field->checked) {
                        $field->element->addAttribute('checked', 'true');
                    }
                    break;
            }

            // Set field hint if present
            if (isset($field->element) && $field->hint !== '') {
                $field->element->addAttribute('hint', $field->hint);
            }

            // Set field description if present
            if (isset($field->element) && $field->description !== '') {
                $field->element->addAttribute('description', $field->description);
            }

            // Finishing parameters
            if (isset($field->instance, $field->element)) {

                // If labels are disabled and a default placeholder was not set
                if (!$show_labels && empty($field->hint) && !in_array($field->type, ['checkbox', 'checkboxes'])) {
                    $hint = $field->title . ($field->required ? ' *' : '');
                    $field->element->addAttribute('hint', $hint);
                }

                $field->element->addAttribute('name', $field->name);

                $label_html_clear = isset($field->label_html) ? trim(strip_tags($field->label_html)) : '';
                if ($field->type === 'checkbox' && ($field->label_html_enabled ?? false) && !empty($label_html_clear)) {
                    $field->element->addAttribute('label', $field->label_html);
                } else {
                    $field->element->addAttribute('label', $field->title);
                }

                if ($field->required) {
                    $field->element->addAttribute('required', 'true');
                }
            }

            $fields = array_merge($fields, [$field->name => $field]);
        }

        return $fields;
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
     * Get field value using field value set by user.
     *
     * @param   object  $field    Field object.
     * @param   array   $default  Default field value (set by user)
     *
     * @return array
     */
    public function getOptionsFieldValue(object $field, array $default = []): array
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

    /**
     * Prepare XML field options for checkboxes,radios and list type fields.
     *
     * @param   object  $field  Field object.
     * @param   array   $value  Field value.
     *
     * @return string
     */
    public function prepareFieldXMLOptions(object $field, array $value = []): string
    {
        $xml = '';

        // For list/select type fields, add a placeholder if exists
        if (!empty($field->hint) && $field->type === 'list') {
            $xml .= '<option value="">- ' . $field->hint . ' -</option>';
        }

        // Render field options
        $options            = (array)$field->options;
        $selected_attribute = $field->type === 'list' ? 'selected' : 'checked';
        foreach ($options as $option) {
            $selected = in_array($option->value, $value,
                false) ? ' ' . $selected_attribute . '="' . $selected_attribute . '"' : '';
            $xml      .= '<option value="' . htmlspecialchars($option->value) . '" ' . $selected . '>' . htmlspecialchars($option->title) . '</option>';
        }

        return $xml;
    }

    /**
     * Prepare XML field options for checkboxes,radios and list type fields.
     *
     * @param   object  $field  Field object.
     * @param   array   $value  Field value.
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
            $selected = in_array($option->email, $value, false) ? ' selected="selected"' : '';
            $xml      .= '<option value="' . htmlspecialchars($option->email) . '" ' . $selected . '>' . htmlspecialchars($option->name) . '</option>';
        }

        return $xml;
    }

    /**
     * Prepare files array.
     *
     * @param   array  $input  Array of input files.
     *
     * @return array
     */
    public function prepareFiles(array $input): array
    {
        $files = [];

        if (!empty($input) && array_key_exists('tmp_name', $input)) {
            $files[] = $input;
        } elseif (!empty($input) && array_key_exists('tmp_name', $input[0])) {
            $files = $input;
        }

        return $files;
    }

    /**
     * Collect attachments from validated data.
     *
     * @param   array  $data  Validated data.
     *
     * @return array
     */
    protected function collectAttachments(array $data): array
    {
        $attachments = [];

        // Collect each attachment
        foreach ($data as $name => $entry) {
            if ($entry->type === 'file' && !empty($entry->value)) {
                $attachments = array_merge($attachments, $entry->value);
                continue;
            }

            if ($entry->type === 'group') {
                foreach ($entry->subfields as $subfield_name => $subfield_entry) {
                    if ($subfield_entry->type === 'file' && !empty($subfield_entry->value)) {
                        $attachments = array_merge($attachments, $subfield_entry->value);
                    }
                }
            }
        }

        return $attachments;
    }

    /**
     * Prepare data html table.
     *
     * @param   array  $data  Form data.
     *
     * @return string
     */
    protected function renderFormValues(array $data): string
    {
        ob_start();

        $layout = $this->params->get('layout', 'default');

        require ModuleHelper::getLayoutPath('mod_bpform', $layout . '_mail');

        return ob_get_clean();
    }

    /**
     * Get recipients from input data and parameters.
     *
     * @param   array  $input  Input data.
     *
     * @return array
     * @throws Exception
     */
    protected function getRecipients(array $input): array
    {
        $recipients = [];

        // If user selected contact as recipient
        if ($this->params->get('recipient') === 'contact') {

            /**
             * @var MVCFactory   $MVCFactory
             * @var ContactTable $contact
             */
            $MVCFactory = $this->app->bootComponent('com_contact')->getMVCFactory();

            try {
                $contact = $MVCFactory->createTable('Contact', 'Administrator');
                if ($contact === null) {
                    throw new RuntimeException();
                }
            } catch (Exception $exception) {
                throw new RuntimeException('Unable to create a ContactTable instance!', 500);
            }

            $contact_id = (int)$this->params->get('recipient_contact');

            if ($contact_id > 0 && $contact->load($contact_id) && !empty($contact->email_to) && $this->formValidator->isValidEmail($contact->email_to)) {
                $recipients[] = $contact->email_to;
            }

            // User selected list of e-mail addresses as the recipients
        } elseif ($this->params->get('recipient') === 'emails') {
            $recipients = (array)$this->params->get('recipient_emails', []);
            $recipients = array_column($recipients, 'email');

            // If user selected recipient, limit recipients to only the selected one
            $fields = $this->getFields($input);
            foreach ($fields as $field) {
                if ($field->type === 'recipient' && !empty($input[$field->name])) {

                    // Leave only recipients that exists in input
                    $recipients = array_intersect($recipients, [$input[$field->name]]);

                    break;
                }
            }

        }

        // Clear recipients from empty strings
        return array_filter($recipients);
    }


    /**
     * Look for e-mail in form fields.
     *
     * @param   array  $input  Form data.
     *
     * @return string
     * @throws Exception
     */
    protected function getClientEmail(array $input, array $fields = []): string
    {
        $email  = '';
        foreach ($fields as $name => $field) {

            // Look for first e-mail type field in fields list
            if ($field->type === 'email') {

                if (array_key_exists($field->name,
                        $input) && !empty($input[$field->name]) && $this->formValidator->isValidEmail($input[$field->name])) {
                    $email = $input[$field->name];
                    break;
                }
            }

            // If this is a group, look inside subfields.
            if (($field->type === 'group') && $found_mail = $this->getClientEmail($input, $field->subfields ?? [])) {
                return $found_mail;
            }
        }

        return $email;
    }

    /**
     * Check if form build by this module has file type fields.
     *
     * @return bool
     * @throws Exception
     */
    public function hasFilesUpload(): bool
    {
        $result = false;

        // Get list of form fields
        $fields = $this->getFields();


        // Check if form has file type field
        foreach ($fields as $field) {
            if ($field->type === 'file') {
                $result = true;
                break;
            }

            if ($field->type === 'group') {
                foreach ($field->subfields as $subfield) {
                    if ($subfield->type === 'file') {
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Prepare message body
     *
     * @param   string  $intro  Message intro.
     * @param   string  $table  Message data table.
     *
     * @return string
     */
    protected function prepareBody(string $intro, string $table): string
    {
        return $intro . $table;
    }

    public function getSpamValidator(): SpamValidator
    {
        return $this->spamValidator;
    }

    public function getFormValidator(): FormValidator
    {
        return $this->formValidator;
    }

    public function isValidInput(array $data): bool
    {
        foreach ($data as $key => $input) {
            if ($input === false) {
                return false;
            }

            if (is_object($input) && $input->type === 'group' && in_array(false, $input->subfields, true)) {
                return false;
            }
        }

        return true;
    }

}