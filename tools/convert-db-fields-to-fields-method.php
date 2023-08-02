<?php
/*

	Use this SQL to generate this for any base classes in the future
   
   SELECT TABLE_NAME, GROUP_CONCAT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'amrayndb' and COLUMN_NAME not in ('id', 'date_added', 'last_updated', 'status') group by TABLE_NAME
     
 */
$string = <<<S
from_address,from_name,to_address,to_name,reply_to_address,reply_to_name,subject,text,html_text,attach_path,sent_date,priority
  	
S;
function underscoreToCamel( $string, $first_char_caps = false)
	{
	    if( $first_char_caps == true ) {
	        $string[0] = strtoupper($string[0]);
	    }
	    $func = create_function('$c', 'return strtoupper($c[1]);');
	    return preg_replace_callback('/_([a-z])/', $func, $string);
	}
$list = explode(",", trim($string));

$fields = array();
foreach ($list as $field) {
	$fields[] = "\"" . underscoreToCamel(trim($field)) . "\"";
}

$fieldsStr = implode(", \n                                       ", $fields);

$method = <<<L
	public function fields()
	{
		return array_merge(parent::fields(),  array(\n                                       $fieldsStr));
	}
L;

echo $method;