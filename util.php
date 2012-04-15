<?php

class Util
{
	protected static $errs = array();

	public static function create_fdf ($strings, $keys)
	{
			$fdf = "%FDF-1.2\n%âãÏÓ\n";
			$fdf .= "1 0 obj \n<</FDF<</Fields[";

			foreach ($strings as $key => $value)
			{
					$key = addcslashes($key, "\n\r\t\\()");
					$value = addcslashes(iconv("UTF8", "ISO-8859-1", $value), "\n\r\t\\()");
					$fdf .= "<</V({$value})/T({$key})>>";
			}
			foreach ($keys as $key => $value)
			{
					$key = addcslashes($key, "\n\r\t\\()");
					$value = addcslashes($value, "\n\r\t\\()");
					$fdf .= "<</V/{$value}/T({$key})>>";
			}

			$fdf .= "]";
			$fdf .= ">>>>\nendobj\ntrailer\n<</Root 1 0 R>>\n";
			$fdf .= "%%EOF\n";

			return $fdf;
	}

	public static function val($v) {
		if (isset($_POST["send"]) and isset($_POST[$v])) {
			return iconv("UTF8", "ISO-8859-1", stripslashes($_POST[$v]));
		}
		return "";
	}

	public static function val_checked($v, $v2 = null) {
		if (isset($_POST["send"]) and isset($_POST[$v])) {
			if ($v2 == null) {
				return true;
			} elseif ($_POST[$v] == $v2) {
				return true;
			}
		}
		return false;
	}

	public static function handle_error($str) {
		self::$errs[] = $str;
	}

	public static function get_errors() {
		return self::$errs;
	}
}
