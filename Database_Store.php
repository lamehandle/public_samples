<?php

namespace app;

use PDO;
use PDOException;

class Database_Store implements Store
{
    private array $instances = [];
    public    PDO   $connection;
    public string   $host;
    public string   $dbname;
    public string   $port;
    public string   $charset;
    public string   $username;
    public string   $password;
    public string   $dsn;   //data source name
    public  array   $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        //always throw exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   //retrieve records as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,              //do not use emulate mode
        ];


    private function __construct($host = '', $dbname = '', $port = '', $charset = '', $username = '', $password = ''){

        $this->host     = $host     | 'localhost';
        $this->dbname   = $dbname   | 'receipts_tracker';
        $this->port     = $port     | '3306';
        $this->charset  = $charset  | 'utf8mb4';
        $this->username = $username | 'root';
        $this->password = $password | 'root';
        $this->dsn      = "mysql:host=$host;dbname=$dbname;port=$port;charset=$charset";

     }

    public static function get_instance($host, $dbname, $port, $charset, $username, $password) : Database_Store    {
        if(!isset($instances)) {
            return  $instances =  new Database_Store($host, $dbname, $port, $charset, $username, $password);
        }
        else {
            return $instances;
        }
     }

    public function connect($dsn = "",$username = "",$password = "",$options = []):PDO {
        try {
        echo "Connection Successful." . PHP_EOL;
        return $this->connection = new PDO($this->dsn,$this->username,$this->password,$this->options);

        } catch(PDOException $e){
            echo "Connection failed " . $e.getMessage();
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function insert_records( Receipt $receipt ) {
          try{
              $data = $receipt->create_sql();
              $connection= $this->connect();

              echo "Inserting records." . PHP_EOL;

              array_map(function($item) use ($connection) {
                  $data = $item->data();
                  $Statement = $connection->prepare($data['sql']);
                  $Statement->execute([
                      $data['id'],
                      $data["vendor"],
                      $data["item"],
                      $data["category"],
                      $data["price"],
                      $data['gst'],
                      $data['pst'],
                      $data["date"]
                      ]);

              }, $data);

          }catch(PDOException $e){
              echo $e->getMessage();
          }
          $connection = null;
          echo "Connection Closed." . PHP_EOL;
    }

     public function retrieve_records(string $sql ) : Receipt {
        try {
            $connection = $this->connect();
            $statement  = $connection->query($sql); //PDO statement object

            $receipt = new Receipt("ret_");

            while ( ( $record = $statement->fetch( PDO::FETCH_NAMED ) ) !== false ) {
               $receipt->line_items[] = new Line_Item($record['vendor'], $record['item'], $record['category'],
                   $record['price'], $record['gst'], $record['pst'], $record['date']);
            }
            echo "items retrieved..." . PHP_EOL;

//            print_r($receipt->line_items); //test output if needed.

            } catch(PDOException $e){
            echo $e->getMessage();
        }
        $connection = null;
        echo "Retrieval connection Closed." . PHP_EOL;
        return $receipt;
    }

    public function update_record(string $sql) : void{
        $connection = $this->connect();
        $connection->query( $sql );
    }

    public function delete_record_by_id(string $id){

        $sql = "DELETE FROM line_items WHERE  id = :id ";

        $connection = $this->connect();
        $statement  = $connection->prepare( $sql );
        $statement->execute( [ $id ] );

    }

}