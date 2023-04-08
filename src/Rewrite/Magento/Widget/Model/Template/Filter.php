<?php

declare(strict_types=1);

namespace Inkl\WidgetExtParameter\Rewrite\Magento\Widget\Model\Template;

use Inkl\WidgetExtParameter\Model\Service\Base64Service;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Email\Model\Template\Css;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Css\PreProcessor\Adapter\CssInliner;
use Magento\Framework\Escaper;
use Magento\Framework\Filesystem;
use Magento\Framework\Filter\VariableResolverInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Variable\Model\Source\Variables;
use Magento\Variable\Model\VariableFactory;
use Magento\Widget\Model\Widget;
use Psr\Log\LoggerInterface;

class Filter extends \Magento\Widget\Model\Template\Filter
{
    private bool $recursiveCheck = false;

    public function __construct(
        private readonly Base64Service $base64Service,
        private readonly FilterProvider $filterProvider,
        StringUtils $string,
        LoggerInterface $logger,
        Escaper $escaper,
        Repository $assetRepo,
        ScopeConfigInterface $scopeConfig,
        VariableFactory $coreVariableFactory,
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        LayoutFactory $layoutFactory,
        State $appState,
        UrlInterface $urlModel,
        Variables $configVariables,
        VariableResolverInterface $variableResolver,
        Css\Processor $cssProcessor,
        Filesystem $pubDirectory,
        CssInliner $cssInliner,
        \Magento\Widget\Model\ResourceModel\Widget $widgetResource,
        Widget $widget,
        $variables = [],
        array $directiveProcessors = []
    ) {
        parent::__construct(
            $string,
            $logger,
            $escaper,
            $assetRepo,
            $scopeConfig,
            $coreVariableFactory,
            $storeManager,
            $layout,
            $layoutFactory,
            $appState,
            $urlModel,
            $configVariables,
            $variableResolver,
            $cssProcessor,
            $pubDirectory,
            $cssInliner,
            $widgetResource,
            $widget,
            $variables,
            $directiveProcessors
        );
    }


    protected function getParameters($value)
    {
        $parameters = parent::getParameters($value);
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $this->base64Service->unserialize($value);
        }

        if (!$this->recursiveCheck) {
            $this->recursiveCheck = true;
            $parameters = $this->arrayMapRecursive([$this->filterProvider->getBlockFilter(), 'filter'], $parameters);
            $this->recursiveCheck = false;
        }

        return $parameters;
    }

    private function arrayMapRecursive(callable $callback, array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->arrayMapRecursive($callback, $value);
            } else {
                $array[$key] = $callback((string)$value);
            }
        }
        return $array;
    }
}
