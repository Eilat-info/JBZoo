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

$type   = $this->getElementType();
$isCore = ($this->isCore() ? 'core' : 'simple');

$attr = array(
    'data-identifier' => $this->identifier,
    'class'           => array(
        'jbprice-' . $type, // very IMPORTANT class for element templates (DON'T REMOVE!)
        'jbprice-param',                    // TODO PLZ, kill me (but JS bugs!)
        'jbprice-param-' . $type,           // TODO PLZ, kill me (but JS bugs!)
        'jbprice-' . $isCore . '-param',    // TODO PLZ, kill me (but JS bugs!)
        'jsElement',
        'jsPriceElement',
        'js' . ucfirst($type),
        'js' . ucfirst($isCore)
    )
);

echo '<div ' . $this->_attrs($attr) . '>' . $html . '</div>';
