<?php

namespace App\Support;

class ArabicSearch
{
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $value) ?? $value;
        $value = strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ٱ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'ة' => 'ه',
        ]);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    public static function matches(?string $candidate, ?string $query): bool
    {
        $normalizedCandidate = self::comparisonKey($candidate);
        $normalizedQuery = self::comparisonKey($query);

        if ($normalizedQuery === '') {
            return false;
        }

        return str_contains($normalizedCandidate, $normalizedQuery)
            || str_contains(
                str_replace('ال', '', $normalizedCandidate),
                str_replace('ال', '', $normalizedQuery)
            );
    }

    private static function comparisonKey(?string $value): string
    {
        $words = preg_split('/\s+/u', self::normalize($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('', array_map(
            static fn (string $word): string => str_starts_with($word, 'ال') && mb_strlen($word) > 3
                ? mb_substr($word, 2)
                : $word,
            $words
        ));
    }

    /**
     * @param  iterable<int, string>  $candidates
     * @return array<int, string>
     */
    public static function suggestions(iterable $candidates, string $query, int $limit = 5): array
    {
        $normalizedQuery = self::normalize($query);
        $ranked = [];

        foreach ($candidates as $candidate) {
            $normalizedCandidate = self::normalize($candidate);
            similar_text($normalizedQuery, $normalizedCandidate, $similarity);
            $ranked[] = [
                'value' => $candidate,
                'score' => self::matches($candidate, $query) ? 200 + $similarity : $similarity,
            ];
        }

        usort($ranked, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);

        return array_map(
            static fn (array $item): string => $item['value'],
            array_slice($ranked, 0, max(1, $limit))
        );
    }
}
