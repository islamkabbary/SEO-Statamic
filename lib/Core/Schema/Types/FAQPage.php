<?php

declare(strict_types=1);

namespace SilaSeo\Core\Schema\Types;

/**
 * schema.org/FAQPage — a list of Question/Answer pairs eligible for the FAQ
 * rich result.
 */
final class FAQPage extends AbstractSchemaType
{
    public function type(): string
    {
        return 'FAQPage';
    }

    public function add(string $question, string $answer): static
    {
        /** @var list<array<string,mixed>> $items */
        $items = $this->properties['mainEntity'] ?? [];

        $items[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];

        return $this->set('mainEntity', $items);
    }

    /**
     * @param array<string,string> $pairs question => answer, in order.
     */
    public function fromPairs(array $pairs): static
    {
        foreach ($pairs as $question => $answer) {
            $this->add($question, $answer);
        }

        return $this;
    }
}