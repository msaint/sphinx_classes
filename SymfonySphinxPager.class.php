<?php

/**
 * Class that extends the symfony pager to work with sphinx query results
 *
 * @author Martín Ernesto Barreyro, Marc St Raymond
 */
class SymfonySphinxPager extends sfPager
{
  
	/**
	* Constructor
	* @param object         $class
	* @param integer        $maxPerPage
	* @param sfSphinxClient $sphinx
	*/
	public function __construct($class, HeardaboutSphinxClient $sphinx, $maxPerPage = 15)
	{
	  parent::__construct($class, $maxPerPage);
	  $this->sphinx = $sphinx;
	  $this->query = Doctrine::getTable($this->getClass())->createQuery();
	}

	/**
	* A function to be called after parameters have been set
	*/
	public function init()
	{
	  $hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
	  $maxRecordLimit = $this->getMaxRecordLimit();
	
	  $results = $this->sphinx->querySphinx();
	  if ($results === false)
	  {
			return;
	  }
	
	  $count = $this->sphinx->getTotalFound();
				
		//Set "number" of results
	  $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);
	
	  if (($this->getPage() == 0 || $this->getMaxPerPage() == 0))
	  {
	      $this->setLastPage(0);
	  } 
	  else
	  {
			$this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));
			
			$offset = ($this->getPage() - 1) * $this->getMaxPerPage();
			
			if ($hasMaxRecordLimit)
			{
				$maxRecordLimit = $maxRecordLimit - $offset;
				if ($maxRecordLimit > $this->getMaxPerPage())
				{
				    $limit = $this->getMaxPerPage();
				} else
				{
				    $limit = $maxRecordLimit;
				}
			} 
			else
			{
			    $limit = $this->getMaxPerPage();
			}
			$this->sphinx->setLimit("$offset, $limit");
	
	  }
	}
    
	/**
	* Return results for given page
	* @return Doctrine_Collection
	*/

	public function getResults()
	{
	  $sphinx_results = $this->sphinx->querySphinx();
	
	  //First we need to get the Ids
	  $ids = array();
	  $collection = array();
	  if (count($sphinx_results) > 0)
	  {         
			//Reformat the array of results so that we can use it in the query
			foreach ($sphinx_results as $value)
			{
				$ids[] = $value['id'];
			}
	
			// Then we retrieve the objects correspoding to the found Ids
			// Obviously update this query to fetch from the appropriate table...using 'Object' as a placeholder
	  	$q = Doctrine::getTable('Object')
	          ->createQuery('o')
	          ->whereIn('o.id', $ids)
	          ->orderBy('FIELD(o.id, ' . implode(',', $ids) . ')');
	  
	  	$collection = $q->execute();
	  }
	
		return $collection;
	}
    
	/* FORCED TO DEFINE THIS METHOD BECAUSE IT IS ABSTRACT IN THE PARENT CLASS (sfPager) */
	public function retrieveObject($offset)
	{} 
}

