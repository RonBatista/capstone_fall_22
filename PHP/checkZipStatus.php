<?php
#PHP code that grabs the path to the zip file and the temporary results directory. The code then calls to a python file (CheckZipStatus.py) which then checks the status of the zip.
     $zip_file_path = $_GET["zipfile_path"];
     $zip_file_path_noExt = $_GET["zipfile_path_noExt"];

     $returningArray = array();
     array_push($returningArray, $zip_file_path);
     array_push($returningArray, $zip_file_path_noExt);

     # Call Python code with field information to check the Zip file and temp results directory.
     $checkZip_command = "python3 /var/www/html/uas_tools/canopy_attribute_generator/Resources/Python/CheckZipStatus.py $zip_file_path_noExt $zip_file_path";
     array_push($returningArray, $checkZip_command);
     $result = shell_exec($checkZip_command);
     if($result){                                     // If there is a result push result to array being passed back in JSON format
       # echo "Python has been executed!";
       array_push($returningArray, $result);
        }else{
          array_push($returningArray, "ERROR: Was unable to call to the CheckZipStatus python executable!");     // Else, append Error on to returning JSON object.
        }

     echo json_encode($returningArray);     //return returning array regarding this call in JSON format.
     die();

?>