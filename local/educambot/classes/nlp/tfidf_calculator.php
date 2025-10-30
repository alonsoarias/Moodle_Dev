<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TF-IDF (Term Frequency-Inverse Document Frequency) calculator for advanced text relevance.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use local_educambot\local\text_helper;

/**
 * Calculates TF-IDF scores for terms across documents to determine relevance.
 */
class tfidf_calculator {
    /** @var array<string,int> Document frequency: how many documents contain each term */
    protected array $documentfrequency = [];

    /** @var int Total number of documents */
    protected int $totaldocuments = 0;

    /** @var array<int,array<string,int>> Term frequency per document */
    protected array $termfrequency = [];

    /** @var array<int,array<string,float>> Cached TF-IDF scores per document */
    protected array $tfidfcache = [];

    /** @var bool Whether the IDF has been calculated */
    protected bool $idfcalculated = false;

    /**
     * Adds a document to the corpus for TF-IDF calculation.
     *
     * @param int $docid Document identifier
     * @param array<int,string> $tokens Array of tokens/terms
     */
    public function add_document(int $docid, array $tokens): void {
        if (empty($tokens)) {
            return;
        }

        $uniqueterms = array_unique($tokens);
        $termcounts = array_count_values($tokens);

        // Store term frequency for this document.
        $this->termfrequency[$docid] = $termcounts;

        // Update document frequency for each unique term.
        foreach ($uniqueterms as $term) {
            if (!isset($this->documentfrequency[$term])) {
                $this->documentfrequency[$term] = 0;
            }
            $this->documentfrequency[$term]++;
        }

        $this->totaldocuments++;
        $this->idfcalculated = false;
        $this->tfidfcache = []; // Invalidate cache.
    }

    /**
     * Calculates TF (Term Frequency) for a term in a specific document.
     *
     * @param string $term The term
     * @param int $docid Document identifier
     * @return float Term frequency (normalized)
     */
    public function calculate_tf(string $term, int $docid): float {
        if (!isset($this->termfrequency[$docid])) {
            return 0.0;
        }

        $tf = $this->termfrequency[$docid];
        $termcount = $tf[$term] ?? 0;

        if ($termcount === 0) {
            return 0.0;
        }

        $maxcount = max($tf);
        if ($maxcount === 0) {
            return 0.0;
        }

        // Normalized TF: frequency of term / frequency of most common term.
        return $termcount / $maxcount;
    }

    /**
     * Calculates IDF (Inverse Document Frequency) for a term.
     *
     * @param string $term The term
     * @return float Inverse document frequency
     */
    public function calculate_idf(string $term): float {
        if ($this->totaldocuments === 0) {
            return 0.0;
        }

        $df = $this->documentfrequency[$term] ?? 0;

        if ($df === 0) {
            return 0.0;
        }

        // IDF = log(total_documents / documents_containing_term).
        return log($this->totaldocuments / $df);
    }

    /**
     * Calculates TF-IDF score for a term in a specific document.
     *
     * @param string $term The term
     * @param int $docid Document identifier
     * @return float TF-IDF score
     */
    public function calculate_tfidf(string $term, int $docid): float {
        $tf = $this->calculate_tf($term, $docid);
        if ($tf === 0.0) {
            return 0.0;
        }

        $idf = $this->calculate_idf($term);
        return $tf * $idf;
    }

    /**
     * Returns the complete TF-IDF vector for a document.
     *
     * @param int $docid Document identifier
     * @return array<string,float> Map of term => TF-IDF score
     */
    public function get_tfidf_vector(int $docid): array {
        if (isset($this->tfidfcache[$docid])) {
            return $this->tfidfcache[$docid];
        }

        if (!isset($this->termfrequency[$docid])) {
            return [];
        }

        $vector = [];
        foreach (array_keys($this->termfrequency[$docid]) as $term) {
            $vector[$term] = $this->calculate_tfidf($term, $docid);
        }

        $this->tfidfcache[$docid] = $vector;
        return $vector;
    }

    /**
     * Calculates cosine similarity between two TF-IDF vectors.
     *
     * @param array<string,float> $vector1 First vector
     * @param array<string,float> $vector2 Second vector
     * @return float Cosine similarity (0.0 to 1.0)
     */
    public function cosine_similarity(array $vector1, array $vector2): float {
        if (empty($vector1) || empty($vector2)) {
            return 0.0;
        }

        $dotproduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        $allterms = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));

        foreach ($allterms as $term) {
            $v1 = $vector1[$term] ?? 0.0;
            $v2 = $vector2[$term] ?? 0.0;

            $dotproduct += $v1 * $v2;
            $magnitude1 += $v1 * $v1;
            $magnitude2 += $v2 * $v2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0.0 || $magnitude2 == 0.0) {
            return 0.0;
        }

        return $dotproduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Calculates TF-IDF vector for a query (new text not in corpus).
     *
     * @param array<int,string> $querytokens Array of query tokens
     * @return array<string,float> TF-IDF vector for the query
     */
    public function get_query_tfidf_vector(array $querytokens): array {
        if (empty($querytokens)) {
            return [];
        }

        $termcounts = array_count_values($querytokens);
        $maxcount = max($termcounts);

        $vector = [];
        foreach ($termcounts as $term => $count) {
            $tf = $maxcount > 0 ? $count / $maxcount : 0.0;
            $idf = $this->calculate_idf($term);
            $vector[$term] = $tf * $idf;
        }

        return $vector;
    }

    /**
     * Ranks documents by relevance to a query using TF-IDF cosine similarity.
     *
     * @param array<int,string> $querytokens Query tokens
     * @param int $limit Maximum number of results
     * @return array<int,array> Array of [docid => score] sorted by relevance
     */
    public function rank_documents(array $querytokens, int $limit = 10): array {
        if (empty($querytokens)) {
            return [];
        }

        $queryvector = $this->get_query_tfidf_vector($querytokens);
        if (empty($queryvector)) {
            return [];
        }

        $scores = [];
        foreach (array_keys($this->termfrequency) as $docid) {
            $docvector = $this->get_tfidf_vector($docid);
            $similarity = $this->cosine_similarity($queryvector, $docvector);
            if ($similarity > 0) {
                $scores[$docid] = $similarity;
            }
        }

        arsort($scores);

        if ($limit > 0) {
            $scores = array_slice($scores, 0, $limit, true);
        }

        return $scores;
    }

    /**
     * Returns the most important terms for a document based on TF-IDF.
     *
     * @param int $docid Document identifier
     * @param int $limit Number of top terms to return
     * @return array<string,float> Map of term => TF-IDF score
     */
    public function get_top_terms(int $docid, int $limit = 10): array {
        $vector = $this->get_tfidf_vector($docid);
        if (empty($vector)) {
            return [];
        }

        arsort($vector);

        return array_slice($vector, 0, $limit, true);
    }

    /**
     * Returns statistics about the corpus.
     *
     * @return array<string,mixed> Statistics
     */
    public function get_statistics(): array {
        $totalterms = count($this->documentfrequency);
        $avgtermsperдокument = 0;

        if ($this->totaldocuments > 0) {
            $termcounts = array_map('count', $this->termfrequency);
            $avgtermsperдокument = array_sum($termcounts) / count($termcounts);
        }

        return [
            'total_documents' => $this->totaldocuments,
            'total_unique_terms' => $totalterms,
            'avg_terms_per_document' => round($avgtermsperдокument, 2),
        ];
    }

    /**
     * Clears all data from the calculator.
     */
    public function reset(): void {
        $this->documentfrequency = [];
        $this->termfrequency = [];
        $this->tfidfcache = [];
        $this->totaldocuments = 0;
        $this->idfcalculated = false;
    }
}
