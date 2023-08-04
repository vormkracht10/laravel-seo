<?php

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Vormkracht10\Seo\Checks\Meta\FocusKeywordInTitleCheck;

it('can perform the focus keyword in title check on a page with the focus keyword in the title', function () {
    $check = new FocusKeywordInTitleCheck();
    $crawler = new Crawler();

    Http::fake([
        'vormkracht10.nl' => Http::response('<html><head><title>vormkracht10</title><meta name="keywords" content="vormkracht10, seo, laravel, package"></head><body></body></html>', 200),
    ]);

    $crawler->addHtmlContent(Http::get('vormkracht10.nl')->body());

    $this->assertTrue($check->check(Http::get('vormkracht10.nl'), $crawler));
});

it('can perform the focus keyword in title check on a page without the focus keyword in the title', function () {
    $check = new FocusKeywordInTitleCheck();
    $crawler = new Crawler();

    Http::fake([
        'vormkracht10.nl' => Http::response('<html><head><title>vormkracht10</title><meta name="keywords" content="seo, laravel, package"></head><body></body></html>', 200),
    ]);

    $crawler->addHtmlContent(Http::get('vormkracht10.nl')->body());

    $this->assertFalse($check->check(Http::get('vormkracht10.nl'), $crawler));
});

it('can perform the focus keyword in title check on a page without keywords', function () {
    $check = new FocusKeywordInTitleCheck();
    $crawler = new Crawler();

    Http::fake([
        'vormkracht10.nl' => Http::response('<html><head></head><body></body></html>', 200),
    ]);

    $crawler->addHtmlContent(Http::get('vormkracht10.nl')->body());

    $this->assertFalse($check->check(Http::get('vormkracht10.nl'), $crawler));
});
