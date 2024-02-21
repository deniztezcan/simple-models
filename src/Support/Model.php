<?php

namespace DenizTezcan\SimpleModels;

use Exception;
use mysqli;

class Model
{
    private mysqli $connection;
    protected string $table;
    protected string $query_text = "";
    protected $query_obj;
    protected Collection $parameters;
    protected array $response;

    public function __construct()
    {
        $this->connection = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWD'], $_ENV['DB_DB'], $_ENV['DB_PORT']);

        if($this->connection->connect_error){
            throw new Exception("Error connecting to the database");
        }
        $this->connection->set_charset('utf8');
        $this->parameters = new Collection();
    }

    private function appendQuery(string $string)
    {
        if(strlen($this->query_text) > 0){
            $this->query_text.= " ".$string;
        }else{
            $this->query_text = $string;
        }
    }

    private function prependQuery(string $string)
    {
        if(strlen($this->query_text) > 0){
            $this->query_text = $string." ".$this->query_text;
        }else{
            $this->query_text = $string;
        }
    }
    public function count(): self
    {
        $this->appendQuery("SELECT COUNT(*)");

        $select_arguments = "";
        $select_arguments = ltrim($select_arguments, ",");
        $this->appendQuery($select_arguments);

        $this->appendQuery("FROM");
        $this->appendQuery("".$this->table."");
        return $this;
    }
    public function sum(string $name): self
    {
        $this->appendQuery("SELECT SUM($name)");

        $select_arguments = "";
        $select_arguments = ltrim($select_arguments, ",");
        $this->appendQuery($select_arguments);

        $this->appendQuery("FROM");
        $this->appendQuery("".$this->table."");
        return $this;
    }

    public function where(
        string $name, 
        string $operator = '=', 
        string $value
    ): self
    {
        if(strpos($this->query_text, 'WHERE') !== false) {
            $this->appendQuery("AND ".$name." ".$operator." ?");
        }else{
            $this->appendQuery("WHERE ".$name." ".$operator." ?");
        }
         $this->parameters->set(uniqid(), array(gettype($value), $value));
        return $this;
    }

    public function select(...$args): self
    {
        $this->appendQuery("SELECT");

        $select_arguments = "";
        foreach($args as $arg) {
            $select_arguments.= ", ".$arg;
        }
        $select_arguments = ltrim($select_arguments, ",");
        $this->appendQuery($select_arguments);

        $this->appendQuery("FROM");
        $this->appendQuery("".$this->table."");
        return $this;
    }

    public function selectDistinct(...$args): self
    {
        $this->appendQuery("SELECT DISTINCT");

        $select_arguments = "";
        foreach($args as $arg) {
            $select_arguments.= ", ".$arg;
        }
        $select_arguments = ltrim($select_arguments, ",");
        $this->appendQuery($select_arguments);

        $this->appendQuery("FROM");
        $this->appendQuery("".$this->table."");
        return $this;
    }

    private function prepareQuery(array $args = [])
    {
        $this->query_obj = $this->connection->prepare($this->query_text);

        if(strlen($this->connection->error) > 0){
            var_dump($this->connection->error);
            echo "<hr>".$this->query_text;exit;
        }

        if(count($args)>0){
            $type_string = "";
            $value_string = "";
            $values = array();
            $count = 0;

            foreach ($args as $type => $value) {
                switch ($value[0]) {
                    case 'integer':
                        $type_string.= "i";
                        break;
                    case 'string':
                        $type_string.= "s";
                        break;
                    case 'double':
                        $type_string.= "d";
                        break;
                    default:
                        $type_string.= "s";
                        break;
                }
                $value_string.= '$values['.$count.'],';
                $values[] = $value[1];
                $count++;
            }

            $value_string = rtrim($value_string, ",");
            eval('$this->query_obj->bind_param("'.$type_string.'", '.$value_string.');');
        }
    }

    private function executeQuery()
    {
        $this->query_obj->execute();
    }

    private function getResult()
    {
        $result = $this->query_obj->get_result();
        $this->response = $result->fetch_all(MYSQLI_ASSOC);
    }

    public function get()
    {
        $this->prepareQuery($this->parameters->all());
        $this->executeQuery();
        $this->getResult();
        $this->cleanup();
        return $this->response;
    }

    public function first()
    {
        $result = $this->get();

        if(isset($result[0])){
            return $result[0];
        }

        return null;
    }

    public function orderBy(string $name, string $orderBy = "ASC"): self
    {
        if(strpos($this->query_text, 'ORDER BY') !== false) {
            $this->appendQuery(", ".$name." ".$orderBy);
        }else{
            $this->appendQuery("ORDER BY ".$name." ".$orderBy);
        }
        return $this;
    }

    private function cleanup()
    {
        $this->connection->close();
    }

}
