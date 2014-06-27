<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Mtf\Client\Driver\Selenium\Element;

use Mtf\Client\Element;
use Mtf\Client\Element\Locator;

/**
 * Class MultiselectgrouplistElement
 * Typified element class for multiselect with group
 */
class MultiselectgrouplistElement extends MultiselectElement
{
    /**
     * Indent length
     */
    const INDENT_LENGTH = 4;

    /**
     * Locator for search optgroup by label
     *
     * @var string
     */
    protected $optgroupByLabel = './/optgroup[@label="%s"]';

    /**
     * Locator for search optgroup by number
     *
     * @var string
     */
    protected $optgroupByNumber = './/optgroup[%d]';

    /**
     * Locator for search next optgroup
     *
     * @var string
     */
    protected $nextOptgroup = './/following-sibling::optgroup[%d]';

    /**
     * Locator for search child optgroup
     *
     * @var string
     */
    protected $childOptgroup = ".//following-sibling::optgroup[%d][@label='%s']";

    /**
     * Locator for search parent optgroup
     *
     * @var string
     */
    protected $parentOptgroup = 'optgroup[option[text()="%s"]]';

    /**
     * Locator for search preceding sibling optgroup
     *
     * @var string
     */
    protected $precedingOptgroup = '/preceding-sibling::optgroup[1][substring(@label,1,%d)="%s"]';

    /**
     * Locator for option
     *
     * @var string
     */
    protected $option = './/option[text()="%s"]';

    /**
     * Locator search for option by number
     *
     * @var string
     */
    protected $optionByNumber = './/optgroup[%d]/option[%d]';

    /**
     * Indent, four symbols non breaking space
     *
     * @var string
     */
    protected $indent = "\xC2\xA0\xC2\xA0\xC2\xA0\xC2\xA0";

    /**
     * Trim symbols
     *
     * @var string
     */
    protected $trim = "\xC2\xA0 ";

    /**
     * Set values
     *
     * @param array|string $values
     * @return void
     */
    public function setValue($values)
    {
        $this->clearSelectedOptions();
        $values = is_array($values) ? $values : [$values];
        foreach ($values as $value) {
            $this->selectOption($value);
        }
    }

    /**
     * Select option
     *
     * @param string $option
     * @return void
     * @throws \Exception
     */
    protected function selectOption($option)
    {
        $isOptgroup = false;
        $optgroupIndent = '';
        $values = explode('/', $option);
        $context = $this;

        foreach ($values as $value) {
            $optionIndent = $isOptgroup ? $this->indent : '';
            $optionElement = $context->find(sprintf($this->option, $optionIndent . $value), Locator::SELECTOR_XPATH);
            if ($optionElement->isVisible()) {
                if (!$optionElement->isSelected()) {
                    $optionElement->click();
                }
                return;
            }

            $value = $optgroupIndent . $value;
            $optgroupIndent .= $this->indent;
            if ($isOptgroup) {
                $context = $this->getChildOptgroup($value, $context);
            } else {
                $context = $this->getOptgroup($value, $context);
                $isOptgroup = true;
            }
        }
        throw new \Exception("Can't find option \"{$option}\".");
    }

    /**
     * Get optgroup
     *
     * @param string $value
     * @param Element $context
     * @return Element
     * @throws \Exception
     */
    protected function getOptgroup($value, Element $context)
    {
        $optgroup = $context->find(sprintf($this->optgroupByLabel, $value), Locator::SELECTOR_XPATH);
        if (!$optgroup->isVisible()) {
            throw new \Exception("Can't find group \"{$value}\".");
        }
        return $optgroup;
    }

    /**
     * Get child optgroup
     *
     * @param string $value
     * @param Element $context
     * @return Element
     * @throws \Exception
     */
    protected function getChildOptgroup($value, Element $context)
    {
        $childOptgroup = null;
        $count = 1;
        while (!$childOptgroup) {
            $optgroup = $context->find(sprintf($this->nextOptgroup, $count), Locator::SELECTOR_XPATH);
            if (!$optgroup->isVisible()) {
                throw new \Exception("Can't find child group \"{$value}\"");
            }

            $childOptgroup = $context->find(
                sprintf($this->childOptgroup, $count, $value),
                Locator::SELECTOR_XPATH
            );
            if (!$childOptgroup->isVisible()) {
                $childOptgroup = null;
            }
            ++$count;
        }

        return $childOptgroup;
    }

    /**
     * Get value
     *
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getValue()
    {
        $values = [];
        $indentOption = str_repeat(' ', self::INDENT_LENGTH);

        foreach ($this->getSelectedOptions() as $option) {
            $value = [];

            /** @var Element $option */
            $optionText = $option->getText();
            $optionValue = trim($optionText, $this->trim);
            $value[] = $optionValue;
            if (0 !== strpos($optionText, $indentOption)) {
                $values[] = implode('/', $value);
                continue;
            }

            $pathOptgroup = sprintf($this->parentOptgroup, $this->indent . $optionValue);
            $optgroup = $this->_getWrappedElement()->byXPath($pathOptgroup);
            $optgroupText = $optgroup->attribute('label');
            $optgroupValue = trim($optgroupText, $this->trim);
            $amountIndent = strlen($optgroupText) - strlen($optgroupValue);
            $amountIndent = $amountIndent ? ($amountIndent / strlen($this->indent)) : 0;
            $value[] = $optgroupValue;
            if (0 == $amountIndent) {
                $values[] = implode('/', $value);
                continue;
            }

            --$amountIndent;
            $indent = $amountIndent ? str_repeat($this->indent, $amountIndent) : '';
            $pathOptgroup .= sprintf($this->precedingOptgroup, $amountIndent * self::INDENT_LENGTH, $indent);
            while (0 <= $amountIndent && $this->find($pathOptgroup, Locator::SELECTOR_XPATH)->isVisible()) {
                $optgroup = $this->_getWrappedElement()->byXPath($pathOptgroup);
                $optgroupText = $optgroup->attribute('label');
                $optgroupValue = trim($optgroupText, $this->trim);
                $value[] = $optgroupValue;

                --$amountIndent;
                $indent = (0 < $amountIndent) ? str_repeat($this->indent, $amountIndent) : '';
                $pathOptgroup .= sprintf($this->precedingOptgroup, $amountIndent * self::INDENT_LENGTH, $indent);
            }

            $values[] = implode('/', array_reverse($value));
        }

        return $values;
    }

    /**
     * Get options
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [];

        $countOptgroup = 1;
        $optgroup = $this->find(sprintf($this->optgroupByNumber, $countOptgroup), Locator::SELECTOR_XPATH);
        while ($optgroup->isVisible()) {
            $countOption = 1;
            $option = $this->find(
                sprintf($this->optionByNumber, $countOptgroup, $countOption),
                Locator::SELECTOR_XPATH
            );
            while ($option->isVisible()) {
                $options[] = $option;
                ++$countOption;
                $option = $this->find(
                    sprintf($this->optionByNumber, $countOptgroup, $countOption),
                    Locator::SELECTOR_XPATH
                );
            }

            ++$countOptgroup;
            $optgroup = $this->find(sprintf($this->optgroupByNumber, $countOptgroup), Locator::SELECTOR_XPATH);
        }
        return $options;
    }

    /**
     * Get selected options
     *
     * @return array
     */
    protected function getSelectedOptions()
    {
        $options = [];
        foreach ($this->getOptions() as $option) {
            /** Element $option */
            if ($option->isSelected()) {
                $options[] = $option;
            }
        }
        return $options;
    }
}