<?php  

/**
 * estatessales.net for some odd reason requires that you provide not only
 * the url to the remote image you want added but oddly ALSO a gigantic encoded
 * byte array of the image file.
 * @param $image_path absolute path to image asset
 * @return json_obj a the byte_array of 
 */

// this will be converted into an array inside of the .net class so that we 
// filter all images added from a post through this function
$image = 'https://grasons.com/wp-content/uploads/2013/06/older-people-smiling.jpg';

function convert_image_to_byte_array($image_path) {

    $opts = [
      "http" => [
        "method" => "GET",
        "header" => "Content-Type: image/jpeg\r\n"
      ]
    ];

    // the stream, in this instance, is a set of conditions for 
    // which the file will be encoded when it is returns. 
    // this was needed as the intial response gave us the default 
    // HTML encoding rather than image/jpeg encoding
    $context = stream_context_create($opts);

    $file = file_get_contents(
                $image_path, 
                false, 
                $context
            );

    $image = [];

    foreach(str_split($file) as $char){ 
        // convert each character into ASCII indexes 
        // see ASCII chart for examples
        array_push($image, ord($char)); 
    }
    
    return json_encode($image);

}
?>
