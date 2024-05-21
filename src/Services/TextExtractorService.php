<?php

namespace TrueRcm\LaravelWebscrape\Services;

use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Services\Contracts\TextExtractorInterface;

class TextExtractorService implements TextExtractorInterface
{
    public function getTextInput(Crawler $node, string $fieldName): string
    {
        // Build the XPath expression
        $xpath = sprintf('//input[@name="%s"] | //textarea[@name="%s"]', $fieldName, $fieldName);
        // Filter based on the XPath expression
        $filteredNode = $node->filterXPath($xpath);
        // Check if any node was found
        if (0 === $filteredNode->count()) {
            return ''; // Return empty string if no node found
        }
        // Extract the text content
        return $filteredNode->attr('value');
    }

    public function getText(Crawler $node, string $fieldName): string
    {
        // Build the XPath expression
        $xpath = sprintf('//input[@name="%s"] | //textarea[@name="%s"]', $fieldName, $fieldName);
        // Filter based on the XPath expression
        $filteredNode = $node->filterXPath($xpath);
        // Check if any node was found
        if (0 === $filteredNode->count()) {
            return ''; // Return empty string if no node found
        }
        // Extract the text content
        return $filteredNode->text('');
    }

    public function getRadioOptions(Crawler $node, string $containerClass): array
    {
        $values = [];
        $options = $node->filterXPath(sprintf('//div[@class="%s"]', $containerClass));

        $options->each(function ($node, $i) use (&$values) {
            $isChecked = $node->filterXPath('//input[@checked="checked"]')->count() > 0;
            $values[$node->text()] = $isChecked;
        });

        return $values;
    }

    public function getSelect(Crawler $node, string $fieldName): string
    {
        $selectedOption = $node->filterXPath(sprintf('//select[@name="%s"]//option[@selected="selected"]', $fieldName));

        if (0 === $selectedOption->count()) {
            return ''; // Return empty string if no selected option found
        }

        return $selectedOption->text('');
    }
}
