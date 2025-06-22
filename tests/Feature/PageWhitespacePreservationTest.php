<?php

namespace Coderstm\Tests\Feature;

use Coderstm\Tests\TestCase;
use Coderstm\Http\Middleware\PreserveJsonWhitespace;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

class PageWhitespacePreservationTest extends TestCase
{
    #[Test]
    public function middleware_preserves_whitespace_in_json_data()
    {
        $middleware = new PreserveJsonWhitespace();

        // Create a JSON request with whitespace that would normally be trimmed
        $jsonData = [
            'data' => [
                'components' => [
                    [
                        'type' => 'textnode',
                        'content' => 'Be at least 18 years old or ' // Trailing space
                    ],
                    [
                        'type' => 'link',
                        'components' => [
                            [
                                'type' => 'textnode',
                                'content' => 'have ' // Trailing space
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($jsonData));

        $nextCalled = false;
        /** @var Request|null $preservedRequest */
        $preservedRequest = null;

        $response = $middleware->handle($request, function ($req) use (&$nextCalled, &$preservedRequest) {
            $nextCalled = true;
            $preservedRequest = $req;
            return new Response();
        });

        $this->assertTrue($nextCalled);
        $this->assertNotNull($preservedRequest);

        // Verify that whitespace is preserved
        $data = $preservedRequest->input('data');
        $this->assertNotNull($data);
        $this->assertSame('Be at least 18 years old or ', $data['components'][0]['content']);
        $this->assertSame('have ', $data['components'][1]['components'][0]['content']);
    }

    #[Test]
    public function middleware_preserves_non_breaking_spaces()
    {
        $middleware = new PreserveJsonWhitespace();

        $jsonData = [
            'data' => [
                'content' => "Text with\u{00A0}non-breaking\u{00A0}spaces"
            ]
        ];

        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($jsonData));

        /** @var Request|null $preservedRequest */
        $preservedRequest = null;

        $middleware->handle($request, function ($req) use (&$preservedRequest) {
            $preservedRequest = $req;
            return new Response();
        });

        $this->assertNotNull($preservedRequest);
        $data = $preservedRequest->input('data');
        $this->assertNotNull($data);
        $this->assertSame("Text with\u{00A0}non-breaking\u{00A0}spaces", $data['content']);
    }

    #[Test]
    public function middleware_handles_non_json_requests_gracefully()
    {
        $middleware = new PreserveJsonWhitespace();

        $request = Request::create('/test', 'POST', [
            'data' => [
                'content' => 'regular form data'
            ]
        ]);

        $nextCalled = false;

        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response();
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Response::class, $response);
    }

    #[Test]
    public function middleware_handles_invalid_json_gracefully()
    {
        $middleware = new PreserveJsonWhitespace();

        $request = Request::create('/test', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], '{"invalid": json}');

        $nextCalled = false;

        $response = $middleware->handle($request, function ($req) use (&$nextCalled) {
            $nextCalled = true;
            return new Response();
        });

        $this->assertTrue($nextCalled);
        $this->assertInstanceOf(Response::class, $response);
    }
}
