<?php

namespace TrueRcm\LaravelWebscrape\Services;

use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Services\Contracts\TextExtractorInterface;

class TextExtractorService implements TextExtractorInterface
{
    public function filterXPath(Crawler $node, string $htmlTag, string $fieldName, string $fieldValue, bool $exactMatch=true): Crawler
    {
        $xpath = sprintf('//%s[contains(@%s, "%s")]', $htmlTag, $fieldName, $fieldValue);

        if($exactMatch){
            $xpath = sprintf('//%s[@%s="%s"]', $htmlTag, $fieldName, $fieldValue);
        }

        return $node->filterXPath($xpath);
    }

    public function getTextInput(Crawler $node): ?string
    {
        // Check if any node was found
        if (0 === $node->count()) {
            return null;
        }
        // Extract the text content
        return $node->attr('value');
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

        $node->each(function ($node, $i) use (&$values) {
            $isChecked = $node->filterXPath('//input[@checked="checked"]')->count() > 0;
            $values[$node->text()] = $isChecked;
        });

        return $values;
    }

    public function getSelect(Crawler $node, string $fieldName, $multiSelect=false): array|string
    {
        $selectedOptions = $node->filterXPath(sprintf('//select[@name="%s"]//option[@selected="selected"]', $fieldName));

        if($multiSelect){
            return $selectedOptions->extract(['_text']);
        }

        return $selectedOptions->text('');
    }
}
