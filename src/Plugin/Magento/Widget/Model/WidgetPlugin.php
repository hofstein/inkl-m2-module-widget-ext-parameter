<?php

declare(strict_types=1);

namespace Inkl\WidgetExtParameter\Plugin\Magento\Widget\Model;

use Inkl\WidgetExtParameter\Model\Service\Base64Service;
use Magento\Framework\DataObject;
use Magento\Widget\Model\Widget;

class WidgetPlugin
{
    private Base64Service $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    public function beforeGetWidgetDeclaration(Widget $subject, $type, $params = [], $asIs = true): array
    {
        $widgetConfig = $subject->getConfigAsObject($type);

        if (isset($params['conditions']) &&
            is_array($params['conditions']) &&
            isset($params['widget_list'])
        ) {
            foreach ($params['conditions'] as $conditionKey => $conditionData) {
                $itemData = explode('--', (string)$conditionKey, 2);

                $itemId = $itemData[0];
                $itemKey = '';
                if (isset($itemData[1])) {
                    $itemKey = '--' . $itemData[1];
                }

                if (!isset($params['widget_list']['product_list_item'][$itemId]['conditions'])) {
                    $params['widget_list']['product_list_item'][$itemId]['conditions'] = [];
                }

                $params['widget_list']['product_list_item'][$itemId]['conditions']['1' . $itemKey] = $conditionData;
            }

            unset($params['conditions']);
        }

        $newParams = [];
        foreach ($params as $name => $data) {
            if ($this->isBase64($name, $widgetConfig)) {
                $data = $this->base64Service->serialize($data);
            }

            $newParams[$name] = $data;
        }

        return [$type, $newParams, $asIs];
    }

    private function isBase64(string $parameterName, DataObject $widgetConfig): bool
    {
        if ($parameterName === 'widget_list') {
            return true;
        }

        foreach ($widgetConfig->getData('parameters') as $parameter) {
            if ($parameter->getData('key') === $parameterName &&
                ($parameter->getData('extra')['base64'] ?? 'false') === 'true'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetWidgetsArray(Widget $subject, $result, $filters = [])
    {
        $widgets = $subject->getWidgets($filters);
        foreach ($result as &$data) {
            $data['extra'] = $widgets[$data['code']]['extra'] ?? [];
        }

        return array_filter($result, function ($widgetData) {
            return ($widgetData['extra']['hidden'] ?? 'false') === 'false';
        });
    }
}
