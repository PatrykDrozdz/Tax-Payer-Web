<?php
   
    require_once 'connect.php';

    $connection = new mysqli($host, $db_user, $db_password, $db_name);

    /*if(isset($_POST['postincomme']) && isset($_POST['postincommeCosts'])  && 
            isset($_POST['postsocial']) && isset($_POST['posthealth'])){*/
if(isset($_POST['incomme'])){
    try{
        
        function getTaxFreePayment($id, $connection){
            
            $result = $connection->query("SELECT * FROM freetaxvalue WHERE idfreetaxvalue = '$id'");
            $row = $result->fetch_assoc();
            
            $value = $row['freePay'];
            
            $result->free();
            
            return $value;
        }
        
        function setProcents($value){
            
            $result = 100*$value;
            $result=$result.'%';
            
            return $result;
        }
        
        $line = 0.0;  
        $prog=0.0;
        $incomme = $_POST['incomme'];
        $incommeCosts = $_POST['incommeCosts'];
        $social = $_POST['social'];
        $health = $_POST['health'];

        /*
        $incomme = $_POST['postincomme'];
        $incommeCosts = $_POST['postincommeCosts'];
        $social = $_POST['postsocial'];
        $health = $_POST['posthealth'];
        */
        //echo $incomme.'<br/>'.$incommeCosts.'<br/>'.$social.'<br/>'.$health.'<br/>';
        
        $payment = $incomme - $social - $incommeCosts;
        
        //echo $payment.'<br/>';
        
        $result = $connection->query("SELECT * FROM taxes");
        
        $allTaxesRows = $result->num_rows;
        
        //echo $allTaxesRows;
        
        $countDatas = 0;
        
        for($i=1; $i<=$allTaxesRows; $i++){
            
            
            $result = $connection->query("SELECT * FROM taxes WHERE flagT=1 AND "
                    . "idtaxes='$i'");
            
            $row = $result->fetch_assoc();
            
            $values[$countDatas] = $row['value'];
            $guaranteedAmount[$countDatas] = $row['guaranteedAmount'];
            $downPayment[$countDatas] = $row['downPayment'];
            $maxPayment[$countDatas] = $row['maxPayment'];
            $freeTaxPayId[$countDatas] = $row['freetaxvalue_idfreetaxvalue'];
            //echo $values[$countDatas];
            $result->free();
            $countDatas++;
            
        }

        
        for($j=0; $j<$countDatas; $j++){
            
            
            if ($maxPayment[$j] >= $payment && $downPayment[$j] < $payment)
                    {//progresja - pośrednie podatki
                        $prog = ($guaranteedAmount[$j] + ($payment - $downPayment[$j] -
                        getTaxFreePayment($freeTaxPayId[$j], $connection)) * $values[$j]) - $health;
                        $taxProg = setProcents($values[$j]);
                    }
                    else if ($maxPayment[$j] == 0 && $downPayment[$j] == 0)
                    {//liniowy podatek
                        $line = ($guaranteedAmount[$j] + ($payment - $downPayment[$j] -
                        getTaxFreePayment($freeTaxPayId[$j], $connection)) * $values[$j]) - $health;
                        $taxLine = setProcents($values[$j]);
                    }
                    else if($maxPayment[$j]==0 && $downPayment[$j] != 0 && $payment>$downPayment[$j])
                    {//progresja - najwyższa stawka
                        $prog = ($guaranteedAmount[$j] + ($payment - $downPayment[$j] -
                        getTaxFreePayment($freeTaxPayId[$j], $connection)) * $values[$j]) - $health;
                        $taxProg = setProcents($values[$j]);
                    }

                    if ($prog < 0)
                    {
                        $prog = 0;
                    }

                    if ($line < 0)
                    {
                        $line = 0;
                    }

            }

            $prog = round($prog);
            $line = round($line);

            if ($prog < $line)
            {

                $resultValue = $prog;
                $resultTax = $taxProg;


            }
            else if($prog > $line)
            {
                $resultValue = $line;
                $resultTax = $taxLine;

            }
            else
            {
                $resultValue = $prog;
                $resultTax = $taxProg;


            }     

        }catch(Exception $e){
            echo $e;
        }
    }

?>