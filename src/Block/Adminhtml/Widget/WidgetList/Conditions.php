<?php

declare(strict_types=1);

namespace Inkl\WidgetExtParameter\Block\Adminhtml\Widget\WidgetList;

class Conditions extends \Magento\CatalogWidget\Block\Product\Widget\Conditions
{
    public function getInputHtml()
    {
        $htmlId = $this->getHtmlId();
        $idData = explode('__', $htmlId);

        $id = end($idData);

        $this->rule->getConditions()->setId($id);

        $widgetParameters = [];

        $widget = $this->registry->registry('current_widget_instance');
        if ($widget) {
            $widgetParameters = $widget->getWidgetParameters();
        } elseif ($widgetOptions = $this->getLayout()->getBlock('wysiwyg_widget.options')) {
            $widgetParameters = $widgetOptions->getWidgetValues();
        }

        $widgetParameters = $widgetParameters['widget_list']['product_list_item'][$id] ?? [];
        if (isset($widgetParameters['conditions'])) {
            $this->rule->loadPost($widgetParameters);
            $this->rule->getConditions()->setJsFormObject($htmlId);
        }

        return parent::getInputHtml();
    }


}
