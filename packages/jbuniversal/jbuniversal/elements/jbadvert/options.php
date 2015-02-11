<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 * @package     jbzoo
 * @version     2.x Pro
 * @author      JBZoo App http://jbzoo.com
 * @copyright   Copyright (C) JBZoo.com,  All rights reserved.
 * @license     http://jbzoo.com/license-pro.php JBZoo Licence
 * @coder       Alexander Oganov <t_tapak@yahoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

$element = $parent->element;
$config  = $element->config;

foreach ($parent->element->data as $key => $text) {
    $name = $element->getControlName('prices');
    $id   = $this->app->jbstring->getId($key);

    echo $this->app->jbhtml->text($name . '[' . $key . ']', $config->find('prices.' . $key), array('id' => $id))
        . '<label for="' . $id . '">'
        . $text
        . '</label>';
}