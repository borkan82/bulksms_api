<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Boris
 * Date: 02/10/15
 * Time: 9:42 AM
 */

include_once ("class/classPARAMETERS.php");

class SMS extends PARAMETERS {
        
        private $_logRoot = "/";
        private $_csvRoot = "csv/";

        public function __construct($db) {
        if ($db) {
            $this->db = $db;
        }
    }

/**********************************************************************
 * ------------ Slanje pojedinacnih SMS-ova sa drugih aplikacija      *
 **********************************************************************/

        public function sendSMS($param){
            $type           = $param[0];
            $state          = $param[1];
            $messageID      = $param[2];
            $from           = $param[3];
            $to             = $param[4];
            $message        = $param[5];
            $count          = 0;

            return $this->deliverySms($type,$state,$messageID,$from,$to,$message,$count);
        }
/**********************************************************************
 * ---------------------------- Precisti poruku ---------------       *
 **********************************************************************/
        private function cleanUTF($name, $state){
            $name = str_replace(array('š','č','đ','č','ć','ž','ñ','â','î','ă','ő','ř','í','á','ł','ż','ň','ó','ů','ě','ј','ѓ','ρ','κ','ľ','ą','ĺ','ń','ș','ď','ț','ā','ý','ė','ú','ē','ī','ū','ģ','ņ','ļ','ę','ë','`','ű','é','ť','ê','è','õ','ş','ţ','ï','ς','ί'),array('s','c','dj','c','c','z','n','','i','a','o','r','i','a','l','z','n','o','u','e','j','g','r','k','l','a','l','n','s','d','t','a','y','e','u','e','i','u','g','n','l','e','e','','u','e','t','e','e','o','s','t','i','s','i'), $name);
            $name = str_replace(array('Š','Č','Đ','Č','Ć','Ž','Ñ','Â',' ','–','Î','Ă','Ő','Ř','Í','Á','Ł','Ż','Ň','Ó','Ů','Ě','Ј','Ѓ','Ρ','Κ','Ľ','Ą','Ĺ','Ń','Ș','Ď','—','Ț','Ā','Ý','Ė','Ú','Ē','Ī','Ū','Ģ','Ņ','Ļ','Ę','Ű','É','Ť','Ê','È','Õ','Ş','Ţ','Ï','Ί'),array('S','C','D','C','C','Z','N','',' ','-','I','A','O','R','I','A','L','Z','N','O','U','E','J','G','R','K','L','A','L','N','S','D','-','T','A','Y','E','U','E','I','U','G','N','L','E','U','E','T','E','E','O','S','T','I','I'), $name);
            
            if ($state !== "BG" && $state !== "MK"){
            $name = str_replace(array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','љ','м','н','њ','о','п','р','с','т','у','ф','х','ц','ч','џ','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Џ','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'),
                                array('a','b','v','g','d','e','e','z','z','i','j','k','l','lj','m','n','nj','o','p','r','s','t','u','f','h','c','c','dz','s','s','i','j','j','e','ju','ja','A','B','V','G','D','E','E','Z','Z','I','J','K','L','Lj','M','N','Nj','O','P','R','S','T','U','F','H','C','C','Dz','S','S','I','J','J','E','Ju','Ja'), $name);
            }

            // Fix za Poruke na Grckom alfabetu
            //if ($state !== "GR"){
                $name = str_replace(array('α','β','γ','δ','ε','ζ','η','θ','ι','κ','λ','μ','ν','ξ','ο','π','ρ','σ','τ','υ','φ','χ','ψ','ω'),array('a','b','g','d','e','z','h','th','i','k','l','m','n','x','o','p','r','s','t','y','f','ch','ps','w'), $name);
                $name = str_replace(array('Α','Β','Γ','Δ','Ε','Ζ','Η','Θ','Ι','Κ','Λ','Μ','Ν','Ξ','Ο','Π','Ρ','Σ','Τ','Υ','Φ','Χ','Ψ','Ω'),array('A','B','G','D','E','Z','H','TH','I','K','L','M','N','X','O','P','R','S','T','Y','F','CH','PS','W'), $name);
            //}

            return $name;
        }
/**********************************************************************
 * --- Izlistaj odjavljene brojeve sa  ------------------------       *
 **********************************************************************/

    private function getSuppressionList($state) {
        $sql = "SELECT * FROM suppressionList
                WHERE state = '{$state}' 
                GROUP BY number";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
* --- Izlistaj brojeve na koje poruka nije isporucena -----------     *
**********************************************************************/

    private function getUndeliveredList($campaign) {
        $sql = "SELECT origin FROM smsMessages
                WHERE 1 
                AND messageId LIKE '{$campaign}' 
                AND status != 2
                ORDER BY id DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
* -------------- Uvecavanje polja za 1 jedinicu ---------------       *
**********************************************************************/
    public function incrementField($table,$field,$kveri){
        $updatekveri    = "UPDATE `{$table}` 
                            SET `{$field}`= {$field} + 1 
                            WHERE 1 {$kveri}";
        $all            = $this->db->query($updatekveri,1);
        if ($all){
            echo "1";
        } else {
            echo "-1";
        }
    }
/**********************************************************************
* -------------- Inicijalno Dodavanje poruke u bazu -----------       *
**********************************************************************/
    public function insertMessage($smsId,$from,$originTo,$mId,$message,$statusNum,$messageNumber,$stateId){

        $message    = mysql_real_escape_string($message, $this->db->_connect);
        $sql        = "INSERT INTO `smsMessages` (`smsId`, `from`, `origin`, `messageId`, `message`, `status`, `smsCount`) 
                                          VALUES ('$smsId','$from','$originTo','$mId','$message',$statusNum,'$messageNumber')";
        $this->db->query($sql,1);
        
        $meId = mysql_insert_id();

        $sql2   = "INSERT INTO `smsMessages_ID` (`sentID`, `sms_ID`,`state_id`) 
                                         VALUES ('$smsId','$meId','$stateId')";
        $all    = $this->db->query($sql2,1);

        // $all        = $this->db->query($sql,2);
        return $all;
    }

/**********************************************************************
* --------------Dodavanje skracenih linkova -------------------       *
**********************************************************************/
    public function insertShort($phone,$campaignid){

        $randomStr = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(5/strlen($x)) )),1,5);

        $sql   = "INSERT INTO `phone_order_shorturlbulk` (`shortCode`, `phone`, `campaignID`, `dateVisited`) 
                                                  VALUES ('$randomStr','$phone','$campaignid','0000-00-00 00:00:00')";
        $all   = $this->db->query($sql,1);

        return $randomStr;
    }

/**********************************************************************
 * --- Provjera da li je primio SMS - izbjegavanje spamovanja -       *
 **********************************************************************/

    public function checkSentThisMonth($number,$interval = 30) {
        $afterDays = $interval; // default broj dana nakon kojih se salje reorder SMS
        $daysAgo = Date("Y-m-d", strtotime('-'.$afterDays.' days'));

        $sql = "SELECT * FROM smsMessages 
                WHERE 1 
                AND origin = '{$number}' 
                AND DATE(smsMessages.dateSent) >= '{$daysAgo}' 
                AND (LEFT(messageId, 3) LIKE '%reo%' OR LEFT(messageId, 3) LIKE '%sms%')";

        return $this->db->query($sql,3);
    }
/**********************************************************************
 * ---------------------------- Precisti broj ---------------       *
 **********************************************************************/
        public function cleanMob($phoneNo, $state){
            $datum      = Date("Y-m-d");
            $repair     = array();  // Niz za redosljed popravki broja
            array_push($repair, $phoneNo);
             
            $areaLen    = strlen($this->_areaCodes[$state]);
             
            $exceptions = array(
              'BA' => Array(),
              'RS' => Array(),
              'HR' => Array(),
              'MK' => Array(),
              'BG' => Array(),
              'SI' => Array(),
              'IT' => Array('391','392','393'),
              'SK' => Array(),
              'LV' => Array(),
              'PL' => Array(),
              'GR' => Array(),
              'LT' => Array(),
              'AT' => Array(),
              'HU' => Array(),
              'CZ' => Array(),
              'RO' => Array(),
              'DE' => Array(),
              'EE' => Array(),
              'FR' => Array(),
              'BE' => Array(),
              'ES' => Array(),
              'AL' => Array(),
              'XK' => Array()
            );

            $exceptToReject = array(
              'BA' => Array(),
              'RS' => Array(),
              'HR' => Array(),
              'MK' => Array(),
              'BG' => Array(),
              'SI' => Array(),
              'IT' => Array('0376','0383','0386'),
              'SK' => Array(),
              'PL' => Array(),
              'GR' => Array(),
              'LV' => Array(),
              'LT' => Array(),
              'AT' => Array(),
              'HU' => Array(),
              'CZ' => Array(),
              'RO' => Array(),
              'DE' => Array(),
              'EE' => Array(),
              'FR' => Array(),
              'BE' => Array(),
              'ES' => Array(),
              'AL' => Array(),
              'XK' => Array()
            );

            // $phoneNo = str_replace('+', "", $phoneNo);
            // $phoneNo = str_replace('ˇ', "", $phoneNo);
            $phoneNo = trim($phoneNo);
            $phoneNo = str_replace('o', "0", $phoneNo);
            $phoneNo = str_replace('O', "0", $phoneNo);
            

             if (strlen($phoneNo) > 15 && substr_count($phoneNo," ") < 4 && substr_count($phoneNo,"/") < 3){
                        $phoneNo = $this->pullCellNumber($phoneNo, $state); // Ako broj duzi od 15, pretpostavka je da su dva broja upisana. Upotrijebi funkciju za izvlacenje mobilnog
             }

             $phoneNo = preg_replace('/[^0-9]/', '', $phoneNo);
            
            array_push($repair, $phoneNo);

            // Obrisi nule ako postoje na pocetku broja
            if (substr($phoneNo, 0, 2) == "00"){
                  $phoneNo = substr($phoneNo, 2); 
             } 
            array_push($repair, $phoneNo);

            $potvrdae = false;

            if(!empty($exceptions[$state])){
                foreach($exceptions[$state] as $except){
                    $duzinae = strlen($except);
                    $cutPhonee = substr($phoneNo, 0, $duzinae);

                    if ($except == $cutPhonee) {
                        $potvrdae = true;

                        if ($state == "IT" && strlen($phoneNo) >= 12){
                            $potvrdae = false;
                        }
                        break;
                    }
                }
            }


            $areaCheck = substr($phoneNo, 0, $areaLen);

            if ($areaCheck == $this->_areaCodes[$state] || ($state == "XK" && $areaCheck == "386") && $potvrdae == false){
                $phoneNo = substr($phoneNo, $areaLen);
            }
                // Exception za prefix 06 u grckoj
                if ($state == "HU"){
                    $checkGR = substr($phoneNo, 0, 2);
                    if ($checkGR == "06") {
                        $phoneNo = substr($phoneNo, 2);
                    }
                }
                // Exception za prefix 8 u Litvaniji
                if ($state == "LT"){
                    $checkLT = substr($phoneNo, 0, 1);
                    if ($checkLT == "8") {
                        $phoneNo = substr($phoneNo, 1);
                    }
                }

            $potvrda = false;
            $duzina = 0;
 
                foreach($this->_allowedArr[$state] as $area){

                    $duzina     = strlen($area);
                    $cutPhone   = substr($phoneNo, 0, $duzina);
 
                    if ($area == $cutPhone) {
                        $potvrda = true;
                        break;
                    } 
                }

                if(!empty($exceptToReject[$state])){
                    foreach($exceptToReject[$state] as $reject){

                        $rduzina = strlen($reject);
                        $rcutPhone = substr($phoneNo, 0, $rduzina);

                        if ($reject == $rcutPhone) {
                            $potvrda = false;
                            break;
                        }
                    }
                }


                    if (($duzina == 3 || $duzina == 4 ) && substr($phoneNo, 0, 1) == 0){
                        $phoneNo = substr($phoneNo, 1);
                    } 
                    
            array_push($repair, $phoneNo);

                //Zavrsno sredjivanje, brisanje whitespacea i formiranje pravilnog broja za unos u csv
                if ($potvrda){
                    $phoneNo = str_replace(' ', "", $phoneNo);
                    $phoneNo = str_replace('+', "", $phoneNo);
                    $phoneNo = str_replace('.', "", $phoneNo);
                    $phoneNo = str_replace('/', "", $phoneNo);
                    $phoneNo = str_replace('-', "", $phoneNo);
                } else {
                    $phoneNo .= " - number rejected";
                }
                    array_push($repair, $phoneNo);
                   
                    $repairNum = $state." - ".$repair[0]." > ".$repair[1]." > ".$repair[2]." > ".$repair[3]." > ".$repair[4]."\n";

                    $existCount1 = 0;
                    if (file_exists($this->_logRoot."repair/".$datum.".txt")){
                                $existCount1 = 1;
                            }


                    $file = fopen($this->_logRoot."repair/".$datum.".txt", "a");
                            file_put_contents($this->_logRoot."repair/".$datum.".txt", $repairNum, FILE_APPEND);
                            fclose($file);
                            
                            if ($existCount1 == 0){
                                chmod($this->_logRoot."repair/".$datum.".txt", 0777);
                            }


                if ($potvrda){
                    return $phoneNo;
                } else {
                    return false;
                    
                }

        }
/**********************************************************************
 * --------- Izdvajanje mobilnog broja ako su dva upisana -----       *
 **********************************************************************/        
        private function pullCellNumber ($phoneNum, $state, $reportNum = 0){

            
            $splitPhone = "";
            $pulledNum  = "";

            //Razdvajanje brojeva zavisno od toga kojim su znakom razdvojeni
            if (strpos($phoneNum,' ') !== false) {
                $splitPhone = explode(" ", $phoneNum);
            } else if (strpos($phoneNum,',') !== false) {
                $splitPhone = explode(",", $phoneNum);
            } else if (strpos($phoneNum,';') !== false) {
                $splitPhone = explode(";", $phoneNum);
            } else if (strpos($phoneNum,'+') !== false) {
                $splitPhone = explode("+", $phoneNum);
            } else {
                $splitPhone = Array($phoneNum);
            }

            foreach ($splitPhone as $brojevi) {

                $brojInt = preg_replace('/[^0-9]/', '', $brojevi);

                if (substr($brojInt, 0, 2) == "00"){
                  $brojInt = substr($brojInt, 2); 
                } 

                foreach($this->_allowedArr[$state] as $area){

                    $duzina     = strlen($area);
                    $cutPhone   = substr($brojInt, 0, $duzina);

                    if ($area == $cutPhone) {
                        $pulledNum = $brojInt;

                        break;
                    }
                }
            }
            $pulledNum = preg_replace('/[^0-9]/', '', $pulledNum);
            $pulledNum = str_replace(' ', "", $pulledNum);

            return $pulledNum;
        }

/**********************************************************************
 * -------------------- Slanje poruke preko NTH ---------------       *
 **********************************************************************/
        private function deliverySms($type,$state,$messageID,$from,$to,$message,$count){
            $datum      = Date("Y-m-d");
            $count      = $count + 1;

            if (isset($this->_stateIDs[$state])){
               $stateId     = $this->_stateIDs[$state]; 
           } else {
               $stateId     = 0;
           }
            
            /*
             * Uncomment if is needed to disable sending at specific time
             */
            // $sleep_time = date('H');
            // $sleep = array('21', '22','23','00','01','02','03','04','05','06');

            // if not between 21:00 - 07:00
            // if(!in_array($sleep_time, $sleep)){

            if  ($type !== "bulk") { 
                $to = $this->cleanMob($to, $state);
                    if ($to == false) { 
                        return "wrong number";
                    }
            }
            //ucitavanje liste odjava 
                if  ($type == "reorder") {  
                    $suppression = $this->getSuppressionList($state);
                    $supArr = array();

                    foreach ($suppression as $sNumber){
                        array_push($supArr, $sNumber['number']);
                    }

                    if (in_array($to, $supArr) == true) {
                        return "number unsubscribed";
                    }
                } 

                // konacno filtriranje samo brojeva   
                $to = preg_replace('/[^0-9]/', '', $to);

                $firstTwo = substr($to, 0, 2);

                if ($state == "XK" && $firstTwo == "49") {
                   $responsePhone  = "+386".$to; 
                   $to     = "00386".$to;
                } else {
                   $responsePhone  = "+".$this->_areaCodes[$state].$to; 
                   $to             = "00".$this->_areaCodes[$state].$to;
                }

                
                $message    =  strip_tags($message);

                $msg    =  $this->cleanUTF($message, $state);
                $ch     = curl_init('http://bulk.mobile-gw.com:9000/?');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "username=*******&password=*********&allow_adaption=1&messageid={$messageID}&status_report=3&origin={$from}&call-number={$to}&text=".urlencode($msg));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_VERBOSE, 1);
                curl_setopt($ch, CURLOPT_HEADER, 1);
                $send_response = curl_exec ($ch);
                curl_close ($ch);

                $getRow = explode("\n", $send_response);
                //******** Hvatanje SMSID broja
                // $getId  = explode(":", $getRow[2]);
                // $smsId  =  trim($getId[1]);


                $getId  = explode(":", $getRow[2]);
                $smsIdRaw  =  trim($getId[1]);

                $countSeparator = substr_count($smsIdRaw,";");
                $messageNumber  = $countSeparator + 1;
                $smsIdExploaded = explode(";", $smsIdRaw);

                $smsId  =  $smsIdExploaded[0];
                //******** Hvatanje Statusa slanja
                $getSecond  = explode(":", $getRow[6]); 
                $getCode    = explode(",", $getSecond[1]);
                $statusCode = trim($getCode[0]);

                $statusNum  = 0;
                if ($statusCode == "00"){
                    $statusNum = 1;
                } else {
                    $statusNum = 3;
                }

                $this->insertMessage($smsId,$from,$to,$messageID,$msg,$statusNum,$messageNumber,$stateId); 
                if($type == "bulk"){
                    //echo json_encode(array("count"=>$count, "number"=>$to, "response"=>$send_response));
                    $writeFile  ='{"MessageId":"'.$messageID.'","SenderId":"'.$from.'","Recipient":"'.$to.'","Response":"'.$send_response.'","Message":"'.$msg.'"},\n';
                    $file       = fopen($this->_logRoot."reports/bulk/".$messageID.".txt", "a");
                    file_put_contents($this->_logRoot."reports/bulk/".$messageID.".txt", $writeFile, FILE_APPEND);
                    fclose($file);
                } else if ($type == "reorder"){
                    //echo json_encode(array("count"=>$count, "number"=>$to, "response"=>$send_response));
                    $writeFile  ='{"MessageId":"'.$messageID.'","SenderId":"'.$from.'","Recipient":"'.$to.'","Response":"'.$send_response.'","Message":"'.$msg.'"},\n';
                    $file       = fopen($this->_logRoot."reports/reorder/".$messageID.".txt", "a");
                    file_put_contents($this->_logRoot."reports/reorder/".$messageID.".txt", $writeFile, FILE_APPEND);
                    fclose($file);
                } else {
                    $writeFile  ='{"MessageId":"'.$messageID.'","SenderId":"'.$from.'","Recipient":"'.$to.'","Response":"'.$send_response.'","Message":"'.$msg.'","Quantity":"'.$messageNumber.'"},\n';
                    $existCount2 = 0;
                    if (file_exists($this->_logRoot."reports/orders/".$datum.".txt")){
                        $existCount2 = 1;
                    }

                    $file       = fopen($this->_logRoot."reports/orders/".$datum.".txt", "a");
                    file_put_contents($this->_logRoot."reports/orders/".$datum.".txt", $writeFile, FILE_APPEND);
                    fclose($file);

                    if ($existCount2 == 0){
                        chmod($this->_logRoot."reports/orders/".$datum.".txt", 0777);
                    }
                }
                $responseJson   =   '{"status":"'.$statusNum.'","msisdn":"'.$responsePhone.'"}';
                return $responseJson;
            // }else{
            //     return 'sleep time';
            // }
        }
/**********************************************************************
 * ---------------------- Slanje poruka ako se koristi bulk  --       *
 **********************************************************************/
       public function sendBULKsms($param,$cType="single",$perHour, $messageStop){

            $type       = "bulk";
            $state      = $param[1];
            $messageID  = $param[2];
            $campaignID = $param[3];
            $from       = $param[5];
            $poruka     = $param[6];
            $count      = 0;
            
            $suppression = $this->getSuppressionList($state);       //lista odjave

            $undelivered = $this->getUndeliveredList($messageID);   //lista neisporucenih
            //$undelivered = Array();
            $sentReport  = Array();


            // Formiranje niza za listu odjavljenih kupaca
            $supArr = array();

                foreach ($suppression as $sNumber){
                    array_push($supArr, $sNumber['number']);
                }
            // Formiranje niza za listu brojeva na koje prva poruka nije isporucena
            $undelArr = array();

                foreach ($undelivered as $uNumber){
                    array_push($undelArr, $uNumber['origin']);
                }

            $query = $messageID;

            $count = 0;
            $sentResult = array();

            //Uzmi CSV sa brojevima i imenima i napravi niz za slanje
            $csv = array();

            if ($cType == "multiple"){
                $file = fopen($this->_csvRoot.'includes/csv/'.$messageID.'.csv', 'r');
            } else {

                $file = fopen($this->_logRoot.'csv/slanje.csv', 'r');
            }

            while (($result = fgetcsv($file)) !== false)
            {
                $csv[] = $result;
            }
            fclose($file);

            $brojevi = "";
            foreach ($csv as $broj) {

                $brojevi .= $broj[1]."\n";
            }

            file_put_contents($this->_logRoot.'reports/undelivered/'.$messageID.'.csv', $brojevi, FILE_APPEND);


                    if ($cType == "multiple"){
                        $endMessage = $messageStop + $perHour; // Limit poruka koje se salju

                            for($x = $messageStop; $x < $endMessage; $x++){


                                $to = $csv[$x][1];
                                $numberToCheck = "00".$this->_areaCodes[$state]."".str_replace(' ', "", $to);
                                $customerName = substr($csv[$x][0], 0, 12); //Maksimalno 12 karaktera za ime
                                $message = str_replace("[[contact.name]]",$customerName,$poruka);
                                if ($to != "") {
            
                                        //Provjera da li je broj u listi undelivered Messages
                                            $ret = array_keys(array_filter($undelArr, function($var) use ($to){
                                                    return strpos($var, $to) !== false;
                                                }));
                
                                        if (in_array($to, $supArr) == false && empty($ret)) {
                                            if (strpos($message, '[[smslink]]') == true){
                                               $shortlink       = $this->insertShort($to,$campaignID);
                                               $shortAddress    = "givv.me/".$shortlink;
                                               $message         = str_replace("[[smslink]]",$shortAddress,$message);
                                            }

                                            $this->deliverySms($type,$state,$messageID,$from,$to,$message,$count); //posalji poruku
                                            $updateSent = $this->incrementField("CampManagement","sent"," AND CampaignName LIKE '".$query."'"); //povecaj u bazi vrijednost za svaku poslatu poruku po kampanji
                                        } 
                                        if ($x > 20000) {
                                            exit; //sigurnosna stavka ako predje preko 20000 ukupnih brojeva za kampanju!!! fixati ako se povecaju brojevi za kampanju
                                        }//end limit
                                } //end empty to variable  

                            } // end for x loop
                    } else {
                            foreach ($csv as $ispis) {
                                $to = $ispis[1];
                                $customerName = substr($ispis[0], 0, 12); //Maksimalno 12 karaktera za ime
                                $message = str_replace("[[contact.name]]",$customerName,$poruka);
                                // echo $type." ".$state." ".$messageID." ".$from." ".$to." ".$message." ".$count;exit;
                                if (in_array($to, $supArr) == false) {
                                    $this->deliverySms($type,$state,$messageID,$from,$to,$message,$count); //posalji poruku
                                    $updateSent = $this->incrementField("CampManagement","sent"," AND CampaignName LIKE '".$query."'"); //povecaj u bazi vrijednost za svaku poslatu poruku po kampanji
                                } 
                            }
                    }



            } 
/**********************************************************************
 * ------------------------------------------------------------       *
 **********************************************************************/
} // END class





?>