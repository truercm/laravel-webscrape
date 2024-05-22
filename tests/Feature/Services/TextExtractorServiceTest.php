<?php

use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Services\TextExtractorService;

it('extracts text from input field', function () {
    $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
</body>
</html>
HTML;

    $crawler = new Crawler($html);

    $node = $crawler->filterXPath('//body');
    $fieldName = 'myField';

    $textExtractor = new TextExtractorService();
    $extractedText = $textExtractor->getTextInput($node, $fieldName);

    $this->assertEquals('This is some text', $extractedText);
});

it('will get checked radio/checkbox options', function () {
    $html = <<<HTML
<html>
<body>
<div class="inputDiv"><input type="radio" name="radioField" value="option1" checked> Option 1</div>
<div class="inputDiv"><input type="radio" name="radioField" value="option2"> Option 2</div>

</body>
</html>
HTML;

    $crawler = new Crawler($html);
    $node = $crawler->filterXPath('//body');
    $containerClass = 'inputDiv';

    $textExtractor = new TextExtractorService();
    $extractedOptions = $textExtractor->getRadioOptions($node, $containerClass);

    $this->assertEquals([
        'Option 1' => true,
        'Option 2' => false,
    ], $extractedOptions);
});
