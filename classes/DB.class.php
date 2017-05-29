<?php

class DB
{
  private $db;

  public function __construct($debug=false)
  {
    $this->db = new mysqli('localhost','comicUser','');
    $this->db->select_db('comics');
  }

 // execute a SINGLE query that will only return a single row from the database.  The result will be in $query_r.
  public function GetRow($query)
  {
    $query_q = $this->db->query($query);
    $query_r = $query_q->fetch_assoc();
    $query_q->free_result();

    while($this->db->more_results())
    {
      $this->db->next_result();
      $this->db->store_result();    // flush out that last bit
    }

    return $query_r;
  }

 // get a single value
  public function GetValue($query, $field)
  {
    $row = $this->GetRow($query);
    return $row[$field];
  }

 // execute a query
  public function Execute($query)
  {
    $query_q = $this->db->query($query);
  }

 // execute a SINGLE query that will return the results of an entire query the database.  The result will be in $query_r.
  public function GetArray($query)
  {
    $query_q = $this->db->query($query);
    while ($query_r = $query_q->fetch_assoc())
      $result[] = $query_r;

    $query_q->free_result();

    while($this->db->more_results())
    {
      $this->db->next_result();
      $this->db->store_result();    // flush out that last bit
    }

    if (isset($result))
      return $result;
    else
      return array();
  }

  public function escape($string)
  {
    return $this->db->escape_string($string);
  }

  public function InsertedID()
  {
    return $this->db->insert_id;
  }
}

$db = new DB();

?>
