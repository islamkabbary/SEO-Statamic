<?php

declare(strict_types=1);

namespace SilaSeo\Core\Tests\Schema;

use PHPUnit\Framework\TestCase;
use SilaSeo\Core\Schema\Types\Course;
use SilaSeo\Core\Schema\Types\Event;
use SilaSeo\Core\Schema\Types\FAQPage;
use SilaSeo\Core\Schema\Types\HowTo;
use SilaSeo\Core\Schema\Types\Product;
use SilaSeo\Core\Schema\Types\VideoObject;
use SilaSeo\Core\Schema\Validator;

final class SchemaTypesTest extends TestCase
{
    public function testProductWithOfferAndRating(): void
    {
        $node = (new Product('Smart Helmet'))
            ->description('A safe helmet')
            ->brand('Sila')
            ->offer(199.99, 'SAR', 'InStock', 'https://example.com/p')
            ->aggregateRating(4.6, 120)
            ->toArray();

        self::assertSame('Product', $node['@type']);
        self::assertSame('Offer', $node['offers']['@type']);
        self::assertSame('199.99', $node['offers']['price']);
        self::assertSame('https://schema.org/InStock', $node['offers']['availability']);
        self::assertSame('4.6', $node['aggregateRating']['ratingValue']);
        self::assertSame(120, $node['aggregateRating']['reviewCount']);
        self::assertSame('Brand', $node['brand']['@type']);
    }

    public function testFaqPageBuildsQuestions(): void
    {
        $node = (new FAQPage())
            ->add('What is SilaSEO?', 'An in-house SEO toolkit.')
            ->add('Is it free?', 'Yes.')
            ->toArray();

        self::assertSame('FAQPage', $node['@type']);
        self::assertCount(2, $node['mainEntity']);
        self::assertSame('Question', $node['mainEntity'][0]['@type']);
        self::assertSame('An in-house SEO toolkit.', $node['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testCourseProvider(): void
    {
        $node = (new Course('Intro to SEO'))->provider('Sila Academy', 'https://example.com')->toArray();

        self::assertSame('Course', $node['@type']);
        self::assertSame('Sila Academy', $node['provider']['name']);
    }

    public function testHowToSteps(): void
    {
        $node = (new HowTo('Brew coffee'))->step('Grind', 'Grind the beans')->step('Pour', 'Pour water')->toArray();

        self::assertCount(2, $node['step']);
        self::assertSame('HowToStep', $node['step'][0]['@type']);
    }

    public function testVideoAndEventValidation(): void
    {
        $validator = new Validator();

        $video = (new VideoObject('Demo'))->thumbnailUrl('https://example.com/t.jpg')->uploadDate('2026-01-01');
        self::assertTrue($validator->isValid($video));

        // Missing startDate fails Event validation.
        self::assertFalse($validator->isValid(new Event('Launch')));
        self::assertTrue($validator->isValid((new Event('Launch'))->startDate('2026-02-01')));
    }
}