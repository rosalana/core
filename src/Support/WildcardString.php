<?php

namespace Rosalana\Core\Support;

final class WildcardString
{
    private const MAX_SCORE = 100_000;
    
    private const STAR_PENALTY = 25;

    /**
     * Create a new WildcardString instance.
     * 
     * @param string $wildcard may include wildcards (*) and variants ({opt1|opt2})
     */
    public function __construct(protected string $wildcard) {}

    /**
     * Get the wildcard string.
     * 
     * @return string
     */
    public function wildcard(): string
    {
        return $this->wildcard;
    }

    /**
     * Check if the given string matches the wildcard.
     * 
     * @param string $string
     * @return bool
     */
    public function match(string $string): bool
    {
        return $this->score($string) > 0;
    }

    /**
     * Calculate a score indicating how well the given string matches the wildcard.
     * 
     * @param string $string
     * @return int 0 = no match, higher is better match (0-100000)
     */
    public function score(string $string): int
    {
        return $this->calculate($string);
    }

    /**
     * Select the best matching option from the given array.
     * 
     * @param array<string> $options
     * @return string|null
     */
    public function select(array $options): ?string
    {
        $result = null;
        $best = 0;

        foreach ($options as $option) {
            $score = $this->calculate($option);

            if ($score > $best) {
                $best = $score;
                $result = $option;
            }
        }

        return $result;
    }

    /**
     * Explain the selection process for the given options.
     * 
     * @param array<string> $options
     * @return array<int, array{wildcard: string, option: string, score: int, matched: bool}>
     */
    public function explainSelect(array $options): array
    {
        $results = [];

        foreach ($options as $option) {
            $results[] = [
                'wildcard' => $this->wildcard,
                'option' => $option,
                'score' => $this->calculate($option),
                'matched' => false,
            ];
        }

        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        if ($results !== []) {
            $results[0]['matched'] = $results[0]['score'] > 0;
        }

        return $results;
    }

    /**
     * Calculate the score for the given string against this wildcard.
     * 
     * @param string $string
     * @return int 0 = no match, higher is better match (0-100000)
     */
    private function calculate(string $string): int
    {
        if ($this->wildcard === '*') return 1;

        $variants = $this->variants();

        $matching = [];

        foreach ($variants as $variant) {
            if (fnmatch($variant, $string, FNM_NOESCAPE)) {
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
     * @param string|null $wildcard
     * @return array<string>
     */
    private function variants(?string $wildcard = null): array
    {
        $wildcard ??= $this->wildcard;

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
