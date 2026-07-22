<?php

namespace Tests\Feature\Services;

use App\Services\BlockParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockParserTest extends TestCase
{
    use RefreshDatabase;

    protected BlockParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BlockParser();
    }

    public function test_it_allows_legitimate_inline_formatting()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => 'This is <b>bold</b>, <i>italic</i>, and a <a href="https://example.com">link</a>.'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringContainsString('<b>bold</b>', $html);
        $this->assertStringContainsString('<i>italic</i>', $html);
        $this->assertStringContainsString('<a href="https://example.com"', $html);
    }

    public function test_it_strips_script_tags_from_text_fields()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => 'Normal text <script>alert("xss")</script>'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert("xss")', $html);
    }

    public function test_it_strips_onerror_handlers_from_raw_blocks()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'raw',
                    'data' => [
                        'html' => '<img src="x" onerror="alert(\'xss\')" />'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('alert', $html);
    }

    public function test_it_rejects_javascript_uris_in_links()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'paragraph',
                    'data' => [
                        'text' => '<a href="javascript:alert(\'xss\')">Click me</a>'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringNotContainsString('javascript:', $html);
        // HTMLPurifier usually strips the href or converts it
    }

    public function test_it_rejects_javascript_uris_in_image_blocks()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'image',
                    'data' => [
                        'file' => ['url' => 'javascript:alert(1)'],
                        'caption' => 'Bad image'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString("src=''", $html);
    }

    public function test_it_escapes_image_captions_to_prevent_attribute_breakout()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'image',
                    'data' => [
                        'file' => ['url' => '/test.png'],
                        'caption' => '"><script>alert(1)</script>'
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;&gt;', $html);
    }

    public function test_it_clamps_header_levels()
    {
        $json = json_encode([
            'blocks' => [
                [
                    'type' => 'header',
                    'data' => [
                        'text' => 'Header',
                        'level' => 9 // Invalid level
                    ]
                ]
            ]
        ]);

        $html = $this->parser->parse($json);

        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringNotContainsString('<h9>', $html);
    }

    public function test_it_purifies_legacy_raw_html()
    {
        $legacyHtml = '<div><script>alert("xss")</script><p>Legacy content</p></div>';

        $html = $this->parser->parse($legacyHtml);

        $this->assertStringContainsString('<p>Legacy content</p>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert', $html);
    }
}
