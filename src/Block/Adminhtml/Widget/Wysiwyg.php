<?php

declare(strict_types=1);

namespace Inkl\WidgetExtParameter\Block\Adminhtml\Widget;

Class Wysiwyg extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $wysiwygConfig;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $factoryElement;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $factoryElement
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        $data = []
    ) {
        $this->factoryElement = $factoryElement;
        $this->wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }

    /**
     * Prepare chooser element HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element Form Element
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function prepareElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $wysiwygConfig = $this->wysiwygConfig->getConfig([
            'add_widgets' => false,
            'add_variables' => true,
        ]);
        $wysiwygConfig->setData('add_images', false);

        $editor = $this->factoryElement->create('editor', ['data' => $element->getData()])
            ->setLabel('')
            ->setForm($element->getForm())
            ->setWysiwyg(true)
            ->setForceLoad(true)
            ->setConfig($wysiwygConfig);

        if ($element->getRequired()) {
            $editor->addClass('required-entry');
        }

        $element->setData('after_element_html', $editor->getElementHtml() . '<style>.textareawidget-option { height: 400px; }</style>');
        $element->setValue(''); // Hides the additional label that gets added.

        /*
        echo '<pre>';
        print_r($wysiwygConfig->getData()); exit;
        */

        return $element;
    }
}

