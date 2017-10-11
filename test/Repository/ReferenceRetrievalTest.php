<?php

namespace LRC\Repository;

use PHPUnit\DbUnit\DataSet\YamlDataSet;

require_once 'DbTestCase3.php';

/**
 * Test cases for managed references, with automatic retrieval.
 *
 * @SuppressWarnings(PHPMD.UnusedLocalVariables)
 */
class ReferenceRetrievalTest extends DbTestCase3
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
     * Test single reference retrieval.
     */
    public function testSingleReferenceRetrieval()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // single result (without conditions)
            $question = $questions->fetchReferences()->find(null, 1);
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // re-insertion
            $question->id = null;
            $questions->save($question);
            $question2 = $questions->fetchReferences()->find(null, $question->id);
            $this->assertEquals($question2, $question);
            
            // update
            $question->published = date('Y-m-d H:i:s');
            $questions->save($question);
            $question2 = $questions->fetchReferences()->find(null, $question->id);
            $this->assertEquals($question2, $question);
            
            // single result (with conditions)
            $question = $questions->fetchReferences()->getFirst('id > 2');
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // single result (named reference)
            $question = $questions->fetchReferences(['user'])->find(null, 2);
            $user = $users->find(null, $question->userId);
            $this->assertInstanceOf(User::class, $question->user);
            $this->assertEquals($user, $question->user);
            
            // multiple results
            $allQuestions = $questions->fetchReferences()->getAll();
            foreach ($allQuestions as $ques) {
                $this->assertInstanceOf(User::class, $ques->user);
                $this->assertEquals($users->find(null, $ques->userId), $ques->user);
            }
            
            // with selection (hack)
            $this->assertEquals(
                $this->getConnection()->getRowCount('question'),
                $questions->fetchReferences()->count()
            );
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $questions->setManager(null);
            $answers->setManager(null);
            $question->setManager(null);
            $question2->setManager(null);
        }
    }
    
    
    /**
     * Test multiple reference retrieval #1.
     */
    public function testMultipleReferenceRetrieval1()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // single results (automatic references)
            $answer = $answers->fetchReferences()->find(null, 1);
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
            
            // single result (named references)
            $answer = $answers->fetchReferences(['question', 'user'])->find(null, 1);
            $question = $questions->find(null, $answer->questionId);
            $user = $users->find(null, $answer->userId);
            $this->assertInstanceOf(Question::class, $answer->question);
            $this->assertInstanceOf(User::class, $answer->user);
            $this->assertEquals($question, $answer->question);
            $this->assertEquals($user, $answer->user);
            
            // single result (named single reference)
            $answer = $answers->fetchReferences(['question'])->find(null, 1);
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
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $answers->setManager(null);
            $questions->setManager(null);
            $answer->question->setManager(null);
            $answer->setManager(null);
            $question->setManager(null);
        }
    }
    
    
    /**
     * Test multiple reference retrieval #2.
     */
    public function testMultipleReferenceRetrieval2()
    {
        try {
            list($users, $questions, $answers) = $this->getRepositories();
            
            // multiple results (automatic references)
            $allAnswers = $answers->fetchReferences()->getAll();
            foreach ($allAnswers as $answer) {
                $question = $questions->find(null, $answer->questionId);
                $user = $users->find(null, $answer->userId);
                $this->assertInstanceOf(Question::class, $answer->question);
                $this->assertInstanceOf(User::class, $answer->user);
                $this->assertEquals($question, $answer->question);
                $this->assertEquals($user, $answer->user);
                $answer->question->setManager(null);
            }
            
            // multiple results (named references)
            $allAnswers = $answers->fetchReferences(['user', 'question'])->getAll();
            foreach ($allAnswers as $answer) {
                $question = $questions->find(null, $answer->questionId);
                $user = $users->find(null, $answer->userId);
                $this->assertInstanceOf(Question::class, $answer->question);
                $this->assertInstanceOf(User::class, $answer->user);
                $this->assertEquals($question, $answer->question);
                $this->assertEquals($user, $answer->user);
                $answer->question->setManager(null);
            }
            
            // multiple results (named single reference)
            $allAnswers = $answers->fetchReferences(['question'])->getAll();
            foreach ($allAnswers as $answer) {
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
            }
        } finally {
            // clean up references to release database lock
            $users->setManager(null);
            $answers->setManager(null);
            $questions->setManager(null);
            $answer->question->setManager(null);
            $answer->setManager(null);
            $question->setManager(null);
        }
    }
}
