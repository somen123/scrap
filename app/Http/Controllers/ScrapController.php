<?php
namespace App\Http\Controllers;

use App\Exports\DataExport;


class ScrapController extends Controller{
	private $export;

	public function __construct()
	{
       $this -> export = new DataExport();
	}

    public function UniversityScrap()
    {
    	
    	$uri ="https://search.studyinaustralia.gov.au/course/search-results.html";
        $list =$this -> export -> scrapUniversity($uri);
       


    }

    public function CourseScrap()
    {

           
        $list =$this -> export -> scrapCourse("https://search.studyinaustralia.gov.au/course/search-results.html");
    }


    
} 

 ?>