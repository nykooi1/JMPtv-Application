<?php
//resize image keeping dimensions proportional
function resize($newSize, $originalFile, $targetFile, $quality) {

    $info = getimagesize($originalFile);
    $mime = $info['mime'];

    switch ($mime) {
            case 'image/jpeg':
                    $image_create_func = 'imagecreatefromjpeg';
                    break;

            case 'image/png':
                    $image_create_func = 'imagecreatefrompng';
                    break;

            default: 
                    throw new Exception('Unknown image type.');
    }
    
    $img = $image_create_func($originalFile);
    list($width, $height) = getimagesize($originalFile);
    
    $newWidth = ($width / $height) * $newSize;
    
    $tmp = imagecreatetruecolor($newWidth, $newSize);
    
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newSize, $width, $height);

    if (file_exists($targetFile)) {
            unlink($targetFile);
    }
    imagejpeg($tmp, $targetFile, $quality);
    imagedestroy($tmp);
}

function vLabel($String) {
	$String = substr($String, 0, 89);
	return preg_replace("/[^a-z0-9-@_\.]/", "", strtolower($String));
}

// Returns a $Size or 13 character (censored) GUID prefixed with $Prefix
function vGUID($Size = null, $Prefix = null) {
    // No vowels prevents spelling censored/bad words
    // No "L" prevents ambiguity with the number "1"
    $Chars = "bcdfghjkmnpqrstvwxyz0123456789"; // 30
    $Len = (strlen($Chars) - 1);
    $Size = ($Size < 1 ? 13 : ($Size > 987 ? 987 : $Size));
    $GUID = vLabel($Prefix);
    for ($i = 0; $i < $Size; $i++) { $GUID .= $Chars[mt_rand(0, $Len)]; }
    return $GUID;
}

//slide number / index
$vSlide = $_POST["vSlide"];

//account name
$vAccount = $_POST["vAccount"];

//get the original image names
$originalImages = scandir("accounts/" . $vAccount . "/media/");

//sets directory for where to save the file
$target_dir = "accounts/" . $vAccount . "/media/";

$filename = $vAccount . "-" . vGUID(21) . "." . pathinfo($_FILES['vFile']['name'], PATHINFO_EXTENSION);

//where to save the file
$target_file =  $target_dir . $filename;

echo $target_file;

//saves the file to the specified location
move_uploaded_file($_FILES["vFile"]["tmp_name"], $target_file);

resize(1200, $target_file, $target_file, 60);

/*
//if the json file does not exist, intialize the array and insert the filename
if(!file_exists("media/" . $vAccount . "/json/" . $vAccount . ".json")){
    $imagesArray = array();
    array_push($imagesArray, $target_file);
    $JSONimages = json_encode($imagesArray);
    file_put_contents("media/" . $vAccount . "/json/" . $vAccount . ".json", $JSONimages);
}
//if the json file does exist, insert the image into the correct index
else {
    $imagesArray = json_decode(file_get_contents("media/" . $vAccount . "/json/" . $vAccount . ".json"));
    echo json_encode($imagesArray);
    array_splice($imagesArray, $vSlide + 1, 0, $target_file);
    $JSONimages = json_encode($imagesArray);
    file_put_contents("media/" . $vAccount . "/json/" . $vAccount . ".json", $JSONimages);
}*/

