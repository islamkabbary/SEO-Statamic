<?php

declare(strict_types=1);

return [
    'fallback' => [
        'pass' => 'Looks good.',
        'warn' => 'Could be improved.',
        'fail' => 'Needs attention.',
    ],

    'focus_keyword_presence' => [
        'pass' => 'A focus keyword is set.',
        'warn' => 'Your focus keyword is quite long — try something more focused.',
        'fail' => 'Set a focus keyword so this page can be optimised.',
    ],
    'title_presence' => [
        'pass' => 'The SEO title is set.',
        'warn' => 'The SEO title length (:length) is unusual — aim for 50–60 characters.',
        'fail' => 'Add an SEO title.',
    ],
    'title_length' => [
        'pass' => 'SEO title length is ideal (:length characters).',
        'warn' => 'SEO title is :length characters — 50–60 is ideal.',
        'fail' => 'SEO title is :length characters — too far from the ideal 50–60.',
    ],
    'title_keyword' => [
        'pass' => 'The focus keyword appears in the SEO title.',
        'fail' => 'Add the focus keyword to the SEO title.',
    ],
    'description_presence' => [
        'pass' => 'The meta description is set.',
        'warn' => 'The meta description is very short (:length characters).',
        'fail' => 'Add a meta description.',
    ],
    'description_length' => [
        'pass' => 'Meta description length is ideal (:length characters).',
        'warn' => 'Meta description is :length characters — aim for 120–158.',
        'fail' => 'Meta description is :length characters — aim for 120–158.',
    ],
    'description_keyword' => [
        'pass' => 'The focus keyword appears in the meta description.',
        'fail' => 'Add the focus keyword to the meta description.',
    ],
    'url_presence' => [
        'pass' => 'The URL slug is set.',
        'warn' => 'The URL slug uses underscores or double hyphens — prefer single hyphens.',
        'fail' => 'Add a URL slug.',
    ],
    'url_keyword' => [
        'pass' => 'The focus keyword appears in the URL slug.',
        'fail' => 'Add the focus keyword to the URL slug.',
    ],
    'content_presence' => [
        'pass' => 'There is enough content to analyse (:words words).',
        'warn' => 'The content is thin (:words words) — add more.',
        'fail' => 'Add body content (only :words words found).',
    ],
    'content_length' => [
        'pass' => 'Content length is good (:words words).',
        'warn' => 'Content is a little short (:words words) — aim for :min+.',
        'fail' => 'Content is too short (:words words) — aim for :min+.',
    ],
    'keyword_in_first_paragraph' => [
        'pass' => 'The focus keyword appears in the first paragraph.',
        'warn' => 'The focus keyword appears late — move it into the first paragraph.',
        'fail' => 'The focus keyword is missing from the opening content.',
    ],
    'keyword_density' => [
        'pass' => 'Keyword density is healthy (:density%).',
        'warn' => 'Keyword density is :density% — keep it between 0.5% and 3%.',
        'fail' => 'Keyword density is :density% — too high or zero; aim for 0.5–3%.',
    ],
    'h1_presence' => [
        'pass' => 'The page has a single H1.',
        'warn' => 'No H1 heading found — add one.',
        'fail' => 'There are :count H1 headings — use exactly one.',
    ],
    'h1_keyword' => [
        'pass' => 'The focus keyword appears in the H1.',
        'fail' => 'Add the focus keyword to the H1 heading.',
    ],
    'image_alt_text' => [
        'pass' => 'All images have alt text (:with_alt/:total).',
        'warn' => 'Some images are missing alt text (:with_alt/:total).',
        'fail' => 'Most images are missing alt text (:with_alt/:total).',
    ],
    'internal_links' => [
        'pass' => 'The content has internal links (:count).',
        'fail' => 'Add at least one internal link.',
    ],
    'readability' => [
        'pass' => 'Readability looks good (estimate: :score).',
        'warn' => 'Readability could be easier (estimate: :score).',
        'fail' => 'The content is hard to read (estimate: :score).',
    ],
];