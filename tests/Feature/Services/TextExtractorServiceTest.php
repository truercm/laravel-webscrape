<?php

use Symfony\Component\DomCrawler\Crawler;
use TrueRcm\LaravelWebscrape\Services\TextExtractorService;

it('filters elements with xpath', function () {
        $html = <<<HTML
<html>
<body>
<input type="text" name="myField" value="This is some text">
<input type="text" name="myField2" value="This is some text">
<input type="text" name="myField3" value="This is some text">
</body>
</html>
HTML;

        $crawler = new Crawler($html);

        $node = $crawler->filterXPath('//body');

        $textExtractor = new TextExtractorService();
        $elements = $textExtractor->filterXPath($node,'input', 'name', 'myField');

        $this->assertInstanceOf(Crawler::class, $elements);
        $this->assertCount(1, $elements);

        $elements = $textExtractor->filterXPath($node,'input', 'name', 'myField', false);

        $this->assertInstanceOf(Crawler::class, $elements);
        $this->assertCount(3, $elements);
});

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

    $this->assertEquals(null, $extractedText);
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
        'Option 1 Option 2' => true,
    ], $extractedOptions);
});
