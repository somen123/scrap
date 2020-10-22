<?php

namespace App\Exports;

use Goutte\Client;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class DataExport 
{
    public $universityArray= array();
    public $courseArray =  array();
    public $courseDetails = array();
    public $universityDetails =array();
    public $url;
    public $client;
    public $universityName;
    public $about;
    public $fp;
    public $location= array();
    public $list = array();
    public $summary,$opurtunity,$criteria;
    public $address = array();

    
    public function __construct()
    {
        
        $this -> client  = new Client();
        // $this -> client = new Client(HttpClient::create(['proxy' => 'https://3.26.5.206:8888']));
        }

    public function scrapUniversity($urlParam){

        $this -> url = $urlParam;
        $this -> fp = fopen('csv/universities.xls', 'w');
        $header  = array("University_Name","About","Address" );
        fputcsv($this -> fp, $header);
         echo "<pre>";


        /*************  Universities list  ********************/
        $crawler    = $this -> client->request('GET', $this -> url);
        $pagination = count($crawler->filter('.hid-ph'));
        $pagination = $pagination+1;
         
        for($i =1 ; $i<= $pagination+1 ; $i++) {
            

            $crawler    = $this -> client->request('GET', $this -> url."?pageno=".$i);

            $crawler->filter('h2 > a')->each(function ($node) {
                // if (strpos($node->text(), 'University') !== false) {
                        $this -> universityArray ['name'][] = $this -> universityName=$node->text();
                        
                        $link = $node->link();
                        $uri  = $link->getUri();
                         
                        $crawler = $this -> client->request('GET', $uri);
                          
                        $crawler  -> filter('.cr_mid p')-> each(function($node){
                                $this -> about =  $node -> text();
                        });
                         $addressCount = $crawler  -> filter('address')-> count();
                         // echo $addressCount;
                        $crawler  -> filter('address')-> each(function($node){
                                $this -> location[] =$node -> text();
                        });
                        $address = implode("\n",array_slice($this -> location, -$addressCount, $addressCount));

                 /*************  Course List ********************/
                        $courseLink = $crawler->filter('.enq > a')->link();
                        $courseUri  = $courseLink -> getUri($courseLink);

                         $courseList = $this -> scrapCourse($courseUri);

                        
                         $this -> list= array(
                                    
                                $this -> universityName,
                                $this -> about,
                                 $address, 
                                 $courseList
                                    
                                );

                         // fputcsv($this -> fp, $this -> list);
                       
        });

            
        }

        print_r($this -> list);
        
    }

    

    public function scrapCourse($urlParam){ 

        //$this -> url = $urlParam;
         $this -> url ="https://search.studyinaustralia.gov.au/course/provider-results.html?institutionid=72206";
        

        $client     = new Client();
        $crawler    = $client->request('GET', $this -> url);
        echo "<pre>";
        
        $pagination = count($crawler->filter('div.artPg > ul > li'));
         print_r($pagination) ;die;
        
        
        if($pagination >=0) {

                for($i =0 ; $i< $pagination+1; $i++) {
                    $this -> summary="";
                    $this -> opurtunity="";
                    $this -> criteria="";
                    $this -> address =array();
                    $crawler    = $client->request('GET', $this -> url.'&pageno='.$i);
                    $crawler->filter('h3.crs_tit > a')->each(function ($node) {
                       
                           $courseTitle = $node->text()."\n";
                          
                           $link = $node->link();
                           $uri  = $link->getUri();
                           $crawler = $this -> client->request('GET', $uri);
                           $crawler -> filter(".cr_mid > p")->each(function($node, $i){
                                 
                                  $this -> summary = $this ->summary."\n".$node->text()."\n"; 
                                  // print_r($this -> summary);
                           });
                      
                           $crawler -> filter(".fl_w100")->each(function($node, $i){
                               
                                $this -> opurtunity= $this -> opurtunity."\n".$node->text()."</br>"; 
                                // print_r($this -> opurtunity);
                           });

                           $crawler -> filter(".fl")->each(function($node){
                                  
                                   $this-> criteria=$this-> criteria."\n". $node->text();
                                    // print_r($this-> criteria)."\n"; 
                           });
                           $addressCount = $crawler  -> filter('address')-> count();
                         
                           $crawler -> filter("address")->each(function($node){

                                 $this -> address[]= $node->text();
                                 // print_r($address)."\n"; 
                           });
                           $address = implode("\n",array_slice($this -> address, -$addressCount, $addressCount));
                           $this -> courseArray [] = array(
                                     "title"      => $courseTitle,
                                     "summary"     => $this -> summary,
                                     "oppurtunity" => $this -> opurtunity,
                                     "criteria"    => $this -> criteria,
                                     "address"     => $address
   
                           );
                      
                    });
                    
                    

                }
        }

         return $this -> courseArray;
    }
}
?>
