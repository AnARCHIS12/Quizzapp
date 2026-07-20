<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Question Model managing multiple question types, answers, and evaluations
 */
class Question
{
    public static function findById(int $id): ?array
    {
        $question = Database::fetch("SELECT * FROM questions WHERE id = ?", [$id]);
        if ($question) {
            $question['answers'] = self::getAnswers($id);
        }
        return $question;
    }

    /**
     * Get all questions for a given quiz with their answers
     */
    public static function getByQuiz(int $quizId): array
    {
        $questions = Database::fetchAll(
            "SELECT * FROM questions WHERE quiz_id = ? ORDER BY sorting_order ASC, id ASC",
            [$quizId]
        );

        foreach ($questions as &$question) {
            $question['answers'] = self::getAnswers($question['id']);
        }

        return $questions;
    }

    /**
     * Get answers for a specific question
     */
    public static function getAnswers(int $questionId): array
    {
        return Database::fetchAll(
            "SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC",
            [$questionId]
        );
    }

    /**
     * Create question and its nested answers
     */
    public static function create(array $data, array $answers): int
    {
        Database::beginTransaction();
        try {
            Database::query(
                "INSERT INTO questions (quiz_id, type, question_text, media_url, points, explanation, sorting_order) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['quiz_id'],
                    $data['type'],
                    $data['question_text'],
                    $data['media_url'] ?? null,
                    $data['points'] ?? 10,
                    $data['explanation'] ?? null,
                    $data['sorting_order'] ?? 0
                ]
            );

            $questionId = (int) Database::lastInsertId();

            foreach ($answers as $ans) {
                Database::query(
                    "INSERT INTO answers (question_id, answer_text, is_correct, match_order, association_pair) 
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $questionId,
                        $ans['answer_text'],
                        $ans['is_correct'] ?? 0,
                        $ans['match_order'] ?? null,
                        $ans['association_pair'] ?? null
                    ]
                );
            }

            Database::commit();
            return $questionId;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Update question details and answers list
     */
    public static function update(int $id, array $data, array $answers): bool
    {
        Database::beginTransaction();
        try {
            Database::query(
                "UPDATE questions SET quiz_id = ?, type = ?, question_text = ?, media_url = ?, points = ?, 
                 explanation = ?, sorting_order = ? WHERE id = ?",
                [
                    $data['quiz_id'],
                    $data['type'],
                    $data['question_text'],
                    $data['media_url'] ?? null,
                    $data['points'] ?? 10,
                    $data['explanation'] ?? null,
                    $data['sorting_order'] ?? 0,
                    $id
                ]
            );

            // Rebuild answers
            Database::query("DELETE FROM answers WHERE question_id = ?", [$id]);

            foreach ($answers as $ans) {
                Database::query(
                    "INSERT INTO answers (question_id, answer_text, is_correct, match_order, association_pair) 
                     VALUES (?, ?, ?, ?, ?)",
                    [
                        $id,
                        $ans['answer_text'],
                        $ans['is_correct'] ?? 0,
                        $ans['match_order'] ?? null,
                        $ans['association_pair'] ?? null
                    ]
                );
            }

            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Delete question
     */
    public static function delete(int $id): bool
    {
        Database::query("DELETE FROM questions WHERE id = ?", [$id]);
        return true;
    }
}
