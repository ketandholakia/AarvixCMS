<?php

namespace Tests\Unit\AI;

use App\AI\Support\VectorStores\InMemoryVectorStore;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InMemoryVectorStoreTest extends TestCase
{
    #[Test]
    public function it_ranks_similar_vectors_and_filters_metadata(): void
    {
        $store = new InMemoryVectorStore();

        $store->upsert('content', [
            [
                'id' => 'docs-1',
                'vector' => [1.0, 0.0, 0.0],
                'metadata' => ['category' => 'docs', 'visibility' => 'public'],
                'text' => 'Documentation item',
            ],
            [
                'id' => 'support-1',
                'vector' => [0.0, 1.0, 0.0],
                'metadata' => ['category' => 'support', 'visibility' => 'public'],
                'text' => 'Support item',
            ],
        ]);

        $results = $store->search('content', [0.95, 0.05, 0.0], ['category' => 'docs'], 5);

        $this->assertCount(1, $results);
        $this->assertSame('docs-1', $results[0]['id']);
        $this->assertSame('Documentation item', $results[0]['text']);
        $this->assertGreaterThan(0.9, $results[0]['score']);
    }

    #[Test]
    public function it_deletes_collections_and_records(): void
    {
        $store = new InMemoryVectorStore();

        $store->upsert('content', [
            [
                'id' => 'docs-1',
                'vector' => [1.0, 0.0, 0.0],
            ],
        ]);

        $this->assertCount(1, $store->search('content', [1.0, 0.0, 0.0]));

        $store->delete('content', ['docs-1']);

        $this->assertSame([], $store->search('content', [1.0, 0.0, 0.0]));
    }
}
