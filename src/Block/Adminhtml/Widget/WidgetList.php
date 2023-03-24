<?php

declare(strict_types=1);

namespace Inkl\WidgetExtParameter\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button as WidgetButton;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\DataObject;
use Magento\Framework\Option\ArrayPool;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Widget\Model\Widget;

class WidgetList extends Template
{
    private Context $context;
    private Widget $widget;
    private Json $json;
    private ArrayPool $sourceModelPool;

    public function __construct(
        Context $context,
        Widget $widget,
        Json $json,
        ArrayPool $sourceModelPool,
        array $data = []
    ) {
        $this->context = $context;
        $this->widget = $widget;
        $this->json = $json;
        $this->sourceModelPool = $sourceModelPool;
        parent::__construct($context, $data);
    }

    public function prepareElementHtml(AbstractElement $element)
    {
        $config = $this->_getData('config');

        $group = $config['group'];
        $widgetClass = $config['widget_class'];
        $widgetListGroupValues = $this->getWidgetListGroupValues($group);

        $html = '';
        if (count($widgetListGroupValues) === 0) {
            $html .= $this->renderItem($element, $group, uniqid(), $widgetClass);
        } else {
            foreach ($widgetListGroupValues as $uniqueId => $values) {
                $html .= $this->renderItem($element, $group, $uniqueId, $widgetClass, $values);
            }
        }

        $html .= $this->renderAddButton($group, $widgetClass);

        $element->setData('after_element_html', $html);
    }

    public function renderItem(
        AbstractElement $rootElemenet,
        string $group,
        string $uniqueId,
        string $widgetClass,
        array $values = []
    ): string {
        $widgetConfig = $this->widget->getConfigAsObject($widgetClass);
        $parameters = $widgetConfig->getData('parameters');

        $fieldset = $this->createItemFieldset($rootElemenet, $group, $uniqueId);
        foreach ($parameters as $parameter) {
            $parameterKey = $parameter->getData('key');
            if ($parameterKey === 'widget_name') {
                continue;
            }

            $this->createItemField(
                $fieldset,
                $parameter,
                $group,
                $uniqueId,
                (string)($values[$parameterKey] ?? '')
            );
        }

        $this->addMoveButtons($fieldset);
        $this->addRemoveButton($fieldset);

        return $fieldset->getElementHtml();
    }

    public function renderAddButton(string $group, string $widgetClass): string
    {
        return $this->context->getLayout()
            ->createBlock(WidgetButton::class)
            ->setType('button')
            ->setLabel(__('Add'))
            ->setOnClick($this->getAddOnClickJs($group, $widgetClass))
            ->toHtml();
    }

    public function addRemoveButton(Fieldset $fieldset): void
    {
        $fieldset->addField(
            $fieldset->getHtmlId() . '__remove',
            'button',
            [
                'value' => __('Remove'),
                'onclick' => "jQuery(this).parents('fieldset:first').remove()"
            ]
        );
    }
    public function addMoveButtons(Fieldset $fieldset): void
    {
        $fieldset->addField(
            $fieldset->getHtmlId() . '__move_container',
            'note',
            [
                'text' => '
                <button onclick="
                var currentFieldset = jQuery(this).parents(\'fieldset:first\');
                var prevFieldset = currentFieldset.prev(\'fieldset\');
                if (prevFieldset.length > 0) {
                    currentFieldset.insertBefore(prevFieldset);
                }
                return false;
                ">' . __('Move up') . '</button>&nbsp;
                <button onclick="
                var currentFieldset = jQuery(this).parents(\'fieldset:first\');
                var nextFieldset = currentFieldset.next(\'fieldset\');
                if (nextFieldset.length > 0) {
                    currentFieldset.insertAfter(nextFieldset);
                }
                return false;
                ">' . __('Move down') . '</button>&nbsp;
                '
            ]
        );
    }

    public function createItemFieldset(AbstractElement $rootElement, string $group, string $uniqueId): Fieldset
    {
        return $rootElement->addFieldset(
            $this->getItemFieldsetHtmlId($group, $uniqueId),
            [
                'class' => 'fieldset-wide fieldset-widget-options',
                'style' => 'margin-bottom: 20px;'
            ]
        );
    }

    public function getItemFieldsetHtmlId(string $group, string $uniqueId): string
    {
        return 'options_fieldset__' . $group . '__' . $uniqueId;
    }

    public function createItemField(
        Fieldset $fieldset,
        DataObject $parameter,
        string $group,
        string $unqiueId,
        string $value
    ): AbstractElement {
        $fieldId = sprintf('parameters__%s__%s__%s', $group, $unqiueId, $parameter->getData('key'));
        $fieldName = sprintf('parameters[widget_list][%s][%s][%s]', $group, $unqiueId, $parameter->getData('key'));
        $fieldType = $parameter->getData('type');

        $data = [
            'name' => $fieldName,
            'label' => __($parameter->getData('label')),
            'required' => $parameter->getData('required'),
            'class' => 'widget-option',
            'note' => __($parameter->getData('description')),
            'value' => $value
        ];

        if ($values = $parameter->getData('values')) {
            $data['values'] = [];
            foreach ($values as $option) {
                $data['values'][] = ['label' => __($option['label']), 'value' => $option['value']];
            }
        } elseif ($sourceModel = $parameter->getData('source_model')) {
            $data['values'] = $this->sourceModelPool->get($sourceModel)->toOptionArray();
        }

        $fieldRenderer = null;
        if ($fieldType && $this->_isClassName($fieldType)) {
            $fieldRenderer = $this->getLayout()->createBlock($fieldType);
            $fieldType = 'text';
        }

        $field = $fieldset->addField($fieldId, $fieldType, $data);
        if ($fieldRenderer) {
            $fieldRenderer->setData('unique_id', $unqiueId);
            $field->setRenderer($fieldRenderer);
        }

        if ($helper = $parameter->getData('helper_block')) {
            $helperBlock = $this->context->getLayout()->createBlock(
                $helper->getType(),
                '',
                ['data' => $helper->getData()]
            );
            if ($helperBlock instanceof DataObject) {
                $helperBlock
                    ->setConfig($helper->getData())
                    ->setFieldsetId($fieldset->getId())
                    ->prepareElementHtml($field);
            }
        }

        return $field;
    }

    public function getWidgetListGroupValues(string $group): array
    {
        $optionsBlock = $this->context->getLayout()->getBlock('wysiwyg_widget.options');

        $widgetValues =  $optionsBlock->getData('widget_values');
        $widgetListValues = $widgetValues['widget_list'] ?? [];

        return $widgetListValues[$group] ?? [];
    }

    public function getAddOnClickJs(string $group, string $widgetClass): string
    {
        $url = $this->context->getUrlBuilder()->getUrl('adminhtml/widget_widgetList/add');
        $widgetClass = str_replace('\\', '\\\\', $widgetClass);

        return <<<PHP_EOF
(function ($) {
    $.post('{$url}', { 
        'form_key': FORM_KEY,
        'group' : '{$group}',
        'widget_class' : '{$widgetClass}'
    }, function(data) {
      $('fieldset[id^=options_fieldset__{$group}]:last').after(data);
    });
})(jQuery);
PHP_EOF;
    }

    private function _isClassName($fieldType)
    {
        return $fieldType && preg_match('/[A-Z]/', $fieldType) > 0;
    }
}
