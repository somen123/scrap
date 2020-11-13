<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use KubAT\PhpSimple\HtmlDomParser;

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
    public $location,$uni_link= array();
    public $list = array();
    public $summary,$opurtunity,$criteria,$spreadsheet,$objWorkSheet,$objWorkSheet1,$institution_type,$code,$logo,$fee,$study_mode,$duration,$start_date,$ielts,$pte,$tofel_ibt,$tofel_pbt,$gre,$gmat;
    public $address = array();
    public $ucount,$key,$courseCount =0;
    public $cell =["A","B","C","D","E","F","G","H","I"];

    
        public function __construct()
        {

        }
    
        public function index()
        {
            $pagination = 10;//$pagination+1;
        
            /*** Creating default worksheet using spredsheet CLASSS OBJECT */
            $this ->spreadsheet = new Spreadsheet();

            /*** Creating a new worksheet , after the default sheet  */
            $this ->objWorkSheet = $this ->spreadsheet -> createSheet();
            $this ->objWorkSheet1 = $this ->spreadsheet -> createSheet(2);
            $this ->ucount =2 ;
            $this ->key =1;
            $this ->courseCount =2;
            for($i =1 ; $i<=$pagination; $i++) {
                if($i==1){
                     $uri = 'https://search.studyinaustralia.gov.au/course/search-results.html';
                } else {
                      
                    $uri = "https://search.studyinaustralia.gov.au/course/search-results.html?pageno=$i";
                }
            
            $scraped_page = $this -> make_request($uri);
            
            
            $dom = HtmlDomParser::str_get_html($scraped_page);
            
            foreach ($dom->find('h2 > a') as $element) {
                $this -> universityArray ['name'][] = $this -> universityName=$element->innertext; 
               
                $link =$element->href;
                $uri  = "https://search.studyinaustralia.gov.au/".$link;
                $crawler = $this -> make_request($uri);
                $dom = HtmlDomParser::str_get_html($crawler);
                        
                foreach ($dom->find('p.mt10 > span') as $element) { $this -> code = $element -> innertext; } 
                foreach ($dom->find('p.crs_cd > span') as $element) { $this -> institution_type =$element -> innertext; } 
                foreach ($dom->find('.cr_mid p') as $element) {  $this -> about =$element -> innertext;  } 
                foreach ($dom->find('img.lazy-loaded') as $element) { $this -> logo = $element -> src;} 
                foreach ($dom->find('.enq > a') as $key => $element) { $this -> uni_link[] = $element -> href; } 
               
                /**Create a first default sheet  */
                $this ->spreadsheet -> setActiveSheetIndex(0);
                $this ->spreadsheet -> getActiveSheet()-> setCellValue("A1","University Name") -> setCellValue("B1","About") ->setCellValue("C1","Institution_type")->setCellValue("D1","Code")->setCellValue("E1","Image")->setCellValue("F1","Website Link") ;
                $this ->spreadsheet -> getActiveSheet()-> setCellValue("A$this->ucount",$this -> universityName) -> setCellValue("B$this->ucount",$this -> about) ->setCellValue("C$this->ucount",$this -> institution_type)->setCellValue("D$this->ucount",$this -> code)->setCellValue("E$this->ucount",$this -> logo)->setCellValue("F$this->ucount",$this -> uni_link[0]) ;

                /**Rename defaul sheet name */
                $this ->spreadsheet -> getActiveSheet()-> setTitle('Universities');

                 /***************Adress***************** */
                        
                $addressCount = count($dom  -> find('address'));
                        //   echo $addressCount;die;
                foreach ($dom  -> find('address') as $node) {
                    $this -> location=explode(PHP_EOL,trim($node ->plaintext,PHP_EOL));//$node -> text();
                    
                    // Add some data to the second sheet,
                   $this ->spreadsheet -> setActiveSheetIndex(1);
                   $this ->spreadsheet -> getActiveSheet()-> setCellValue("A$this->key",$this -> universityName);
                   if(count($this -> location)==7){
                       
                       $this ->spreadsheet -> getActiveSheet()-> setCellValue("B$this->key", $this ->location[0])-> setCellValue("C$this->key", $this ->location[1])-> setCellValue("D$this->key", $this ->location[2])-> setCellValue("E$this->key", $this ->location[3])-> setCellValue("F$this->key", $this ->location[4])-> setCellValue("G$this->key", $this ->location[5])-> setCellValue("H$this->key", $this ->location[6]);
                      
                   } else if (count($this -> location)==6){
                       
                       $this ->spreadsheet -> getActiveSheet()-> setCellValue("B$this->key", $this ->location[0])-> setCellValue("C$this->key", "")-> setCellValue("D$this->key", $this ->location[1])-> setCellValue("E$this->key", $this ->location[2])-> setCellValue("F$this->key", $this ->location[3])-> setCellValue("G$this->key", $this ->location[4])-> setCellValue("H$this->key", $this ->location[5]);
                                       
                       
                   } else {
                       $this ->spreadsheet -> getActiveSheet()-> setCellValue("B$this->key", $this ->location[0])-> setCellValue("C$this->key", "")-> setCellValue("D$this->key", "")-> setCellValue("E$this->key", $this ->location[1])-> setCellValue("F$this->key", $this ->location[2])-> setCellValue("G$this->key", $this ->location[3])-> setCellValue("H$this->key", $this ->location[4]);
                      
                   }
                    
                    
                    // Rename Second Sheet
                    $this ->spreadsheet -> getActiveSheet()-> setTitle("Address");
                    
                        $this -> key ++;
                } 

                /***************************************************  Course List *************************************************************************/

                $courseLink = $dom->find('.enq > a')[0]->href; //->link();
                
                $course_pagination = 10;

                for($i =1; $i< $course_pagination; $i++) {
                    $this -> summary="";
                    $this -> opurtunity="";
                    $this -> criteria="";
                    $this -> address =array();
                    $uri= $this -> url.'&pageno='.$i;
                    if($i==1){
                        $courseUri  ="https://search.studyinaustralia.gov.au/$courseLink";
                
                   } else {
                         
                    $courseUri  ="https://search.studyinaustralia.gov.au/$courseLink&pageno=$i";
                   }
               
               
                    $crawler    = $this ->make_request($courseUri);
                    $course_dom = HtmlDomParser::str_get_html($crawler);
                    foreach($course_dom -> find('h3.crs_tit > a')as $course_node) { 
                                    
                        $courseTitle = $course_node->text()."\n";
                       
    
                        $link = $course_node->href;
                        $uri  = "https://search.studyinaustralia.gov.au".$link;
                        
                        
                        $crawler = $this -> make_request($uri);
                        $course_detail_dom = HtmlDomParser::str_get_html($crawler);
                        
                        foreach($course_detail_dom -> find(".cr_mid > p") as $summaries){
                            $this -> summary = $this ->summary."\n".$summaries -> plaintext."\n"; 
                        } 

                        
                        $course = count($course_detail_dom  -> find('div.cr_mid > p.crs_txt')); 
                        if($course>1){
                            // foreach($course_detail_dom  -> find('div.cr_mid > p.crs_txt') as $texts){
                                $this -> code = explode(":",$course_detail_dom->find('div.cr_mid > p')[0] ->innertext)[1]; 

                                $this -> institution_type = explode(":",$course_detail_dom->find('div.cr_mid > p')[1] ->innertext)[1]; 

                            // }
                                
                        
                            } else {
                                $this -> code =" ";
                                $this -> institution_type = explode(":",$course_detail_dom->find('div.cr_mid > p')[0] ->innertext)[1];
                        
                            }
                        
                            foreach($course_detail_dom -> find("div.tb_cl >.fl_w100") as $i =>$node){
                            if($i==0) $this -> fee = explode(":",$node->innertext)[1]; 
                            if($i==1) $this -> start_date = explode(":",$node->innertext)[1];
                            if($i==2) $this -> duration = explode(":",$node->innertext)[1];
                            if($i==3) $this -> study_mode = explode(":",$node->innertext)[1];

                            
                            $this -> opurtunity= $this -> opurtunity."\n".$node->innertext."</br>"; 
                                
                            };

                        
                            foreach($course_detail_dom -> find(".fl") as $node){
                                
                                $this-> criteria=$this-> criteria."\n". $node->innertext;

                                $string_val = $this -> criteria;
                                $NumericPattern = '/(\d*\.?\d+)/';
                                
                                    $IELTS = substr($string_val, strpos($string_val, "IELTS"));
                                            preg_match($NumericPattern, $IELTS, $IELTSmatches);
                                            $IELTS_score = isset($IELTSmatches[1])?$IELTSmatches[1]:0;
                                    $PTE = substr($string_val, strpos($string_val, "PTE"));
                                            preg_match($NumericPattern, $PTE, $PTEmatches);
                                            $PTE_score = isset($PTEmatches[1])?$PTEmatches[1]:0;
                                    $ISLPR = substr($string_val, strpos($string_val, "ISLPR"));
                                            preg_match($NumericPattern, $ISLPR, $ISLPRmatches);
                                            $ISLPR_score = isset($ISLPRmatches[1])?$ISLPRmatches[1]:0;
                                    $CAMBRIDGE = substr($string_val, strpos($string_val, "Cambridge"));
                                            preg_match($NumericPattern, $CAMBRIDGE, $CAMBRIDGEmatches);
                                            $CAMBRIDGE_score = isset($CAMBRIDGEmatches[1])?$CAMBRIDGEmatches[1]:0;
                                    $TOEFL = substr($string_val, strpos($string_val, "TOEFL"));
                                    $TOEFL_PBT_score ='';
                                    $TOEFL_IBT_score='';
                                        
                                        if( (strpos($TOEFL, "IBT")) || (strpos($TOEFL, "internet"))  ){
                                            if(strpos($TOEFL, "IBT")){$StrPosVal = 'IBT';}elseif(strpos($TOEFL, "internet")){$StrPosVal = 'internet';}
                                            $IBT = substr($TOEFL, strpos($TOEFL, "$StrPosVal"));
                                                preg_match($NumericPattern, $IBT, $TOEFL_IBTmatches);
                                                $TOEFL_IBT_score = isset($TOEFL_IBTmatches[1])?$TOEFL_IBTmatches[1]:0;
                                        } 
                                        if( (strpos($TOEFL, "PBT")) || (strpos($TOEFL, "paper")) ){
                                            if(strpos($TOEFL, "PBT")){$StrPosVal = 'PBT';}elseif(strpos($TOEFL, "paper")){$StrPosVal = 'paper';}
                                            $PBT = substr($TOEFL, strpos($TOEFL, "$StrPosVal"));
                                                preg_match($NumericPattern, $PBT, $TOEFL_PBTmatches);
                                                $TOEFL_PBT_score = isset($TOEFL_PBTmatches[1])?$TOEFL_PBTmatches[1]:0;
                                        }

                                $this -> ielts ="$IELTS_score";
                                $this -> pte="$PTE_score";
                                $this -> tofel_ibt="$TOEFL_IBT_score";
                                $this -> tofel_pbt="$TOEFL_PBT_score";
                                $this -> gre="";
                                $this -> gmat="";

            
                
                                    };


                        $addressCount = count($course_detail_dom  -> find('address'));
                        
                        foreach($course_detail_dom -> find("address") as $node){

                                $this -> address[]= $node->innertext;
                                
                        };
                        
                        $address = implode("\n",array_slice($this -> address, -$addressCount, $addressCount));
                        $this -> courseArray [] = array(
                                    "title"       => $courseTitle,
                                    "level"        =>$this -> institution_type,
                                    "code"         => $this -> code,
                                    "summary"     => $this -> summary,
                                    "fees"          => $this -> fee,
                                    "start_date"   => $this -> start_date,
                                    "duration" => $this -> duration,
                                    "study_mode" => $this -> study_mode,
                                    "criteria"    => $this -> criteria,
                                    "address"     => $address,
                                    "ielts"     =>   $this -> ielts ,
                                    "pte"     =>     $this -> pte,
                                    "tofel_IBT"     =>   $this -> tofel_ibt,
                                    "tofel_PBT"     =>   $this -> tofel_pbt,
                                    "gre"     =>     $this -> gre,
                                    "gmat"     =>    $this -> gmat

                                );
                        
                    
                        };

                        $this ->spreadsheet -> setActiveSheetIndex(2);
                                        
                        $this  ->spreadsheet -> getActiveSheet()-> setCellValue("A1","university Name")  -> setCellValue("B1", 'title' )-> setCellValue("C1", "Code" )-> setCellValue("D1", "Level of Study")-> setCellValue("E1", "Fees" ) -> setCellValue("F1", 'Duration' )-> setCellValue("G1", "start Date" )-> setCellValue("H1", "Study mode" )-> setCellValue("I1", "Ielts" )-> setCellValue("J1", "PTE" )-> setCellValue("K1", "Tofel IBT" )->setCellValue("L1", "Tofel PBT" )->setCellValue("M1", "GRE" )->setCellValue("N1", "GMAT" );
                            
                    
                        // /***** Add some data to the third sheet**********/
                        foreach( $this -> courseArray as $course){ 
                            
                            $this  ->spreadsheet -> getActiveSheet()-> setCellValue("A$this->courseCount",$this -> universityName)  -> setCellValue("B$this->courseCount", $course ['title'] )-> setCellValue("C$this->courseCount", $course["code"] )-> setCellValue("D$this->courseCount", $course["level"])-> setCellValue("E$this->courseCount", $course["fees"] ) -> setCellValue("F$this->courseCount", $course['duration'] )-> setCellValue("G$this->courseCount", $course["start_date"] )-> setCellValue("H$this->courseCount", $course["study_mode"] )-> setCellValue("I$this->courseCount", $course["ielts"] )-> setCellValue("J$this->courseCount", $course["pte"] )-> setCellValue("K$this->courseCount", $course["tofel_IBT"] )->setCellValue("L$this->courseCount", $course["tofel_PBT"] )->setCellValue("M$this->courseCount", $course["gre"] )->setCellValue("N$this->courseCount", $course["gmat"] );
        
                            //   $this  ->spreadsheet -> getActiveSheet()-> setCellValue("A$this->courseCount",$this -> universityName)  -> setCellValue("B$this->courseCount", $course ['title'] )-> setCellValue("C$this->courseCount", $course ['summary'] )-> setCellValue("D$this->courseCount", $course ['oppurtunity'] )-> setCellValue("E$this->courseCount", $course ['criteria'] ) -> setCellValue("E$this->courseCount", $course ['address'] );
                                        
                        $this -> courseCount++;
                                                        
                        }

                        // /*****Rename Second Sheet**********/
                        $this ->spreadsheet -> getActiveSheet()-> setTitle("Courses");

                
                    }
                        

                          

                       
                        $this -> list []= array(
                                    
                                $this -> universityName,
                                $this -> about,
                                $this -> institution_type,
                                $this -> code,
                                $this -> logo,
                                $this -> uni_link
                                // $address, 
                                // $courseList
                                    
                                );

                         // fputcsv($this -> fp, $this -> list);
                         $this -> ucount ++ ; 
                         flush();

                }
                


            }
            $writer = new Xlsx($this -> spreadsheet);
            $writer->save('universities.xlsx');
            echo "<pre>"; echo "here";
            print_r($this -> list);
            
            
           
        }

        function make_request($url)
        {
            
            $ch = curl_init();
            
            $proxy = 'proxy.crawlera.com:8010';
            $proxy_auth = 'c5abd2f12d1045eeaaa66d02b91d0308:';
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180);
            curl_setopt($ch, CURLOPT_CAINFO, 'crawlera-ca.crt'); //required for HTTPS
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //required for HTTPS
            
             $scraped_page = curl_exec($ch);
            
            if($scraped_page === false)
            {
                echo 'cURL error: ' . curl_error($ch);
            }
            else
            {
                 
                return $scraped_page;
            }
            
            curl_close($ch);
            
            
        }

    
}

$objects = new DataExport();
$objects -> index();
?>
