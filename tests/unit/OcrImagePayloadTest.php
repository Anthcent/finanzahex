<?php

use App\Controllers\OcrController;
use PHPUnit\Framework\TestCase;

final class OcrImagePayloadTest extends TestCase
{
    private function normalizer(): object
    {
        return new class extends OcrController {
            public function normalize(string $image): ?string
            {
                return $this->normalizeOcrImagePayload($image);
            }
        };
    }

    public function testAddsDataUriToLegacyRawJpegPayload(): void
    {
        $raw = base64_encode("\xFF\xD8\xFFtest-jpeg");

        $this->assertSame(
            'data:image/jpeg;base64,' . $raw,
            $this->normalizer()->normalize($raw)
        );
    }

    public function testPreservesValidCompleteDataUri(): void
    {
        $raw = base64_encode("\x89PNG\r\n\x1a\ntest-png");
        $payload = 'data:image/png;base64,' . $raw;

        $this->assertSame($payload, $this->normalizer()->normalize($payload));
    }

    public function testRejectsInvalidBase64(): void
    {
        $this->assertNull($this->normalizer()->normalize('not-valid-base64%%%'));
    }
}
