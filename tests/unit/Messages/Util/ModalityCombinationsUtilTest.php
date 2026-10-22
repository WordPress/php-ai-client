<?php

declare(strict_types=1);

namespace WordPress\AiClient\Tests\unit\Messages\Util;

use PHPUnit\Framework\TestCase;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Messages\Util\ModalityCombinationsUtil;

/**
 * @covers \WordPress\AiClient\Messages\Util\ModalityCombinationsUtil
 */
class ModalityCombinationsUtilTest extends TestCase
{
    /**
     * Tests that buildCombinations returns the correct number of combinations.
     *
     * @dataProvider combinationCountProvider
     * @param list<ModalityEnum> $required The required modalities.
     * @param list<ModalityEnum> $optional The optional modalities.
     * @param int $expectedCount The expected number of combinations.
     * @return void
     */
    public function testBuildCombinationsReturnsCorrectCount(
        array $required,
        array $optional,
        int $expectedCount
    ): void {
        $combinations = ModalityCombinationsUtil::buildCombinations($required, $optional);

        $this->assertCount($expectedCount, $combinations);
    }

    /**
     * Provides data for combination count tests.
     *
     * @return array<string, array{list<ModalityEnum>, list<ModalityEnum>, int}>
     */
    public function combinationCountProvider(): array
    {
        return [
            'both empty'                       => [[], [], 1],
            'only required'                    => [[ModalityEnum::text()], [], 1],
            'only optional one'                => [[], [ModalityEnum::text()], 2],
            'only optional two'                => [[], [ModalityEnum::text(), ModalityEnum::audio()], 4],
            'required and one optional'        => [[ModalityEnum::text()], [ModalityEnum::image()], 2],
            'required and two optional'        => [
                [ModalityEnum::text()],
                [ModalityEnum::image(), ModalityEnum::audio()],
                4,
            ],
            'required and four optional'       => [
                [ModalityEnum::text()],
                [ModalityEnum::image(), ModalityEnum::audio(), ModalityEnum::document(), ModalityEnum::video()],
                16,
            ],
        ];
    }

    /**
     * Tests that every combination always contains all required modalities.
     *
     * @return void
     */
    public function testBuildCombinationsAlwaysIncludesRequiredModalities(): void
    {
        $required = [ModalityEnum::text()];
        $optional = [ModalityEnum::image(), ModalityEnum::audio()];

        $combinations = ModalityCombinationsUtil::buildCombinations($required, $optional);

        foreach ($combinations as $combo) {
            $this->assertContains(ModalityEnum::text(), $combo);
        }
    }

    /**
     * Tests that buildCombinations produces no duplicate combinations.
     *
     * @return void
     */
    public function testBuildCombinationsProducesNoDuplicates(): void
    {
        $required = [ModalityEnum::text()];
        $optional = [ModalityEnum::image(), ModalityEnum::audio(), ModalityEnum::document()];

        $combinations = ModalityCombinationsUtil::buildCombinations($required, $optional);

        // Normalise each combo to a sorted value string for de-duplication comparison.
        $normalised = array_map(
            static function (array $combo): string {
                $values = array_map(
                    static function (ModalityEnum $m): string {
                        return $m->value;
                    },
                    $combo
                );
                sort($values);
                return implode(',', $values);
            },
            $combinations
        );

        $this->assertCount(count($normalised), array_unique($normalised));
    }

    /**
     * Tests that the combination with no optional modalities is always present.
     *
     * @return void
     */
    public function testBuildCombinationsIncludesRequiredOnlyCombination(): void
    {
        $required = [ModalityEnum::text()];
        $optional = [ModalityEnum::image(), ModalityEnum::audio()];

        $combinations = ModalityCombinationsUtil::buildCombinations($required, $optional);

        // The first combination produced by the bitmask loop (i=0) is always required-only.
        $firstCombo = $combinations[0];
        $this->assertCount(count($required), $firstCombo);
        $this->assertContains(ModalityEnum::text(), $firstCombo);
    }

    /**
     * Tests that both required and optional empty returns a single empty combination.
     *
     * @return void
     */
    public function testBuildCombinationsBothEmptyReturnsSingleEmptyCombo(): void
    {
        $combinations = ModalityCombinationsUtil::buildCombinations([], []);

        $this->assertCount(1, $combinations);
        $this->assertSame([], $combinations[0]);
    }

    /**
     * Tests that optional modalities each appear in exactly half the combinations.
     *
     * With k optional modalities there are 2^k subsets, and each specific optional
     * modality appears in exactly 2^(k-1) of them.
     *
     * @return void
     */
    public function testBuildCombinationsEachOptionalAppearsInExactlyHalfTheCombinations(): void
    {
        $optional     = [ModalityEnum::image(), ModalityEnum::audio(), ModalityEnum::document()];
        $combinations = ModalityCombinationsUtil::buildCombinations([], $optional);

        $expectedAppearances = count($combinations) / 2; // 2^(k-1) = 4

        foreach ($optional as $modality) {
            $appearances = count(
                array_filter(
                    $combinations,
                    static function (array $combo) use ($modality): bool {
                        return in_array($modality, $combo, true);
                    }
                )
            );

            $this->assertSame(
                (int) $expectedAppearances,
                $appearances,
                sprintf('Modality "%s" should appear in exactly half the combinations.', $modality->value)
            );
        }
    }
}
