<?php

class example_model_articles extends __model
{
    protected $database = 'MY_DATABASE'; // configuration specified in database.php conf file

    public function init()
    {
        // method called before any other
    }

    public function loadArticles() {
        $prep = $this->connexion->prepare("
            SELECT * 
            FROM articles 
            WHERE deleted IS NULL");
        $prep->execute(array(
            'author' => 'myself'
        ));
        return $prep->fetchAll();
    }

    public function insertArticle($author, $text) {
        $prep = $this->connexion->prepare("
            INSERT INTO articles
            (date_create, author, text)
            VALUES
            (UNIX_TIMESTAMP(), :author, :text)");
        $prep->execute(array(
           'author' => $author,
           'text' => $text
        ));

        return $this->connexion->lastInsertId();
    }

    public function deleteArticle($article_id) {
        $prep = $this->connexion->prepare("
            UPDATE articles 
            SET deleted = UNIX_TIMESTAMP() 
            WHERE id = :id AND deleted IS NULL");
        $prep->execute(array(
            'id' => $article_id
        ));

        return $prep->rowCount();
    }

}
