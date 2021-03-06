<?php
/**
 * @version		$Id: search.php 22338 2011-11-04 17:24:53Z github_bot $
 * @package		Joomla.Site
 * @subpackage	com_search
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.model');

/**
 * Search Component Search Model
 *
 * @package		Joomla.Site
 * @subpackage	com_search
 * @since 1.5
 */
class FieldsattachModelAdvancedSearch extends JModelList
{
	/**
	 * Sezrch data array
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Search total
	 *
	 * @var integer
	 */
	var $_total = null;

	/**
	 * Search areas
	 *
	 * @var integer
	 */
	var $_areas = null;

        /**
	 * Search areas
	 *
	 * @var integer
	 */
	var $_fields = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	var $_pagination = null;
        
        

	/**
	 * Constructor
	 *
	 * @since 1.5
	 */
	function __construct()
	{
		parent::__construct();

		//Get configuration
		$app	= JFactory::getApplication();
		$config = JFactory::getConfig();

		// Get the pagination request variables
		$this->setState('limit', $app->getUserStateFromRequest('com_fieldsattach.limit', 'limit', $config->get('list_limit'), 'int'));
		$this->setState('limitstart', JRequest::getVar('limitstart', 0, '', 'int'));
                
                //echo "limitttttt".$app->getUserStateFromRequest('com_fieldsattach.limit', 'limit', $config->get('list_limit'), 'int');

		// Set the search parameters
		$keyword                    = urldecode(JRequest::getString('searchword'));
		$match                      = JRequest::getWord('searchphrase', 'any');
		$ordering                   = JRequest::getWord('ordering', 'newest');
                $advancedsearchcategories   = JRequest::getVar('advancedsearchcategories');
		$this->setSearch($keyword, $match, $ordering);
                
                
                $this->setState('advancedsearchcategories', $advancedsearchcategories);
                
		//Set the search areas
		$areas = JRequest::getVar('areas');
		$this->setAreas($areas);

                //Set the search fields
		$fields = JRequest::getVar('fields'); 
		$this->setFields($fields);
	}

	/**
	 * Method to set the search parameters
	 *
	 * @access	public
	 * @param string search string
	 * @param string mathcing option, exact|any|all
	 * @param string ordering option, newest|oldest|popular|alpha|category
	 */
	function setSearch($keyword, $match = 'all', $ordering = 'newest')
	{
		if (isset($keyword)) {
                        if(empty($keyword)) $keyword = "";
			$this->setState('origkeyword', $keyword);
			if($match !== 'exact') {
				$keyword 		= preg_replace('#\xE3\x80\x80#s', ' ', $keyword);
			}
			$this->setState('keyword', $keyword);
		}

		if (isset($match)) {
			$this->setState('match', $match);
		}

		if (isset($ordering)) {
			$this->setState('ordering', $ordering);
		}
	}

	/**
	 * Method to set the search areas
	 *
	 * @access	public
	 * @param	array	Active areas
	 * @param	array	Search areas
	 */
	function setAreas($active = array(), $search = array())
	{
		$this->_areas['active'] = $active;
		$this->_areas['search'] = $search;
	}

        /**
	 * Method to set the search areas
	 *
	 * @access	public
	 * @param	array	Active areas
	 * @param	array	Search areas
	 */
	function setFields($active = array(), $search = array())
	{
		$this->_fields['active'] = $active;
		$this->_fields['search'] = $search;
	}

	/**
	 * Method to get weblink item data for the category
	 *
	 * @access public
	 * @return array
	 */
	function getData()
	{
                
                 
		// Lets load the content if it doesn't already exist
		if (empty($this->_data))
		{
                    
                        
                        $advancedsearchcategories   = JRequest::getVar('advancedsearchcategories'); 
                        $fieldsfilter   = JRequest::getVar('fields');  
                        
                        //echo  "FFF:".$fieldsfilter;
                        
                        $limit= $this->getState('limit', 40);
                        
                        //echo "limit:".$limit;
                        
			$areas = $this->getAreas();
                        $fields = $this->getFields();

			JPluginHelper::importPlugin('advancedsearch');
			$dispatcher = JDispatcher::getInstance(); 
                        
			$results = $dispatcher->trigger('onContentSearch', array(
				$this->getState('keyword'),
				$this->getState('match'),
				$this->getState('ordering'),
				$areas['active'],
                                $advancedsearchcategories,
                                $fieldsfilter,
                                $limit)
			);
                         

			$rows = array();
			foreach ($results as $result) {
				$rows = array_merge((array) $rows, (array) $result);
			}

			$this->_total	= count($rows);
                        //echo $this->getState('limit')." -- ".$this->_total;
			if ($this->getState('limit') > 0) {
				$this->_data	= array_splice($rows, $this->getState('limitstart'), $this->getState('limit'));
			} else {
				$this->_data = $rows;
			}
		}

		return $this->_data;
	}

	/**
	 * Method to get the total number of weblink items for the category
	 *
	 * @access public
	 * @return integer
	 */
	function getTotal()
	{
		return $this->_total;
	}

	/**
	 * Method to get a pagination object of the weblink items for the category
	 *
	 * @access public
	 * @return integer
	 */
	function getPagination()
	{
		// Lets load the content if it doesn't already exist
		if (empty($this->_pagination))
		{
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination($this->getTotal(), $this->getState('limitstart'), $this->getState('limit'));
		}

		return $this->_pagination;
	}

	/**
	 * Method to get the search areas
	 *
	 * @since 1.5
	 */
	function getAreas()
	{
		// Load the Category data
		if (empty($this->_areas['search']))
		{
			$areas = array();

			JPluginHelper::importPlugin('advancedsearch');
			$dispatcher = JDispatcher::getInstance();
			$searchareas = $dispatcher->trigger('onContentSearchAreas');

			foreach ($searchareas as $area) {
				if (is_array($area)) {
					$areas = array_merge($areas, $area);
                                           
				}
			}

			$this->_areas['search'] = $areas;
                      
		}

		return $this->_areas;
	}

         /**
	 * returns substring of characters around a searchword
	 *
	 * @param string The source string
	 * @param int Number of chars to return
	 * @param string The searchword to select around
	 * @return string
	 */
        function getFields()
	{ 
            // Load the Category data
            if (empty($this->_fields['search']))
            {
                    $fields = array();

                    $db = JFactory::getDbo();

                    $params = JComponentHelper::getParams('com_fieldsattach');
                    $enable_log_searches = $params->get('enabled');

                    $db = JFactory::getDbo();
                    $query = 'SELECT a.*, b.id as idgroup, b.title as titlegroup'
                    . ' FROM #__fieldsattach as a'
                    . ' INNER JOIN #__fieldsattach_groups as b'
                    . ' ON a.groupid = b.id'
                    . ' WHERE  a.searchable = 1 and a.published=1'
                    //. ' GROUP BY b.id'
                    . ' ORDER BY b.id'
                    ;
                    
                    $db->setQuery($query);
                    $fields = $db->loadObjectList();

                    if(count($fields)>0)
                    {
                        foreach ($fields as $tmp_fields) {
                            if (is_array($tmp_fields)) {
                                    $fields = array_merge($fields, $tmp_fields);
                            }
                        }
                    }



                    $this->_fields['search'] = $fields;
            }

            return $this->_fields ;  
	}
}
