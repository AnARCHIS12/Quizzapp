<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Sélection et déduplication de questions (classique + duel).
 */
class QuestionSelectionService
{
    public static function normalizeText(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        return mb_strtolower($text);
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @return array<int, array<string, mixed>>
     */
    public static function deduplicateByText(array $questions): array
    {
        $seen = [];
        $result = [];

        foreach ($questions as $question) {
            $key = self::normalizeText((string)($question['question_text'] ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[] = $question;
        }

        return $result;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, int>
     */
    public static function getPlayedQuestionIds(array $userIds): array
    {
        $userIds = array_values(array_filter(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        try {
            $rows = Database::fetchAll(
                'SELECT DISTINCT question_id FROM user_question_history WHERE user_id IN (' . implode(',', $userIds) . ')'
            );
        } catch (\Exception $e) {
            return [];
        }

        return array_map(static fn(array $row): int => (int)$row['question_id'], $rows);
    }

    /**
     * @param array<int, int> $excludeIds
     * @param array<string, true> $excludeTexts normalized question texts
     * @return array<int, array<string, mixed>>
     */
    public static function fetchCandidatesForCategory(
        int $categoryId,
        array $excludeIds,
        array $excludeTexts,
        int $limit,
        bool $includeParentScope = true
    ): array {
        $excludeIds = array_values(array_unique(array_filter(array_map('intval', $excludeIds))));
        $notInClause = $excludeIds !== [] ? 'AND q.id NOT IN (' . implode(',', $excludeIds) . ')' : '';

        $rows = Database::fetchAll(
            "SELECT DISTINCT q.* FROM questions q
             JOIN quizzes quiz ON q.quiz_id = quiz.id
             JOIN categories c ON quiz.category_id = c.id
             WHERE (c.id = ? OR c.parent_id = ?) {$notInClause}
             ORDER BY RAND()
             LIMIT ?",
            [$categoryId, $categoryId, max($limit * 4, 12)]
        );

        if ($includeParentScope && count($rows) < $limit) {
            $extraRows = Database::fetchAll(
                "SELECT DISTINCT q.* FROM questions q
                 JOIN quizzes quiz ON q.quiz_id = quiz.id
                 JOIN categories c ON quiz.category_id = c.id
                 WHERE (
                    c.id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                    OR c.parent_id IN (SELECT parent_id FROM categories WHERE id = ? AND parent_id IS NOT NULL)
                    OR c.parent_id = ?
                 ) {$notInClause}
                 ORDER BY RAND()
                 LIMIT ?",
                [$categoryId, $categoryId, $categoryId, max($limit * 4, 12)]
            );

            $existingIds = array_column($rows, 'id');
            foreach ($extraRows as $row) {
                if (!in_array($row['id'], $existingIds, true)) {
                    $rows[] = $row;
                    $existingIds[] = $row['id'];
                }
            }
        }

        $picked = [];
        foreach ($rows as $row) {
            $textKey = self::normalizeText((string)$row['question_text']);
            if ($textKey === '' || isset($excludeTexts[$textKey])) {
                continue;
            }
            $picked[] = $row;
            $excludeTexts[$textKey] = true;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return $picked;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    public static function attachAnswers(array $questions): array
    {
        foreach ($questions as &$question) {
            $rawAnswers = Database::fetchAll(
                'SELECT id, answer_text, is_correct, match_order, association_pair FROM answers WHERE question_id = ?',
                [$question['id']]
            );
            $unique = [];
            foreach ($rawAnswers as $answer) {
                $key = trim((string)$answer['answer_text']);
                if (!isset($unique[$key])) {
                    $unique[$key] = $answer;
                }
            }
            $question['answers'] = array_values($unique);
        }
        unset($question);

        return $questions;
    }

    /**
     * @param array<int, array<string, mixed>|int> $questionsOrIds
     */
    public static function recordPlayedQuestions(int $userId, array $questionsOrIds): void
    {
        if ($userId <= 0) {
            return;
        }

        foreach ($questionsOrIds as $item) {
            $questionId = is_array($item) ? (int)($item['id'] ?? 0) : (int)$item;
            if ($questionId <= 0 || $questionId >= 100000) {
                continue;
            }
            try {
                Database::query(
                    'INSERT IGNORE INTO user_question_history (user_id, question_id) VALUES (?, ?)',
                    [$userId, $questionId]
                );
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @return array<int, array<string, mixed>>
     */
    public static function pickRandomForCategory(int $categoryId, ?int $userId, int $limit): array
    {
        $excludeIds = $userId ? self::getPlayedQuestionIds([$userId]) : [];
        $excludeTexts = [];
        $candidates = self::fetchCandidatesForCategory($categoryId, $excludeIds, $excludeTexts, $limit);

        if (count($candidates) < $limit) {
            $candidates = self::fetchCandidatesForCategory($categoryId, [], $excludeTexts, $limit, false);
        }

        shuffle($candidates);
        return self::attachAnswers(array_slice($candidates, 0, $limit));
    }
}
