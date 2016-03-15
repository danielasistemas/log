<?

/**
 * album.php displays image with header from soar_form table
 **/


//find which table, column, primary key, and row is needed

//var_dump($url_array);
$table = $url_array[1];
$column = $url_array[2];
$key_column = $url_array[3];
$record_key = (int)$url_array[4];

//get image blob
$query = "SELECT $column as img FROM $table WHERE $key_column = $record_key;";
$query = mysql_escape_string($query);

if ($result = mysql_query($query, $conn)){
	if($row = mysql_fetch_assoc($result)){
		$output = $row['img'];
	}
}

//default image if image is empty
if (empty($output)) $output = file_get_contents($_SERVER['DOCUMENT_ROOT']."/theme/images/no_image.jpg");

ob_clean(); //empty the output buffer

//output the header for image mime type
header('Content-type: image/jpeg');
echo $output;

exit;
?>
