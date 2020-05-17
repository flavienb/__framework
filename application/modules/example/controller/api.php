<?php

class example_controller_api extends __controller
{
    /**
     * @var example_model_articles
     */
    protected $datasource;

    public function init() {
        // initialize our model
        $this->datasource = new example_model_articles();
    }

    /**
     * Get all articles
     * @return array
     */
    public function getArticlesAction() {
        $articles = $this->datasource->loadArticles();
        return $articles;
    }

    /**
     * Insert a article, with parameters validation
     * @return array
     * @throws Exception
     */
    public function insertArticleAction() {
        $requiredParams = ['author', 'text'];
        if ($params = __request::checkParams($requiredParams)) {
            $article_id = $this->datasource->insertArticle($params['author'], $params['text']);

            return ['id' => $article_id];
        }

        __::bad_request('Missing parameters');
    }

    /**
     * Delete an article
     * @return array
     * @throws Exception
     */
    public function deleteArticleAction() {
        if (__request::has('id')) {
            $article_id = __request::get('id');
            $deletedCount = $this->datasource->deleteArticle($article_id);

            return ['deletedCount' => $deletedCount];
        }

        __::bad_request('Missing parameters');
    }

    /**
     * Example using a database transaction
     * @return array
     * @throws Exception
     */
    public function transactionAction() {
        try {
            $this->datasource->beginTransaction();

            $article_id = $this->datasource->insertArticle('John Smith', 'Nice writing');

            $this->datasource->deleteArticle($article_id);

            $this->datasource->commit();

            return ['success' => true];

        } catch(Exception $ex) {
            $this->datasource->rollBack();
            throw $ex;
        }
    }

}
