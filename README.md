# BP Form

![PHP 7.2](https://github.com/bpextensions/mod_bpform/workflows/PHP%207.2-8.0/badge.svg)

Simple Joomla! 3 contact form module.

# Features

- Manage form fields:
  - Text
  - Phone
  - E-mail
  - Calendar
  - Textarea
    - Checkbox
    - Checkboxes
    - Radio
    - Select
  - File
  - Recipient (select recipient from a predefined list)
  - HTML
  - Heading

- Send e-mail to selected list of e-mail addresses, contact or select the recipient from a predefined list.
- Available translations:
  - English
  - Polish

# Requirements

- PHP 7.2+
- Joomla 3.9.x

# How to build from repository

## Build requirements

- PHP 7.2
- Composer
- Node/Npm

## Build process

If you are a developer, and you are willing to build extension from this repo you will need Composer installed globally.
Here are instructions how to build installation package from scratch.

- Prepare a clean Joomla! installation
- Clone this repo on your installation or cope its contents straight to your Joomla! root directory
- Run `composer install`
- Run `composer build`
- Your installation zip files should now be read in `/.build` directory.

# Changelog

## v1.2.1
- Fix files uploads.
- Fix translation of upload error messages.
- Update PHP Requirements.

## v1.2.0
- Added files upload

## v1.1.6
- Added updates server

## v1.1.5
- Added file upload support.

## v1.0.4
- Adding Calendar type field.

## v1.0.0
- Initial release.