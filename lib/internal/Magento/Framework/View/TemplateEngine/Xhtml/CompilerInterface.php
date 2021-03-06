<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\TemplateEngine\Xhtml;

use Magento\Framework\Object;

/**
 * Interface CompilerInterface
 */
interface CompilerInterface
{
    const PATTERN_TAG = '|@|';

    /**
     * The compilation of the template and filling in the data
     *
     * @param \DOMNode $node
     * @param Object $dataObject
     * @param Object $context
     * @return void
     */
    public function compile(\DOMNode $node, Object $dataObject, Object $context);

    /**
     * Run postprocessing contents
     *
     * @param string $content
     * @return string
     */
    public function postprocessing($content);

    /**
     * Set postprocessing data
     *
     * @param string $key
     * @param string $content
     * @return void
     */
    public function setPostprocessingData($key, $content);
}
