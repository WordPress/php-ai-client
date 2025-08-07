<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Results\DTO;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tests\traits\ArrayTransformationTestTrait;
use WordPress\AiClient\Tools\DTO\FunctionCall;

/**
 * @covers \WordPress\AiClient\Results\DTO\GenerativeAiResult
 */
class GenerativeAiResultTest extends TestCase
{
    use ArrayTransformationTestTrait;

    /**
     * Tests creating result with single candidate.
     *
     * @return void
     */
    public function testCreateWithSingleCandidate(): void
    {
        $message = new ModelMessage([
            new MessagePart('This is the AI response.')
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        $tokenUsage = new TokenUsage(20, 10, 30);

        $result = new GenerativeAiResult(
            'result_123',
            [$candidate],
            $tokenUsage
        );

        $this->assertEquals('result_123', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertSame($candidate, $result->getCandidates()[0]);
        $this->assertSame($tokenUsage, $result->getTokenUsage());
        $this->assertEquals([], $result->getProviderMetadata());
    }

    /**
     * Tests creating result with multiple candidates.
     *
     * @return void
     */
    public function testCreateWithMultipleCandidates(): void
    {
        $candidates = [];
        for ($i = 1; $i <= 3; $i++) {
            $message = new ModelMessage([
                new MessagePart("Response variant $i")
            ]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), $i * 10);
        }
        $tokenUsage = new TokenUsage(20, 90, 110);

        $result = new GenerativeAiResult(
            'result_multi',
            $candidates,
            $tokenUsage
        );

        $this->assertCount(3, $result->getCandidates());
        $this->assertEquals(3, $result->getCandidateCount());
        $this->assertTrue($result->hasMultipleCandidates());
    }

    /**
     * Tests creating result with provider metadata.
     *
     * @return void
     */
    public function testCreateWithProviderMetadata(): void
    {
        $message = new ModelMessage([new MessagePart('Response')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 5);
        $tokenUsage = new TokenUsage(10, 5, 15);
        $metadata = [
            'model' => 'gpt-4',
            'temperature' => 0.7,
            'max_tokens' => 1000,
            'custom_data' => ['key' => 'value']
        ];

        $result = new GenerativeAiResult(
            'result_meta',
            [$candidate],
            $tokenUsage,
            $metadata
        );

        $this->assertEquals($metadata, $result->getProviderMetadata());
    }

    /**
     * Tests result rejects empty candidates array.
     *
     * @return void
     */
    public function testRejectsEmptyCandidatesArray(): void
    {
        $tokenUsage = new TokenUsage(0, 0, 0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one candidate must be provided');

        new GenerativeAiResult('result_empty', [], $tokenUsage);
    }

    /**
     * Tests toText method.
     *
     * @return void
     */
    public function testToText(): void
    {
        $text = 'This is the extracted text content.';
        $message = new ModelMessage([
            new MessagePart($text)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 8);
        $tokenUsage = new TokenUsage(10, 8, 18);

        $result = new GenerativeAiResult(
            'result_text',
            [$candidate],
            $tokenUsage
        );

        $this->assertEquals($text, $result->toText());
    }

    /**
     * Tests toText throws exception when no text content.
     *
     * @return void
     */
    public function testToTextThrowsExceptionWhenNoTextContent(): void
    {
        $file = new File('https://example.com/image.jpg', 'image/jpeg');
        $message = new ModelMessage([
            new MessagePart($file)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 5);
        $tokenUsage = new TokenUsage(10, 5, 15);

        $result = new GenerativeAiResult(
            'result_no_text',
            [$candidate],
            $tokenUsage
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No text content found in first candidate');

        $result->toText();
    }

    /**
     * Tests toFile method.
     *
     * @return void
     */
    public function testToFile(): void
    {
        $base64Data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVQI12P4DwABAQEAG7buVgAAAABJRU5ErkJggg==';
        $file = new File(
            'data:image/png;base64,' . $base64Data,
            'image/png'
        );
        $message = new ModelMessage([
            new MessagePart('Here is the generated image:'),
            new MessagePart($file)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 20);
        $tokenUsage = new TokenUsage(15, 20, 35);

        $result = new GenerativeAiResult(
            'result_file',
            [$candidate],
            $tokenUsage
        );

        $this->assertSame($file, $result->toFile());
    }

    /**
     * Tests toFile throws exception when no file content.
     *
     * @return void
     */
    public function testToFileThrowsExceptionWhenNoFileContent(): void
    {
        $message = new ModelMessage([
            new MessagePart('Just text, no file.')
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 5);
        $tokenUsage = new TokenUsage(10, 5, 15);

        $result = new GenerativeAiResult(
            'result_no_file',
            [$candidate],
            $tokenUsage
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No file content found in first candidate');

        $result->toFile();
    }

    /**
     * Tests toImageFile method.
     *
     * @return void
     */
    public function testToImageFile(): void
    {
        $imageFile = new File('https://example.com/photo.jpg', 'image/jpeg');
        $message = new ModelMessage([
            new MessagePart($imageFile)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        $tokenUsage = new TokenUsage(5, 10, 15);

        $result = new GenerativeAiResult(
            'result_image',
            [$candidate],
            $tokenUsage
        );

        $this->assertSame($imageFile, $result->toImageFile());
    }

    /**
     * Tests toImageFile throws exception for non-image file.
     *
     * @return void
     */
    public function testToImageFileThrowsExceptionForNonImageFile(): void
    {
        $pdfFile = new File('https://example.com/document.pdf', 'application/pdf');
        $message = new ModelMessage([
            new MessagePart($pdfFile)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        $tokenUsage = new TokenUsage(5, 10, 15);

        $result = new GenerativeAiResult(
            'result_pdf',
            [$candidate],
            $tokenUsage
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File is not an image. MIME type: application/pdf');

        $result->toImageFile();
    }

    /**
     * Tests toAudioFile method.
     *
     * @return void
     */
    public function testToAudioFile(): void
    {
        $audioFile = new File('https://example.com/song.mp3', 'audio/mpeg');
        $message = new ModelMessage([
            new MessagePart($audioFile)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        $tokenUsage = new TokenUsage(5, 10, 15);

        $result = new GenerativeAiResult(
            'result_audio',
            [$candidate],
            $tokenUsage
        );

        $this->assertSame($audioFile, $result->toAudioFile());
    }

    /**
     * Tests toVideoFile method.
     *
     * @return void
     */
    public function testToVideoFile(): void
    {
        $videoFile = new File('https://example.com/video.mp4', 'video/mp4');
        $message = new ModelMessage([
            new MessagePart($videoFile)
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 10);
        $tokenUsage = new TokenUsage(5, 10, 15);

        $result = new GenerativeAiResult(
            'result_video',
            [$candidate],
            $tokenUsage
        );

        $this->assertSame($videoFile, $result->toVideoFile());
    }

    /**
     * Tests toMessage method.
     *
     * @return void
     */
    public function testToMessage(): void
    {
        $message = new ModelMessage([
            new MessagePart('Response message')
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 3);
        $tokenUsage = new TokenUsage(5, 3, 8);

        $result = new GenerativeAiResult(
            'result_msg',
            [$candidate],
            $tokenUsage
        );

        $this->assertSame($message, $result->toMessage());
    }

    /**
     * Tests toTexts method with multiple candidates.
     *
     * @return void
     */
    public function testToTextsWithMultipleCandidates(): void
    {
        $texts = ['First response', 'Second response', 'Third response'];
        $candidates = [];

        foreach ($texts as $text) {
            $message = new ModelMessage([
                new MessagePart($text)
            ]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 5);
        }

        $tokenUsage = new TokenUsage(20, 15, 35);
        $result = new GenerativeAiResult(
            'result_texts',
            $candidates,
            $tokenUsage
        );

        $this->assertEquals($texts, $result->toTexts());
    }

    /**
     * Tests toFiles method with multiple candidates.
     *
     * @return void
     */
    public function testToFilesWithMultipleCandidates(): void
    {
        $file1 = new File('https://example.com/image1.jpg', 'image/jpeg');
        $file2 = new File('https://example.com/image2.png', 'image/png');
        $file3 = new File('https://example.com/doc.pdf', 'application/pdf');

        $candidates = [];
        foreach ([$file1, $file2, $file3] as $file) {
            $message = new ModelMessage([
                new MessagePart('Generated file:'),
                new MessagePart($file)
            ]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 10);
        }

        $tokenUsage = new TokenUsage(30, 30, 60);
        $result = new GenerativeAiResult(
            'result_files',
            $candidates,
            $tokenUsage
        );

        $files = $result->toFiles();
        $this->assertCount(3, $files);
        $this->assertSame($file1, $files[0]);
        $this->assertSame($file2, $files[1]);
        $this->assertSame($file3, $files[2]);
    }

    /**
     * Tests toImageFiles filters only image files.
     *
     * @return void
     */
    public function testToImageFilesFiltersOnlyImages(): void
    {
        $imageFile1 = new File('https://example.com/image1.jpg', 'image/jpeg');
        $pdfFile = new File('https://example.com/doc.pdf', 'application/pdf');
        $imageFile2 = new File('https://example.com/image2.png', 'image/png');

        $candidates = [];
        foreach ([$imageFile1, $pdfFile, $imageFile2] as $file) {
            $message = new ModelMessage([new MessagePart($file)]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 10);
        }

        $tokenUsage = new TokenUsage(30, 30, 60);
        $result = new GenerativeAiResult(
            'result_mixed',
            $candidates,
            $tokenUsage
        );

        $images = $result->toImageFiles();
        $this->assertCount(2, $images);
        $this->assertSame($imageFile1, $images[0]);
        $this->assertSame($imageFile2, $images[1]);
    }

    /**
     * Tests toAudioFiles filters only audio files.
     *
     * @return void
     */
    public function testToAudioFilesFiltersOnlyAudio(): void
    {
        $audioFile1 = new File('https://example.com/song.mp3', 'audio/mpeg');
        $imageFile = new File('https://example.com/image.jpg', 'image/jpeg');
        $audioFile2 = new File('https://example.com/podcast.wav', 'audio/wav');

        $candidates = [];
        foreach ([$audioFile1, $imageFile, $audioFile2] as $file) {
            $message = new ModelMessage([new MessagePart($file)]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 10);
        }

        $tokenUsage = new TokenUsage(30, 30, 60);
        $result = new GenerativeAiResult(
            'result_audio_mix',
            $candidates,
            $tokenUsage
        );

        $audioFiles = $result->toAudioFiles();
        $this->assertCount(2, $audioFiles);
        $this->assertSame($audioFile1, $audioFiles[0]);
        $this->assertSame($audioFile2, $audioFiles[1]);
    }

    /**
     * Tests toVideoFiles filters only video files.
     *
     * @return void
     */
    public function testToVideoFilesFiltersOnlyVideo(): void
    {
        $videoFile1 = new File('https://example.com/movie.mp4', 'video/mp4');
        $imageFile = new File('https://example.com/image.jpg', 'image/jpeg');
        $videoFile2 = new File('https://example.com/clip.webm', 'video/webm');

        $candidates = [];
        foreach ([$videoFile1, $imageFile, $videoFile2] as $file) {
            $message = new ModelMessage([new MessagePart($file)]);
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 10);
        }

        $tokenUsage = new TokenUsage(30, 30, 60);
        $result = new GenerativeAiResult(
            'result_video_mix',
            $candidates,
            $tokenUsage
        );

        $videoFiles = $result->toVideoFiles();
        $this->assertCount(2, $videoFiles);
        $this->assertSame($videoFile1, $videoFiles[0]);
        $this->assertSame($videoFile2, $videoFiles[1]);
    }

    /**
     * Tests toMessages method.
     *
     * @return void
     */
    public function testToMessages(): void
    {
        $messages = [];
        $candidates = [];

        for ($i = 1; $i <= 3; $i++) {
            $message = new ModelMessage([
                new MessagePart("Message $i")
            ]);
            $messages[] = $message;
            $candidates[] = new Candidate($message, FinishReasonEnum::stop(), 5);
        }

        $tokenUsage = new TokenUsage(15, 15, 30);
        $result = new GenerativeAiResult(
            'result_messages',
            $candidates,
            $tokenUsage
        );

        $extractedMessages = $result->toMessages();
        $this->assertCount(3, $extractedMessages);
        foreach ($messages as $index => $message) {
            $this->assertSame($message, $extractedMessages[$index]);
        }
    }

    /**
     * Tests JSON schema.
     *
     * @return void
     */
    public function testJsonSchema(): void
    {
        $schema = GenerativeAiResult::getJsonSchema();

        $this->assertIsArray($schema);
        $this->assertEquals('object', $schema['type']);

        // Check properties
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey(GenerativeAiResult::KEY_ID, $schema['properties']);
        $this->assertArrayHasKey(GenerativeAiResult::KEY_CANDIDATES, $schema['properties']);
        $this->assertArrayHasKey(GenerativeAiResult::KEY_TOKEN_USAGE, $schema['properties']);
        $this->assertArrayHasKey(GenerativeAiResult::KEY_PROVIDER_METADATA, $schema['properties']);

        // Check id property
        $this->assertEquals('string', $schema['properties'][GenerativeAiResult::KEY_ID]['type']);

        // Check candidates property
        $candidatesSchema = $schema['properties'][GenerativeAiResult::KEY_CANDIDATES];
        $this->assertEquals('array', $candidatesSchema['type']);
        $this->assertEquals(1, $candidatesSchema['minItems']);

        // Check providerMetadata property
        $metadataSchema = $schema['properties'][GenerativeAiResult::KEY_PROVIDER_METADATA];
        $this->assertEquals('object', $metadataSchema['type']);
        $this->assertTrue($metadataSchema['additionalProperties']);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        $this->assertContains(GenerativeAiResult::KEY_ID, $schema['required']);
        $this->assertContains(GenerativeAiResult::KEY_CANDIDATES, $schema['required']);
        $this->assertContains(GenerativeAiResult::KEY_TOKEN_USAGE, $schema['required']);
        $this->assertNotContains(GenerativeAiResult::KEY_PROVIDER_METADATA, $schema['required']);
    }

    /**
     * Tests result implements ResultInterface.
     *
     * @return void
     */
    public function testImplementsResultInterface(): void
    {
        $message = new ModelMessage([new MessagePart('Test')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 1);
        $tokenUsage = new TokenUsage(1, 1, 2);

        $result = new GenerativeAiResult(
            'result_interface',
            [$candidate],
            $tokenUsage
        );

        $this->assertInstanceOf(
            \WordPress\AiClient\Results\Contracts\ResultInterface::class,
            $result
        );
    }

    /**
     * Tests hasMultipleCandidates returns false for single candidate.
     *
     * @return void
     */
    public function testHasMultipleCandidatesReturnsFalseForSingle(): void
    {
        $message = new ModelMessage([new MessagePart('Single response')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 3);
        $tokenUsage = new TokenUsage(5, 3, 8);

        $result = new GenerativeAiResult(
            'result_single',
            [$candidate],
            $tokenUsage
        );

        $this->assertFalse($result->hasMultipleCandidates());
        $this->assertEquals(1, $result->getCandidateCount());
    }

    /**
     * Tests array transformation.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $message = new ModelMessage([
            new MessagePart('AI generated response'),
            new MessagePart('with multiple parts')
        ]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 15);
        $tokenUsage = new TokenUsage(10, 15, 25);
        $metadata = ['model' => 'test-model', 'version' => '1.0'];

        $result = new GenerativeAiResult(
            'result_json_123',
            [$candidate],
            $tokenUsage,
            $metadata
        );

        $json = $this->assertToArrayReturnsArray($result);

        $this->assertArrayHasKeys(
            $json,
            [
                GenerativeAiResult::KEY_ID,
                GenerativeAiResult::KEY_CANDIDATES,
                GenerativeAiResult::KEY_TOKEN_USAGE,
                GenerativeAiResult::KEY_PROVIDER_METADATA
            ]
        );
        $this->assertEquals('result_json_123', $json[GenerativeAiResult::KEY_ID]);
        $this->assertIsArray($json[GenerativeAiResult::KEY_CANDIDATES]);
        $this->assertCount(1, $json[GenerativeAiResult::KEY_CANDIDATES]);
        $this->assertIsArray($json[GenerativeAiResult::KEY_TOKEN_USAGE]);
        $this->assertEquals($metadata, $json[GenerativeAiResult::KEY_PROVIDER_METADATA]);
    }

    /**
     * Tests fromJson method.
     *
     * @return void
     */
    public function testFromArray(): void
    {
        $json = [
            GenerativeAiResult::KEY_ID => 'result_from_json',
            GenerativeAiResult::KEY_CANDIDATES => [
                [
                    Candidate::KEY_MESSAGE => [
                        Message::KEY_ROLE => MessageRoleEnum::model()->value,
                        Message::KEY_PARTS => [
                            [
                                MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value,
                                MessagePart::KEY_TEXT => 'First part'
                            ],
                            [
                                MessagePart::KEY_TYPE => MessagePartTypeEnum::text()->value,
                                MessagePart::KEY_TEXT => 'Second part'
                            ]
                        ]
                    ],
                    Candidate::KEY_FINISH_REASON => FinishReasonEnum::stop()->value,
                ]
            ],
            GenerativeAiResult::KEY_TOKEN_USAGE => [
                TokenUsage::KEY_PROMPT_TOKENS => 8,
                TokenUsage::KEY_COMPLETION_TOKENS => 20,
                TokenUsage::KEY_TOTAL_TOKENS => 28
            ],
            GenerativeAiResult::KEY_PROVIDER_METADATA => ['provider' => 'test']
        ];

        $result = GenerativeAiResult::fromArray($json);

        $this->assertInstanceOf(GenerativeAiResult::class, $result);
        $this->assertEquals('result_from_json', $result->getId());
        $this->assertCount(1, $result->getCandidates());
        $this->assertEquals(8, $result->getTokenUsage()->getPromptTokens());
        $this->assertEquals(20, $result->getTokenUsage()->getCompletionTokens());
        $this->assertEquals(28, $result->getTokenUsage()->getTotalTokens());
        $this->assertEquals(['provider' => 'test'], $result->getProviderMetadata());
    }

    /**
     * Tests round-trip array transformation with multiple candidates.
     *
     * @return void
     */
    public function testArrayRoundTripWithMultipleCandidates(): void
    {
        $candidates = [];
        for ($i = 1; $i <= 2; $i++) {
            $message = new ModelMessage([
                new MessagePart("Response $i"),
                new MessagePart(new FunctionCall("call_$i", "func$i", ['arg' => $i]))
            ]);
            $candidates[] = new Candidate($message, FinishReasonEnum::toolCalls(), 25 * $i);
        }

        $this->assertArrayRoundTrip(
            new GenerativeAiResult(
                'result_roundtrip',
                $candidates,
                new TokenUsage(30, 75, 105),
                ['test_meta' => true]
            ),
            function ($original, $restored) {
                $this->assertEquals($original->getId(), $restored->getId());
                $this->assertCount(count($original->getCandidates()), $restored->getCandidates());
                $this->assertEquals(
                    $original->getTokenUsage()->getTotalTokens(),
                    $restored->getTokenUsage()->getTotalTokens()
                );
                $this->assertEquals($original->getProviderMetadata(), $restored->getProviderMetadata());

                // Check first candidate details
                $originalFirst = $original->getCandidates()[0];
                $restoredFirst = $restored->getCandidates()[0];
                $this->assertEquals(
                    $originalFirst->getMessage()->getParts()[0]->getText(),
                    $restoredFirst->getMessage()->getParts()[0]->getText()
                );
                $this->assertEquals(
                    $originalFirst->getMessage()->getParts()[1]->getFunctionCall()->getId(),
                    $restoredFirst->getMessage()->getParts()[1]->getFunctionCall()->getId()
                );
            }
        );
    }

    /**
     * Tests array transformation without provider metadata.
     *
     * @return void
     */
    public function testToArrayWithoutProviderMetadata(): void
    {
        $message = new ModelMessage([new MessagePart('Simple response')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 5);
        $tokenUsage = new TokenUsage(3, 5, 8);

        $result = new GenerativeAiResult(
            'result_no_meta',
            [$candidate],
            $tokenUsage
        );

        $json = $this->assertToArrayReturnsArray($result);

        $this->assertArrayHasKeys(
            $json,
            [
                GenerativeAiResult::KEY_ID,
                GenerativeAiResult::KEY_CANDIDATES,
                GenerativeAiResult::KEY_TOKEN_USAGE,
                GenerativeAiResult::KEY_PROVIDER_METADATA
            ]
        );
        $this->assertEquals([], $json[GenerativeAiResult::KEY_PROVIDER_METADATA]);
    }

    /**
     * Tests GenerativeAiResult implements WithArrayTransformationInterface.
     *
     * @return void
     */
    public function testImplementsWithArrayTransformationInterface(): void
    {
        $message = new ModelMessage([new MessagePart('test')]);
        $candidate = new Candidate($message, FinishReasonEnum::stop(), 1);
        $tokenUsage = new TokenUsage(1, 1, 2);

        $result = new GenerativeAiResult('test', [$candidate], $tokenUsage);
        $this->assertImplementsArrayTransformation($result);
    }
}
