<?php

namespace Rosalana\Core\Support;

final class WildcardMatch
{
    private const MAX_SCORE = 100_000;

    private const STAR_PENALTY = 25;

    public function __construct(protected string $value) {}

    /**
     * Get current value.
     * 
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Check if the given text match the wildcard.
     * 
     * @param string $wildcard may include wildcards (*) and variants ({opt1|opt2})
     * @return bool
     */
    public function wildcard(string $wildcard): bool
    {
        return $this->score($wildcard) > 0;
    }

    /**
     * Calculate a score indicating how well the given string matches the wildcard.
     * 
     * @param string $wildcard may include wildcards (*) and variants ({opt1|opt2})
     * @return int 0 = no match, higher is better match (0-100000)
     */
    public function score(string $wildcard): int
    {
        return $this->calculate($wildcard);
    }

    /**
     * Get the best match from the given array of wildcards
     *
     * @param array<string> $wildcards
     */
    public function best(array $wildcards): ?string
    {
        $best = null;
        $max = 0;

        foreach ($wildcards as $wildcard) {
            $score = $this->score($wildcard);

            if ($score > $max) {
                $max = $score;
                $best = $wildcard;
            }
        }

        return $best;
    }


    /**
     * Use array_keys as wildcards and return the best array_value
     *
     * @param array<string, mixed> $options
     */
    public function resolve(array $options): mixed
    {
        return $options[$this->best(array_keys($options))] ?? null;
    }

    /**
     * Get only the matching wildcards ordered by score
     *
     * @param array<string> $wildcards
     */
    public function matching(array $wildcards): array
    {
        return array_keys($this->ranked($wildcards));
    }

    /**
     * Get only the matching wildcards with the score for each.
     *
     * @param array<string> $wildcards
     */
    public function ranked(array $wildcards): array
    {
        $results = [];

        foreach ($wildcards as $wildcard) {
            $score = $this->score($wildcard);

            if ($score > 0) {
                $results[$wildcard] = $score;
            }
        }

        arsort($results);

        return $results;
    }

    /**
     * Check if any of the given wildcards match the text
     *
     * @param array<string> $wildcards
     */
    public function any(array $wildcards): bool
    {
        return !empty($this->matching($wildcards));
    }

    /**
     * Check if all of the given wildcards match the text
     *
     * @param array<string> $wildcards
     */
    public function every(array $wildcards): bool
    {
        return count($this->matching($wildcards)) === count($wildcards);
    }

    /**
     * Check if none of the given wildcards match the text
     *
     * @param array<string> $wildcards
     */
    public function none(array $wildcards): bool
    {
        return empty($this->matching($wildcards));
    }

    /**
     * Calculate the score for the given string against this wildcard.
     * 
     * @param string $wildcard
     * @return int 0 = no match, higher is better match (0-100000)
     */
    private function calculate(string $wildcard): int
    {
        if ($wildcard === '*') return 1;

        $variants = $this->variants($wildcard);

        $matching = [];

        foreach ($variants as $variant) {
            if (fnmatch($variant, $this->value, FNM_NOESCAPE)) {
                $matching[] = $variant;
            }
        }

        if (empty($matching)) return 0;

        $variantCount = max(1, count($variants));

        $bestStars = PHP_INT_MAX;
        $bestLiteralLen = 0;

        foreach ($matching as $v) {
            $stars = substr_count($v, '*');
            $literalLen = strlen(str_replace('*', '', $v));

            if ($stars < $bestStars) {
                $bestStars = $stars;
                $bestLiteralLen = $literalLen;
            } elseif ($stars === $bestStars && $literalLen > $bestLiteralLen) {
                $bestLiteralLen = $literalLen;
            }
        }

        $score = intdiv(self::MAX_SCORE, $variantCount);
        $score = intdiv($score, 1 + ($bestStars * $bestStars * self::STAR_PENALTY));
        $score += $bestLiteralLen;

        return min(self::MAX_SCORE, max(1, $score));
    }

    /**
     * Generate all possible variants of the wildcard string.
     * 
     * @param string $wildcard may include wildcards (*) and variants ({opt1|opt2})
     * @return array<string>
     */
    private function variants(string $wildcard): array
    {
        if (!preg_match('/\{([^}]+)\}/', $wildcard, $m, PREG_OFFSET_CAPTURE)) {
            return [$wildcard];
        }

        $full = $m[0][0];
        $pos  = $m[0][1];
        $len  = strlen($full);
        $opts = explode('|', $m[1][0]);

        $out = [];

        foreach ($opts as $opt) {
            $next = substr_replace($wildcard, $opt, $pos, $len);

            foreach ($this->variants($next) as $v) {
                $out[] = $v;
            }
        }

        return array_values(array_unique($out));
    }
}
