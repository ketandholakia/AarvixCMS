<?php

namespace Tests\Unit\AI;

use App\AI\Support\WriterPreview;
use PHPUnit\Framework\TestCase;

class WriterPreviewTest extends TestCase
{
    public function test_malformed_response_falls_back_to_safe_paragraph_blocks(): void
    {
        $preview = WriterPreview::fromResponse([
            'mode' => 'insert',
            'summary' => ['not' => 'a string'],
            'plain_text' => null,
            'blocks' => [
                'invalid',
                ['type' => 'header', 'data' => ['text' => '']],
            ],
        ], 'rewrite', [
            'plain_text' => 'Fallback content.',
        ], 'selection');

        $this->assertSame('insert', $preview['mode']);
        $this->assertSame('Fallback content.', $preview['plain_text']);
        $this->assertSame('Fallback content.', $preview['blocks'][0]['data']['text']);
        $this->assertNull($preview['seo']);
    }

    public function test_malformed_seo_payload_is_sanitized(): void
    {
        $preview = WriterPreview::fromResponse([
            'seo' => [
                'meta_title' => '<script>bad</script>Title',
                'meta_description' => "Line one\nLine two",
                'slug' => 'seo-ready-title',
                'keywords' => ['alpha', '', '<b>beta</b>'],
                'warnings' => ['<em>warning</em>'],
                'lengths' => ['meta_title' => '40', 'meta_description' => '155', 'keywords' => '2'],
            ],
        ], 'seo', [
            'plain_text' => 'Document text',
        ]);

        $this->assertSame('badTitle', $preview['seo']['meta_title']);
        $this->assertSame("Line one\nLine two", $preview['seo']['meta_description']);
        $this->assertSame(['alpha', 'beta'], $preview['seo']['keywords']);
        $this->assertSame(['warning'], $preview['seo']['warnings']);
        $this->assertSame(40, $preview['seo']['lengths']['meta_title']);
    }
}
