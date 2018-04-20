<?php  
    header('Content-Type: application/json');
    $data = file_get_contents("https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg");

    $array = array(); 
    foreach(str_split($data) as $char){ 
        array_push($array, ord($char)); 
    }
    echo json_encode($array);
?>
