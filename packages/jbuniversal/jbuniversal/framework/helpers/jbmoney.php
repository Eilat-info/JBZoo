<?php
/**
 * JBZoo App is universal Joomla CCK, application for YooTheme Zoo component
 *
 * @package     jbzoo
 * @version     2.x Pro
 * @author      JBZoo App http://jbzoo.com
 * @copyright   Copyright (C) JBZoo.com,  All rights reserved.
 * @license     http://jbzoo.com/license-pro.php JBZoo Licence
 * @coder       Denis Smetannikov <denis@jbzoo.com>
 */

// no direct access
defined('_JEXEC') or die('Restricted access');


/**
 * Class JBMoneyHelper
 */
class JBMoneyHelper extends AppHelper
{
    const BASE_CURRENCY = 'EUR'; // don't touch!
    const PERCENT = '%';

    static $curList = array();

    /**
     * @var array
     */
    protected $_config = array();

    /**
     * @var boolean
     */
    protected $_isBuilded = FALSE;

    /**
     * @var array
     */
    protected $_defaultFormat = array(
        'symbol'          => '',
        'num_decimals'    => 2,
        'decimal_sep'     => '.',
        'thousands_sep'   => ' ',
        'format_negative' => '-%v %s',
        'format_positive' => '%v %s',
    );

    /**
     * @param App $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->_config = JBModelConfig::model();
    }

    /**
     * Get all currency values and cache in memory
     */
    public function init()
    {
        // optimize
        if ($this->_isBuilded || !empty(self::$curList)) {
            return self::$curList;
        }

        $this->app->jbdebug->mark('jbmoney::init::start');

        $curParams = $this->_config->getGroup('cart.currency')->get('list');

        $cacheKey = serialize(array(
            'params' => (array)$curParams,
            'date'   => date('d-m-Y'),
        ));

        self::$curList = $this->app->jbcache->get($cacheKey, 'currency', TRUE);
        if (empty(self::$curList)) {

            $elements = $this->app->jbcartposition->loadElements('currency');

            foreach ($elements as $element) {

                $code = $element->getCode();

                if ($code && $element->checkCurrency($code)) {

                    self::$curList[$code] = array(
                        'code'   => $code,
                        'name'   => $element->getCurrencyName(),
                        'value'  => $element->getValue($code),
                        'format' => $element->getFormat(),
                    );

                }
            }

            if (empty(self::$curList)) {
                $defaultCur = $this->getDefaultCur();

                self::$curList = array(
                    $defaultCur => array(
                        'value'  => 1,
                        'code'   => $defaultCur,
                        'name'   => JText::_('JBZOO_CART_CURRENCY_DEFAULT'),
                        'format' => $this->_defaultFormat,
                    )
                );
            }

            $this->app->jbcache->set($cacheKey, self::$curList, 'currency', TRUE);
        }

        $this->_isBuilded = TRUE;

        $this->app->jbdebug->mark('jbmoney::init::finish');

        return self::$curList;
    }

    /**
     * Clear price string
     *
     * @param $value
     *
     * @return float
     */
    public function clearValue($value)
    {
        $value = (string)$value;
        $value = JString::trim($value);
        $value = preg_replace('#[^0-9\,\.\-\+]#ius', '', $value);

        if (preg_match('#^([\+\-]{0,1})([0-9\.\,]*)$#ius', $value, $matches)) {
            $value = str_replace(',', '.', $matches[2]);

            return (float)($matches[1] . (float)$value);
        }

        return 0;
    }

    /**
     * Convert currency
     *
     * @param $from    string
     * @param $to      string
     * @param $value   float
     *
     * @return mixed
     */
    public function convert($from, $to, $value)
    {
        $this->init();

        $value = $this->clearValue($value);
        $from  = $this->clearCurrency($from);
        $to    = $this->clearCurrency($to);

        if (isset(self::$curList[$to]) && isset(self::$curList[$from])) {
            $normValue = $value / self::$curList[$from]['value'];
            $result    = $normValue * self::$curList[$to]['value'];

            return $result;
        }

        return NULL;
    }

    /**
     * Currency list
     *
     * @param bool $isShort
     *
     * @return array
     */
    public function getCurrencyList($isShort = FALSE)
    {
        $this->init();

        $result = array();

        if (empty(self::$curList)) {
            return $result;
        }

        foreach (self::$curList as $code => $currency) {

            if ($isShort) {
                $result[$code] = $code;
            } else {
                $result[$code] = $code . ' - ' . $currency['name'];
            }
        }

        return $result;
    }

    /**
     * convert number to money formated string
     *
     * @param $value
     * @param $code
     *
     * @return null|string
     */
    public function toFormat($value, $code = NULL)
    {
        $this->init();

        $code = $this->clearCurrency($code);

        if (empty($code)) {
            $code = $this->getDefaultCur();
        }

        if ($code == self::PERCENT) {
            return $this->_numberFormat($value, array(
                'symbol' => self::PERCENT,

            ));

        } else if (isset(self::$curList[$code])) {
            return $this->_numberFormat($value, self::$curList[$code]['format']);
        }

        return NULL;
    }


    /**
     * Check currency
     *
     * @param        $currency
     * @param string $default
     *
     * @return string
     */
    public function clearCurrency($currency, $default = NULL)
    {
        $this->init();

        $currency = trim(strtolower($currency));

        if ($currency == self::PERCENT) {
            return self::PERCENT;
        }

        if (isset(self::$curList[$currency])) {
            return $currency;
        }

        return NULL;
    }

    /**
     * Get base currency
     * @return string
     */
    public function getDefaultCur()
    {
        return JBCartElementCurrency::BASE_CURRENCY;
    }

    /**
     * Check if exists currency
     *
     * @param  $currency
     *
     * @return bool|string
     */
    public function checkCurrency($currency)
    {
        $currency = trim(strtolower($currency));
        if (array_key_exists($currency, self::$curList)) {
            return $currency;
        }

        return FALSE;
    }

    /**
     * @param       $value
     *
     * @return mixed
     */
    public function format($value)
    {
        $this->init();

        return $this->_numberFormat($value);
    }

    /**
     * @param null  $code
     * @param array $format
     *
     * @return null|string
     */
    public function getSymbol($code = NULL, $format = array())
    {
        $this->init();

        $code = $this->clearCurrency($code);

        if (empty($code)) {
            $code = $this->getDefaultCur();
        }
        $format = array_merge($this->_defaultFormat, (array)$format);

        if (!$code) {
            $code = $format['code'];
        }

        if ($code == self::PERCENT) {

            return self::PERCENT;

        } else if (isset(self::$curList[$code])) {

            $format = self::$curList[$code]['format'];

            return $format['symbol'];

        }

        return NULL;
    }

    /**
     * Convert value to money format from config
     *
     * @param string $value
     * @param array  $format
     *
     * @return string
     */
    protected function _numberFormat($value, $format = array())
    {
        $format = array_merge($this->_defaultFormat, (array)$format);
        $value  = $this->clearValue($value);
        $value  = !empty($value) ? $value : 0;

        $valueStr =
            number_format(abs($value), $format['num_decimals'], $format['decimal_sep'], $format['thousands_sep']);

        $moneyFormat = ($value >= 0) ? $format['format_positive'] : $format['format_negative'];

        return str_replace(array('%s', '%v'), array($format['symbol'], $valueStr), $moneyFormat);
    }

    /**
     * Calculate total value
     *
     * @param float  $value
     * @param string $baseCurrency
     * @param float  $addValue
     * @param string $currency
     *
     * @return float
     */
    public function calc($value, $baseCurrency, $addValue, $currency)
    {
        $value    = $this->clearValue($value);
        $addValue = $this->clearValue($addValue);

        $baseCurrency = $this->clearCurrency($baseCurrency);
        $currency     = $this->clearCurrency($currency, $baseCurrency);

        $sign = '';
        if ($addValue[0] == '-' || $addValue[0] == '+') {
            $sign = $addValue[0];
        }

        if ($currency == self::PERCENT) {
            $addValue = (float)($sign . abs($value * $addValue / 100));
        } else {
            $addValue = (float)($sign . abs($this->convert($currency, $baseCurrency, $addValue)));
        }

        if ($sign == '-' || $sign == '+') {
            $result = $value + $addValue;
        } else {
            $result = $addValue;
        }

        if ($result <= 0) {
            return 0;
        }

        return $result;
    }

    /**
     * Calculate with discount value
     *
     * @param $value
     * @param $baseCurrency
     * @param $addValue
     * @param $currency
     *
     * @return float
     */
    public function calcDiscount($value, $baseCurrency, $addValue, $currency)
    {
        $value        = $this->clearValue($value);
        $baseCurrency = $this->clearCurrency($baseCurrency);
        $addValue     = $this->clearValue($addValue);
        $currency     = $this->clearCurrency($currency, $baseCurrency);

        if ($currency == self::PERCENT) {
            $addValue = $value * $addValue / 100;
        } else {
            $addValue = $this->convert($currency, $baseCurrency, $addValue);
        }

        $result = $value + $addValue;
        if ($result <= 0) {
            return 0;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $this->init();

        return $this->app->data->create(self::$curList);
    }

}