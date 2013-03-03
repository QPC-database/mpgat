<?php

require 'gapi.class.php';

/**
 * Multiple Profile Google Analytics Tool - MPGAT
 * 
 * Copyright (c) 2012 Mario Rothauer
 * www.rothauerwebsites.com
 * http://github.com/mrothauer/mpgat
 *
 * @author Mario Rothauer <office@rothauerwebsites.com>
 * @version 1.0
 * @license MIT
 * 
 * - no warranty for anything, usage at own risk
 * 
 * - provides an overview of all your different google analytics profiles
 * - no more need to click around in google analytics just to check today's visitors of all projects
 * - provides a useful grid of your profiles
 * - highly customizeable
 * - well defined default values  
 * - metrics and dimensions can be found here: http://ga-dev-tools.appspot.com/explorer/
 * 
 * USAGE
 * 1) download gapi.class.php from http://code.google.com/p/gapi-google-analytics-php-interface and copy it in same folder
 * 2) rename config.default.php to config.php
 * 3) change the $profiles array to your profile data
 * 4) enable curl (if not already enabled) 
 * 5) call index.php
 * 6) click on a link on the top left corner
 * 
 * CAUTION
 * if you use this program for a lot of profiles with a lot of dimensions, a lot of
 * requests to google analytics are fired: you can get an error message for that and you will have
 * to wait some time in order to use this program again
 * i did not have any problems for about 20 profiles with 5 dimensions (average) => 100 requests, fired max 5 times a day
 * that's enough to keep track of what's going on on your sites
 * for detailed information use google anayltics web interface found on http://www.google.com/analytics  
 * 
 */
class MPGAT {
  
  /**
   * @var gapi
   */
  public $ga;
  
  /**
   * @var $string
   */
  public $email = '';
  
  /**
   * @var $string
   */
  public $password = '';
  
  /**
   * @var array
   */
  public $profiles = array();
  
  /**
   * @var array
   */
  public $defaultMetrics = array('visits','pageviews','uniquePageviews', 'pageviewsPerVisit', 'timeOnSite', 'avgTimeOnSite');
  
  /**
   * @var array
   */
  public $defaultSort = '-visits';
  
  /**
   * @var array
   */
  public $defaultFilter = '';
  
  /**
   * @var array
   */
  public $requests = array();
  
  /**
   * @var array
   */
  public $predefinedRequests = array();
  
  /**
   * @param string $email
   * @param string $password
   */
  public function __construct($email, $password) {
      $this->email = $email;
      $this->password = $password;
      $this->setPredefinedRequests();
  }
  
  public function connect() {
      $this->ga = new gapi($this->email, $this->password);
  }
  
  /**
   * @param string $name
   * @param result $result
   * @return string
   */
  public function getRequestOutput($key, $result) {
      
    switch($key) {
        case 'referer':
            $result = explode(' ',$result);
            $result = $result[0].$result[1];
            $link = 'http://'.$result;
            $result = '<a target="_blank" href="'.$link.'">'.$link.'</a>';
            break;
        case 'cities' :
            $result = '<a target="_blank" href="http://maps.google.at/maps?q='.urlencode($result).'">'.$result.'</a>';
            break;
        case 'keywords' :
            $result = '<a target="_blank" href="http://www.google.at/search?q='.urlencode($result).'">'.$result.'</a>';
            break;
        case 'pages' :
            $result = explode(' ',$result);
            $pagePath = $result[1];
            $result = $result[0].$result[1];
            $link = 'http://'.$result;
            $result = '<a target="_blank" href="'.$link.'">'.$pagePath.'</a>';
            break;
    }
    
    return $result;
        
  }
  
  /**
   * @param string $metric
   * @param int $value
   * @return string
   */
  public function formatMetric($metric, $value) {
      
      switch($metric) {
          case 'avgTimeOnSite':
          case 'timeOnSite':
          case 'pageviewsPerVisit':
              $value = '<span title="'.number_format($value / 60, 1, ',', '.').' min'.'">'.number_format($value, 1, ',', '.').'</span>';
              break;
      }
      
      return $value;
       
  }
  
  /**
   * @param int $profileId
   * @param array $dimensions
   * @param array $metrics
   * @param string $sort
   * @param string $filter
   * @param date $startDate
   * @param date $endDate
   * @return array
   */
  public function getReportData($profileId, $dimensions, $metrics, $sort, $filter, $startDate, $endDate) {
      
      $this->ga->requestReportData($profileId ,$dimensions ,$metrics ,$sort, $filter, $startDate, $endDate, 1 ,10000);
      $results = $this->ga->getResults();
      return $results;
      
  }
  
  /**
   * configures predefined requests which can be used
   * without further configuration in the config.php file
   */
  public function setPredefinedRequests() {
      
        $predefinedRequests = array(
            'keywords' =>
                array(
                    'dimensions' => array('keyword')
                   ,'name' => 'Keyword'
                   ,'viewFilter' => '(not set|not provided)'
                   ,'metrics' => $this->defaultMetrics
                   ,'sort' => $this->defaultSort
                   ,'filter' => $this->defaultFilter
               )
           ,'referer' =>
                array(
                    'dimensions' => array('source', 'referralPath')
                   ,'name' => 'Referer'
                   ,'viewFilter' => '(not set|google|bing|suche\.t\-online\.de|direct)'
                   ,'metrics' => $this->defaultMetrics
                   ,'sort' => $this->defaultSort
                   ,'filter' => $this->defaultFilter
                )
          ,'pages' =>
                array(
                   'dimensions' =>     array('hostname', 'pagePath')
                   ,'name' => 'Pages'
                   ,'metrics' => $this->defaultMetrics
                   ,'sort' => $this->defaultSort
                   ,'filter' => $this->defaultFilter
                )
          ,'events' =>
               array(
                   'dimensions' => array('eventCategory', 'eventAction', 'eventLabel')
                  ,'name' => 'Events'
                  ,'viewFilter' => 'not set'
                  ,'metrics' => array('totalEvents', 'uniqueEvents')
                  ,'sort' => '-totalEvents'
                  ,'filter' => $this->defaultFilter
               )
          ,'cities' =>
               array(
                   'dimensions' =>    array('city')
                  ,'name' => 'City'
                  ,'viewFilter' => 'not set'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
              )
          ,'countries' =>
               array(
                   'dimensions' => array('country')
                  ,'name' => 'Country'
                  ,'viewFilter' => 'not set'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
          ,'languages' =>
               array(
                   'dimensions' => array('language')
                  ,'name' => 'Language'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
          ,'screenResolutions' =>
               array(
                   'dimensions' => array('screenResolution')
                  ,'name' => 'Resolution'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
          ,'browsers' =>
                array(
                   'dimensions' => array('browser')
                  ,'name' => 'Browser'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
          ,'landingPages' =>
               array(
                   'dimensions' => array('landingPagePath')
                  ,'name' => 'Landing Page'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
          ,'exitPages' =>
               array(
                   'dimensions' => array('exitPagePath')
                  ,'name' => 'Exit Page'
                  ,'metrics' => $this->defaultMetrics
                  ,'sort' => $this->defaultSort
                  ,'filter' => $this->defaultFilter
               )
      );
      
      $this->predefinedRequests = $predefinedRequests;
       
  }
  
  /**
   * sets requests
   * @param int $profileId
   * @throws Exception
   */
  public function setRequests($profileId) {
      
      $this->requests[$profileId] = array();
      
      $profile = $this->profiles[$profileId];
      
      // set default requests
         if (isset($profile['predefinedRequests'])) {
             foreach($profile['predefinedRequests'] as $predefinedRequest) {
                 $this->requests[$profileId][$predefinedRequest] = $this->predefinedRequests[$predefinedRequest];
             }
         }
         
    if (!$this->requests[$profileId]['pages']) {
          throw new Exception('pages must be implemented in order to get added up total pageviews, visits and unique pageviews');
      }
         
      //set custom event requests
         if (isset($profile['customEventRequests'])) {
            $i=0;
            foreach($profile['customEventRequests'] as $customEventRequest) {
                $i++;
                // can be overridden in config.php
                $defaultCustomEventRequest = array(
                     'dimensions' => array('eventCategory', 'eventAction', 'eventLabel')
                    ,'viewFilter' => 'not set'
                    ,'metrics' => array('totalEvents', 'uniqueEvents')
                    ,'sort' => '-totalEvents'
                );
                $customEventRequest = array_merge($defaultCustomEventRequest, $customEventRequest);
                $this->requests[$profileId]['custom-event-request-' . $i] = $customEventRequest;
             }
         }

         //set custom requests
         if (isset($profile['customRequests'])) {
             $i=0;
             foreach($profile['customRequests'] as $customRequest) {
                 $i++;
                 $this->requests[$profileId]['custom-request-' . $i] = $customRequest;
             }
         }
             
  }
  
  /*
   * @return date
   */
  public function getToday() {
      return date('Y-m-d');
  }
  
  /**
   * @return string
   */
  public function getPeriodLinks() {
      $links = '
          <a href="?period=last-0">today - </a>
          <a href="?period=last-1">yesterday - </a>
          <a href="?period=last-2">2 days - </a>
          <a href="?period=last-7">7 days - </a>
          <a href="?period=last-30">30 days - </a>
          <a href="?period=last-100">100 days</a>';
      return $links;
  }
  
  /**
   * @param string $period (last-x)
   * @return date
   */
  public function getStartDate($period) {
      $period = explode('-', $period);
      $interval = (int) $period[1];
      $unformattedStartDate = strtotime ('-'.$interval. ' days' ,strtotime($this->getToday()));
      $startDate = date('Y-m-d', $unformattedStartDate);
      return $startDate;
  }
  
  /**
   * @return string
   */
  public function getWrapperWidth() {
      $px = count($this->profiles) * 312;
      return $px . 'px';
  }
  
  /**
   * @param int $profiles
   */
  public function setProfiles($profiles) {
    
      $this->profiles = $profiles;
    
      // TODD: does not belong in this method
    foreach($profiles as $profileId => $profile) {
        $this->setRequests($profileId);
    }
    
  }
  
  /**
   * @return array
   */
  public function getProfiles() {
    return $this->profiles;
  }
  
  /**
   * @return array
   */
  public function getRequests($profileId) {
    return $this->requests[$profileId];
  }

  /**
   * @return gapi
   */
  public function getGa() {
    return $this->ga;
  }
  
}

?>