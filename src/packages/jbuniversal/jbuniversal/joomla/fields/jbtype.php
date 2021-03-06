<?php
/**
 * JBZoo Application
 *
 * This file is part of the JBZoo CCK package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package    Application
 * @license    GPL-2.0
 * @copyright  Copyright (C) JBZoo.com, All rights reserved.
 * @link       https://github.com/JBZoo/JBZoo
 * @author     Denis Smetannikov <denis@jbzoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

// load config
require_once(JPATH_ADMINISTRATOR . '/components/com_zoo/config.php');

/**
 * Class JFormFieldJBType
 */
class JFormFieldJBType extends JFormField
{

    protected $type = 'jbtype';

    /**
     * @return null|string
     */
    public function getInput()
    {
        $app      = App::getInstance('zoo');
        $typePath = $app->path->path('jbapp:types');
        $attr     = array();

        $options = array('' => JText::_('JBZOO_FIELDS_TYPE'));

        $files = JFolder::files($typePath, '\.config$');
        if (!empty($files)) {
            foreach ($files as $file) {
                $typeId           = pathinfo($file, PATHINFO_FILENAME);
                $options[$typeId] = json_decode($app->jbfile->read($typePath . '/' . $file))->name;
            }
        }

        if ((int)$this->element->attributes()->headhide) {
            unset($options['']);
        }

        if ((int)$this->element->attributes()->multiple) {
            $attr['multiple'] = 'multiple';
        }

        if ((int)$this->element->attributes()->required) {
            $attr['required'] = 'required';
        }

        return $app->jbhtml->select($options, $this->getName($this->fieldname), $attr, $this->value);
    }
}