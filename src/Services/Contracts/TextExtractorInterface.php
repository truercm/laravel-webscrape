<?php

namespace TrueRcm\LaravelWebscrape\Services\Contracts;

use Symfony\Component\DomCrawler\Crawler;

interface TextExtractorInterface
{
    /**
     * Extracts the text content of an input or textarea field.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $node the DOM node to extract text from
     * @param string $fieldName the name of the field to extract
     * @return string the extracted text content
     */
    public function getText(Crawler $node, string $fieldName): string;

    /**
     * Extracts an array containing the text and checked state of all radio button options with the specified field name.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $node the DOM node to extract options from
     * @param string $containerClass the name of the field to extract
     * @return array an associative array where keys are option text and values are boolean indicating checked state
     */
    public function getRadioOptions(Crawler $node, string $containerClass): array;

    /**
     * Extracts the text content of the selected option within a select element.
     *
     * @param \Symfony\Component\DomCrawler\Crawler $node the DOM node to extract the selected value from
     * @param string $fieldName the name of the select field to extract
     * @return array|string the text content of the selected option, or empty string if none is selected
     */
    public function getSelect(Crawler $node, string $fieldName): array|string;
}
