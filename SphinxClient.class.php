<?php

/**
 * Class to interact with Sphinx using mysql protocol (SphinxQL)
 *
 * @author Martín Ernesto Barreyro, Marc St Raymond
 */

class HeardaboutSphinxClient
{

	private $select;
	private $from;
	private $limit;
	private $groupBy;
	private $kwdsToMatch;
	private $orderBy;
	private $where;
	private $extraMatch;
	
	private $conn = null;
	
	public function __construct()
	{
	  
	}
	
	private function getConnection()
	{
	  if($this->conn == null)
	  {
	      $pdo = new PDO(sfConfig::get('app_sphinx_dsn'));
	      $this->conn = Doctrine_Manager::getInstance()->openConnection($pdo, 'sphinx', false);
	  }
	  return $this->conn;
	}
    
	public function updateRtIndex($sphinxql = "")
	{        
		$this->getConnection()->execute($sphinxql);
	}

	public function updateMVA($index, $field, $value, $id)
	{        
		$sphinxql = 'UPDATE ' . $index . ' SET ' . $field . '=(' . $value . ') WHERE id=' . $id;
		$this->getConnection()->execute($sphinxql);
	}
    
	public function querySphinx($sphinxql = "")
	{
	  if($sphinxql == "")
	  {
			$sphinxql = $this->createQuery();
	  }
	  return $this->getConnection()->fetchAssoc($sphinxql);
	}
	
	public function getMetas()
	{
	  return $this->getConnection()->fetchAssoc("SHOW META;");
	}
	
	public function getTotalFound()
	{
	  $metas = $this->getMetas();
	  $total = 0;
	  foreach ($metas as $value)
	  {
			if($value['Variable_name'] == 'total_found')
			{
				$total = $value['Value'];
	  	}
	  }
	  return $total;
	}
	
	public function setFrom($from)
	{
	  $this->from = $from;
	  return $this;
	}
	
	public function setSelect($select)
	{
	  $this->select = $select;
	  return $this;
	}
     
	//takes a string with two numbers, offset and limit
	public function setLimit($limit)
	{
	  $this->limit = $limit;
	  return $this;
	}
	
	public function setGroupBy($groupBy)
	{
	  $this->groupBy = $groupBy;
	  return $this;
	}
	
	public function setMatch($kwds)
	{
	  $this->kwdsToMatch = $kwds;
	  return $this;
	}
	
	public function setExtraMatches($extraMatch)
	{
	  $this->extraMatch = $extraMatch;
	  return $this;
	}
	
	public function setWhere($where)
	{
	  $this->where = $where;
	  return $this;
	}
	
	public function setOrderBy($orderBy)
	{
	  $this->orderBy = $orderBy;
	  return $this;
	}
	
	public function getMatch()
	{
	  return $this->extraMatch;
	}
	
	public function getExtraMatch()
	{
	  return $this->extraMatch;
	}
    
	public function createQuery()
	{
	  
	  $sphinxql  = "SELECT {$this->select} ";
	  $sphinxql .= "FROM {$this->from} ";
	  
	  if($this->where || $this->kwdsToMatch || $this->extraMatch)
	  {
			$sphinxql .= "WHERE ";
			
			if($this->kwdsToMatch)
			{
				$sphinxql .= "MATCH('".$this->prepareWordsSearchToSphinx($this->kwdsToMatch)." {$this->extraMatch}') ";
				
			}
			elseif($this->extraMatch)
			{
				$sphinxql .= "MATCH('{$this->extraMatch}') ";
			}
			
			if($this->where)
			{
				if($this->kwdsToMatch || $this->extraMatch)
				{
					$sphinxql .= "AND {$this->where} ";
				}
				else
				{
					$sphinxql .= " {$this->where} ";
				}
			}			
	  }
   
	  if($this->groupBy)
	      $sphinxql .= "GROUP BY {$this->groupBy} ";
	  
	  if($this->orderBy)
	      $sphinxql .= "ORDER BY {$this->orderBy} ";
	      
	  if($this->limit)
	      $sphinxql .= "LIMIT {$this->limit} ";
	
	  return $sphinxql;
	}
    
    
	/**
	*  Function prepareSearchToSphinx
	*
	*  Logic to add "*" at the start and end of all words (separators are ".", "," and " ")
	*
	* @param <string> $query
	*/
	
	protected function prepareWordsSearchToSphinx($query_internal)
	{
	  $arrWords = array();
	  $word = "";
	  if ($query_internal)
	  {
	      
			for ($contChar = 0; $contChar < strlen($query_internal); $contChar++)
			{
			  if (in_array($query_internal[$contChar], array('.', ',', ' ')))
			  {
					if ($word && $word != "*")
					{
						$arrWords[] = $word . "*";
					}
					
					$word = "*";
					
					if ($query_internal[$contChar] != " ")
					{
				    $contChar++;
			  	}
			  }
			  else
			  {
					if ($contChar == 0)
					{
						$word = "*";
					}
			
					$word.=$query_internal[$contChar];
			
					if (strlen($query_internal) == $contChar + 1)
					{
						$arrWords[] = $word . "*";
						$word = "*";
					}
				}
			}
		}
	  
	  $query_internal = implode(" ", $arrWords);
	  
	  if (trim($query_internal) != "")
	  {
			$query_internal = "( " . $query_internal . " )";
		}
		
	  return $query_internal;
	}
}
