<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase3.php';

/**
 * Test cases for managed references, with automatic retrieval.
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariables)
 */
class SoftReferenceRetrievalTest extends DbTestCase3
{
    private $manager;
    
    
    private function getRepositories()
    {
        $db = (new \Anax\Database\DatabaseQueryBuilder())->configure('db3.php');
        $this->manager = new RepositoryManager();
        return [
            $this->manager->createRepository(User::class, ['db' => $db, 'type' => 'db', 'table' => 'user']),
            $this->manager->createRepository(Question::class, ['db' => $db, 'type' => 'db-soft', 'table' => 'question']),
            $this->manager->createRepository(Answer::class, ['db' => $db, 'table' => 'answer'])
        ];
    }
    
    
    public function tearDown()
    {
        unset($this->manager);
        parent::tearDown();
    }
    
    
    public function getDataSet()
    {
        return new YamlDataSet(ANAX_APP_PATH . '/test3.yml');
    }
    
    
    /**
     * Test automatic reference retrieval, taking soft-deletion into account.
     */
    public function testAutomaticReferenceRetrieval()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // single result (no soft-deletion)
            $question = $questions->fetchReferences(true, true)->findSoft(null, 1);
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // re-insertion
            $question->id = null;
            $questions->save($question);
            $question2 = $questions->fetchReferences(true, true)->find(null, $question->id);
            $this->assertEquals($question2, $question);
            
            // update
            $question->published = date('Y-m-d H:i:s');
            $questions->save($question);
            $question2 = $questions->fetchReferences(true, true)->find(null, $question->id);
            $this->assertEquals($question2, $question);
            
            // single result (automatic non-deleted reference)
            $answer = $answers->fetchReferences(true, true)->findSoft(null, 3);
            $question = $questions->find(null, $answer->questionId);
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(Question::class, $answer->question);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertEquals($question, $answer->question);
            $this->assertEquals($user, $answer->user);
            
            // secondary reference
            $user2 = $question->user;
            $this->assertInstanceOf(User::class, $user2);
            $this->assertEquals($users->find(null, $question->userId), $user2);
            
            // single result (automatic soft-deleted reference)
            $answer = $answers->fetchReferences(true, true)->findSoft(null, 4);
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertNull($answer->question);
            $this->assertEquals($user, $answer->user);
            
            // single result (automatic non-deleted reference, with condition and ordering)
            $question = $questions->fetchReferences(true, true)->getFirstSoft('id <= 3', [], 'published DESC');
            $this->assertEquals(2, $question->id);
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // single result (automatic soft-deleted reference, with ordering)
            $answer = $answers->fetchReferences(true, true)->getFirstSoft(null, [], 'questionId DESC');
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertNull($answer->question);
            $this->assertEquals($user, $answer->user);
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $questions->setManager(null);
            $answers->setManager(null);
            if (!is_null($answer->question)) {
                $answer->question->setManager(null);
            }
            $answer->setManager(null);
            $question->setManager(null);
            $question2->setManager(null);
        }
    }
    
    
    /**
     * Test named reference retrieval, taking soft-deletion into account.
     */
    public function testNamedReferenceRetrieval()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // single result (no soft-deletion)
            $question = $questions->fetchReferences(['user'], true)->findSoft(null, 1);
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // single result (named non-deleted reference)
            $answer = $answers->fetchReferences(['question'], true)->findSoft(null, 3);
            $question = $questions->find(null, $answer->questionId);
            $this->assertInstanceOf(Question::class, $answer->question);
            $this->assertEquals($question, $answer->question);
            $exception = null;
            try {
                $user = $answer->user;
            } catch (\Exception $ex) {
                $exception = $ex;
            }
            $this->assertInstanceOf(RepositoryException::class, $exception);
            
            // single result (named soft-deleted reference)
            $answer = $answers->fetchReferences(['question'], true)->findSoft(null, 4);
            $this->assertNull($answer->question);
            $exception = null;
            try {
                $user = $answer->user;
            } catch (\Exception $ex) {
                $exception = $ex;
            }
            $this->assertInstanceOf(RepositoryException::class, $exception);
            
            // single result (named non-deleted references)
            $answer = $answers->fetchReferences(['question', 'user'], true)->findSoft(null, 3);
            $question = $questions->find(null, $answer->questionId);
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(Question::class, $answer->question);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertEquals($question, $answer->question);
            $this->assertEquals($user, $answer->user);
            
            // single result (named soft-deleted references)
            $answer = $answers->fetchReferences(['user', 'question'], true)->findSoft(null, 4);
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertNull($answer->question);
            $this->assertEquals($user, $answer->user);
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $questions->setManager(null);
            $answers->setManager(null);
            $question->setManager(null);
            if (!is_null($answer->question)) {
                $answer->question->setManager(null);
            }
            $answer->setManager(null);
        }
    }
    
    
    /**
     * Test multiple reference retrieval, taking soft-deletion into account.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function testMultipleReferenceRetrieval()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // multiple results (automatic references)
            $allAnswers = $answers->fetchReferences(true, true)->getAllSoft();
            foreach ($allAnswers as $answer) {
                if (!is_null($answer->question)) {
                    $this->assertInstanceOf(Question::class, $answer->question);
                }
                $this->assertInstanceOf(User::class, $answer->user);
                $question = $questions->findSoft(null, $answer->questionId);
                $this->assertEquals($question, $answer->question);
                $this->assertEquals($users->find(null, $answer->userId), $answer->user);
            }
            
            // multiple results (named references)
            $allAnswers = $answers->fetchReferences(['user', 'question'], true)->getAllSoft();
            foreach ($allAnswers as $answer) {
                if (!is_null($answer->question)) {
                    $this->assertInstanceOf(Question::class, $answer->question);
                }
                $this->assertInstanceOf(User::class, $answer->user);
                $question = $questions->findSoft(null, $answer->questionId);
                $this->assertEquals($question, $answer->question);
                $this->assertEquals($users->find(null, $answer->userId), $answer->user);
            }
            
            // multiple results (named references, with condition and ordering)
            $allAnswers = $answers->fetchReferences(['question', 'user'], true)->getAllSoft('userId = ?', [3], 'questionId ASC');
            $this->assertEquals(3, $allAnswers[1]->id);
            foreach ($allAnswers as $answer) {
                if (!is_null($answer->question)) {
                    $this->assertInstanceOf(Question::class, $answer->question);
                }
                $this->assertInstanceOf(User::class, $answer->user);
                $question = $questions->findSoft(null, $answer->questionId);
                $this->assertEquals($question, $answer->question);
                $this->assertEquals($users->find(null, $answer->userId), $answer->user);
            }
            
            // multiple results (named single reference)
            $allAnswers = $answers->fetchReferences(['question'], true)->getAllSoft();
            foreach ($allAnswers as $answer) {
                if (!is_null($answer->question)) {
                    $this->assertInstanceOf(Question::class, $answer->question);
                }
                $question = $questions->findSoft(null, $answer->questionId);
                $this->assertEquals($question, $answer->question);
                $exception = null;
                try {
                    $user = $answer->user;
                } catch (\Exception $ex) {
                    $exception = $ex;
                }
                $this->assertInstanceOf(RepositoryException::class, $exception);
            }
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $answers->setManager(null);
            $questions->setManager(null);
            if (!is_null($answer->question)) {
                $answer->question->setManager(null);
            }
            $answer->setManager(null);
            if ($question) {
                $question->setManager(null);
            }
        }
    }
}
