<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Global format helper functions
|
*/


/**
 * Auto text format
 *
 * @param string $string
 * @return string
 */
function auto_text_format($string, $first_line_strong = false) {
	$return = '';
	if ($string != '') {

		// Explode lines
		$is_first_line = true;
		$new_lines = explode("\n", $string);

		if (! empty($new_lines)) {
			foreach ($new_lines as $line) {
				if ($line != '-') {

					// Trim line
					$line = trim($line);

					// Check URLs
					if (substr($line, 0, 4) == 'www.') {
						$line = 'http://'.$line;
					}

					// Make first line strong
					if (($first_line_strong == true) && ($is_first_line == true)) {
						$return .= '<strong>';
					}

					// Return URL
					if (! empty($line) && (filter_var($line, FILTER_VALIDATE_URL) == true)) {
						$return .= '<a href="'.$line.'" class="external_link" target="_blank">'.str_replace(array('http://', 'https://'), '', $line).'</a><br>';
					}

					// Return email
					else if (! empty($line) && (filter_var($line, FILTER_VALIDATE_EMAIL) == true)) {
						$return .= safe_mailto($line, $line, array('class' => 'email_link')).'<br>';
					}

					// Return line without changes
					else {
						$return .= $line.'<br>';
					}

					// Close strong first line
					if (($first_line_strong == true) && ($is_first_line == true)) {
						$return .= '</strong>';
						$first_line_strong = false;
					}
				}
			}
		}

		// Remove duplicated breaklines
		$return = str_replace('<br><br>', '<br>', $return);

	}

	return $return;
}


/**
 * Overwrite Auto-linker:
 * Include also dash sign at the end of an url (improved regex).
 *
 * Automatically links URL and Email addresses.
 * Note: There's a bit of extra code here to deal with
 * URLs or emails that end in a period. We'll strip these
 * Off and add them after the link.
 *
 * @param	string	the string
 * @param	string	the type: email, url, or both
 * @param	bool	whether to create pop-up links
 * @return	string
 */
function auto_link($str, $type = 'both', $popup = FALSE) {

	// Find and replace any URLs.
	if ($type !== 'email' && preg_match_all('#(\w*://|www\.)[a-z0-9äöü]+(-+[a-z0-9äöü]+)*(\.[a-z0-9äöü]+(-+[a-z0-9äöü]+)*)+(/([^\s()<>;]+\w)?/?)?#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
	{
		// Set our target HTML if using popup links.
		$target = ($popup) ? ' target="_blank" rel="noopener"' : '';

		// We process the links in reverse order (last -> first) so that
		// the returned string offsets from preg_match_all() are not
		// moved as we add more HTML.
		foreach (array_reverse($matches) as $match)
		{
			// $match[0] is the matched string/link
			// $match[1] is either a protocol prefix or 'www.'
			//
			// With PREG_OFFSET_CAPTURE, both of the above is an array,
			// where the actual value is held in [0] and its offset at the [1] index.
			$a = '<a href="'.(strpos($match[1][0], '/') ? '' : 'http://').$match[0][0].'"'.$target.'>'.$match[0][0].'</a>';
			$str = substr_replace($str, $a, $match[0][1], strlen($match[0][0]));
		}
	}

	// Find and replace any emails.
	if ($type !== 'url' && preg_match_all('#([\w\.\-\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[^[:punct:]\s])#i', $str, $matches, PREG_OFFSET_CAPTURE))
	{
		foreach (array_reverse($matches[0]) as $match)
		{
			if (filter_var($match[0], FILTER_VALIDATE_EMAIL) !== FALSE)
			{
				$str = substr_replace($str, safe_mailto($match[0]), $match[1], strlen($match[0]));
			}
		}
	}

	return $str;
}



/**
 * Transform newlines to paragraphs
 *
 * @access public
 * @param mixed $text
 * @return string
 */
function nl2p($text) {
	return '<p>'.str_replace(array("\r\n", "\r", "\n"), '</p><p>', $text).'</p>';
}



/**
 * Encoded Mailto Link
 *
 * Create a spam-protected mailto link written in Javascript
 *
 * @access	public
 * @param	string	the email address
 * @param	string	the link title
 * @param	mixed	any attributes
 * @return	string
 */
 function safe_mailto($email, $title = '', $attributes = '') {
	$title = (string) $title;
	if ($title == "") {
		$title = $email;
	}

	for ($i = 0; $i < 16; $i++) {
		$x[] = substr('<a href="mailto:', $i, 1);
	}

	for ($i = 0; $i < strlen($email); $i++) {
		$x[] = "|".ord(substr($email, $i, 1));
	}

	$x[] = '"';

	if ($attributes != '') {
		if (is_array($attributes)) {
			foreach ($attributes as $key => $val) {
				$x[] =  ' '.$key.'="';
				for ($i = 0; $i < strlen($val); $i++) {
					$x[] = "|".ord(substr($val, $i, 1));
				}
				$x[] = '"';
			}
		}
		else {
			for ($i = 0; $i < strlen($attributes); $i++) {
				$x[] = substr($attributes, $i, 1);
			}
		}
	}

	$x[] = '>';

	$temp = [];
	for ($i = 0; $i < strlen($title); $i++) {
		$ordinal = ord($title[$i]);

		if ($ordinal < 128) {
			$x[] = "|".$ordinal;
		}
		else {
			if (count($temp) == 0) {
				$count = ($ordinal < 224) ? 2 : 3;
			}

			$temp[] = $ordinal;
			if (count($temp) == $count) {
				$number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);
				$x[] = "|".$number;
				$count = 1;
				$temp = [];
			}
		}
	}

	$x[] = '<'; $x[] = '/'; $x[] = 'a'; $x[] = '>';

	$x = array_reverse($x);
	ob_start();

?><script type="text/javascript">
//<![CDATA[
var l=new Array();
<?php
$i = 0;
foreach ($x as $val){ ?>l[<?php echo $i++; ?>]='<?php echo $val; ?>';<?php } ?>

for (var i = l.length-1; i >= 0; i=i-1){
if (l[i].substring(0, 1) == '|') document.write("&#"+unescape(l[i].substring(1))+";");
else document.write(unescape(l[i]));}
//]]>
</script><?php

	$buffer = ob_get_contents();
	ob_end_clean();
	return $buffer;
}



/**
 * Create a web friendly URL slug from a string.
 *
 * Although supported, transliteration is discouraged because
 *     1) most web browsers support UTF-8 characters in URLs
 *     2) transliteration causes a loss of information
 *
 * @author Sean Murphy <sean@iamseanmurphy.com>
 * @copyright Copyright 2012 Sean Murphy. All rights reserved.
 * @license http://creativecommons.org/publicdomain/zero/1.0/
 *
 * @param string $str
 * @param array $options
 * @return string
 */
function url_slug($str, $options = []) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

	$defaults = array(
		'delimiter' => '-',
		'limit' => null,
		'lowercase' => true,
		'replacements' => array(),
		'transliterate' => false,
	);

	// Merge options
	$options = array_merge($defaults, $options);

	$char_map = array(
		// Latin
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
		'ß' => 'ss',
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
		'ÿ' => 'y',
		// Latin symbols
		'©' => '(c)',
		// Greek
		'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
		'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
		'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
		'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
		'Ϋ' => 'Y',
		'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
		'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
		'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
		'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
		'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
		// Turkish
		'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
		'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
		// Russian
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
		'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
		'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
		'я' => 'ya',
		// Ukrainian
		'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
		// Czech
		'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
		'Ž' => 'Z',
		'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
		'ž' => 'z',
		// Polish
		'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
		'Ż' => 'Z',
		'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
		'ż' => 'z',
		// Latvian
		'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
		'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
		'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
		'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);

	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}

	// Replace non-alphanumeric characters with our delimiter
	$str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);

	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}



/**
 * Set the first char of a string to uppercase, even if its a multibyte char
 *
 * @param string $str
 * @return string
 */
function ucfirst_utf8($str) {
  $a = mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8');
  return $a . mb_substr($str, 1, null, 'UTF-8');
}



/**
 * Check if string contains html tags
 * 
 * @param string $string
 * @return bool
 */
function contains_html_tags($string) {
    return strlen($string) != strlen(strip_tags($string));
}



/**
 * Output text with or without html tags
 * 
 * @param string $text
 * @return string
 */
function output_text($text) {

	// Text with html tags
    if (contains_html_tags($text)) {
		return $text;
	}

	// Text without html tags
	else {
		return auto_link(nl2br($text), 'both', true);
	}

}