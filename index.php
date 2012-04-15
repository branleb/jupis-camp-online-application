<?php

function create_fdf ($strings, $keys)
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

session_start();

function val($v) {
	if (isset($_POST["send"]) and isset($_POST[$v])) {
		return iconv("UTF8", "ISO-8859-1", stripslashes($_POST[$v]));
	}
	return "";
}

function val_checked($v, $v2 = null) {
	if (isset($_POST["send"]) and isset($_POST[$v])) {
		if ($v2 == null) {
			return true;
		} elseif ($_POST[$v] == $v2) {
			return true;
		}
	}
	return false;
}

$errs = array();
function handle_error($str) {
	global $errs;
	$errs[] = $str;
}

if (isset($_POST["send"])) {
	$name = val("name");
	$nick = val("nick");
	$anschrift = val("anschrift");
	$wohnort = val("wohnort");
	$mailadresse = val("mailadresse");
	$geburtsdatum = strtotime(val("geburtsdatum"));
	$notfallname = val("notfallname");
	$notfallnummer = val("notfallnummer");
	switch (val("ernaehrung")) {
	case "alles":
		$ernaehrung = 1;
		break;
	case "vegi":
		$ernaehrung = 2;
		break;
	case "vegan":
		$ernaehrung = 3;
		break;
	}
	$unvertraeglichkeiten = val_checked("unvertraeglichkeiten") ? val("unvertraeglichkeiten2") : null;
	$allergien = val("allergien");
	$schwimmen = true || val_checked("schwimmen");
	$anmerkungen = val("anmerkungen");

	if (trim($name) == "") {
		handle_error("Das Feld \"Name\" muss ausgef&uuml;lt sein.");
	} else if (trim($anschrift) == "") {
		handle_error("Das Feld \"Anschrift\" muss ausgef&uuml;lt sein.");
	} else if (trim($wohnort) == "") {
		handle_error("Das Feld \"Wohnort\" muss ausgef&uuml;lt sein.");
	} else if (trim($mailadresse) == "") {
		handle_error("Das Feld \"Mailadresse\" muss ausgef&uuml;lt sein.");
	} else if ($geburtsdatum == 0) {
		handle_error("Das Feld \"Geburtsdatum\" muss ausgef&uuml;lt sein.");
	} else if ($geburtsdatum > mktime(0,0,0,date("n"),date("j"),date("Y")-18)) {
		handle_error("Dieses Anmeldeformular ist nur f&uuml;r vollj&auml;hrige Teilnehmer.");
	} else {
		$rand = rand(10000,99999) . "-" . md5(microtime());
		$fdf = array();
		$fdf["Name"] = $name;
		$fdf["Nick"] = $nick;
		$fdf["Anschrift"] = $anschrift;
		$fdf["Wohnort"] = $wohnort;
		$fdf["Mailadresse"] = $mailadresse;
		$fdf["Geburtsdatum"] = date("d.m.Y", $geburtsdatum);
		$fdf["NotfallAnschrift"] = $notfallname;
		$fdf["NotfallTelefon"] = $notfallnummer;
		$fdf["Lebensmittelunvertr#C3#A4glichkeiten"] = ($unvertraeglichkeiten == null) ? "" : $unvertraeglichkeiten;
		$fdf["Allergien"] = $allergien;
		$fdf["Anmerkungen"] = $anmerkungen;
		$fdf_opt = array();
		$fdf_opt["Essenswunsch"] = $ernaehrung;
		$fdf_opt["Lebensmittelunvertr#C3#A4glichkeitCheck"] = ($unvertraeglichkeiten == null) ? "Off" : "Yes";
		$fdf_opt["DarfSchwimmen"] = $schwimmen ? "Yes" : "Off";
		file_put_contents("temp/" . $rand . ".fdf", create_fdf($fdf, $fdf_opt));
		file_put_contents("temp/vorlage.pdf", file_get_contents("http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Camp-2012-Anmeldung.pdf"));
		system("pdftk temp/vorlage.pdf fill_form temp/" . $rand . ".fdf output temp/" . $rand . ".pdf flatten");
		unlink("temp/" . $rand . ".fdf");
		$_SESSION["data"] = $fdf;
		$_SESSION["options"] = $fdf_opt;
		$_SESSION["rand"] = $rand;

		header("Location: ?check");
	}
}

if (isset($_REQUEST["mail"])) {
	$seperator = md5(microtime(true));
	$headers  = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-Type: multipart/mixed; boundary=" . $seperator . "\r\n";
	$headers .= "From: " . $_SESSION["data"]["Mailadresse"] . "\r\n";
	$body  = "Multipart: MIME" . "\r\n";
	$body .= "--{$seperator}" . "\r\n";
	$body .= "Content-Type: text/plain; charset=utf-8" . "\r\n";
	$body .= "" . "\r\n";
	$body .= "Schau in den Anhang - diese Anmeldung kam per Webformular ;)" . "\r\n";
	$body .= print_r($_SESSION["data"], true) . "\r\n";
	$body .= print_r($_SESSION["options"], true) . "\r\n";
	$body .= "" . "\r\n";
	$body .= "--{$seperator}" . "\r\n";
	$body .= "Content-Type: application/pdf" . "\r\n";
	$body .= "Content-Disposition: attachment; filename=anmeldung-" . $_SESSION["data"]["Name"] . ".pdf" . "\r\n";
	$body .= "Content-Transfer-Encoding: base64" . "\r\n";
	$body .= "" . "\r\n";
	$body .= chunk_split(base64_encode(file_get_contents("temp/" . $_SESSION["rand"] . ".pdf"))) . "\r\n";
	$body .= "--{$seperator}--" . "\r\n";
	unlink("temp/" . $_SESSION["rand"] . ".pdf");
#	mail("patrick.rauscher@junge-piraten.de", "Neue Camp-Anmeldung", $body, $headers);
	mail("camp@junge-piraten.de", "Neue Camp-Anmeldung", $body, $headers);

	header("Location: ?finished");
}

header("Content-Type: text/html; charset=utf-8");
?>
<html>
<head>
<title>Anmeldung zum JuPi-Camp 2012</title>
<style type="text/css">
body {font-family:sans-serif; margin:15px; background:gray;}
fieldset {border:2px solid black; width: 900px; margin:0px auto 20px auto; background:white;}
#logo {float:right; margin:20px;}
dt {float:left;}
dd {margin-left:200px; margin-bottom:15px;}
.konto dd {margin-bottom:0px;}
.error {margin: 0px auto 20px auto; color:#bb0000; background:white; border:5px solid #bb0000; width:900px;}
body>p {background:white; width:900px; margin: 0px auto 20px auto; border:2px solid black;}
</style>
</head>
<body>
<?php
if (false) {
?>
<div class="error"><strong>Achtung!</strong> Dieses Formular ist noch nicht produktiv, sondern bisher nur zum Testen vorgesehen!</div>
<?php
}
if (isset($_REQUEST["check"])) {
?>
<p>Bitte kontrolliere die <a href="temp/<?php echo $_SESSION["rand"]; ?>.pdf">erzeugte Anmeldung</a> auf Fehler und <a href="?mail">fahre fort</a>.</p>
<?php
} else if (isset($_REQUEST["finished"])) {
?>
<p>Deine Anmeldung wurde eingereicht. Du wirst eine Anmeldebest&auml;tigung erhalten, sobald deine Anmeldung bearbeitet wird. Wir sehen uns im Sommer!</p>
<?php
} else {
?>
<p>Diese Online-Anmeldung gilt nur f&uuml;r vollj&auml;hrige Camp-Teilnehmer. Solltest du 
 minderj&auml;hrig sein oder die Online-Anmeldung nicht benutzen wollen, verschicke die
 <a href="//wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Camp-2012-Anmeldung.pdf">
 Anmeldung</a> als Brief oder Fax.</p>
<?php
	foreach ($errs as $err) {
		echo "<div class=\"error\">" . $err . "</div>";
	}
?>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" accept-charset="utf-8">
 <fieldset>
  <img src="//www.junge-piraten.de/logo.png" id="logo" />
  <h1>Junge Piraten</h1>
  <p>Anmeldung zum Camp 2012</p>
  <p>Hiermit melde ich mich <!-- / mein Kind --></p>
  <dl>
   <dt>Name:</dt>
   <dd><input type="text" name="name" value="<?php echo val("name"); ?>" size="20" /></dd>
   <dt>Nick:</dt>
   <dd><input type="text" name="nick" value="<?php echo val("nick"); ?>" size="20" /></dd>
   <dt>Anschrift:</dt>
   <dd><input type="text" name="anschrift" value="<?php echo val("anschrift"); ?>" size="40" /></dd>
   <dt>Wohnort:</dt>
   <dd><input type="text" name="wohnort" value="<?php echo val("wohnort"); ?>" size="40" /></dd>
   <dt>Mailadresse:</dt>
   <dd><input type="text" name="mailadresse" value="<?php echo val("mailadresse"); ?>" size="40" /></dd>
   <dt>Geburtsdatum:</dt>
   <dd><input type="text" name="geburtsdatum" value="<?php echo val("geburtsdatum"); ?>" size="20" /></dd>
  </dl>
  <p>zum Camp der Jungen Piraten 2012 in 49377 Vechta (Niedersachsen) vom 5. bis zum
   12. August verbindlich an. Die Campteilnahme kostet 75 Euro. In diesem Preis enthalten
   sind Unterkunft und Verpflegung (Frühstück, Mittagessen, Abendessen) sowie Wasser,
   Kaffee und Tee. Weitere nichtalkoholische Getr&auml;nke sind vor Ort zum Selbstkostenpreis zu
   erwerben. Die An- und Abreise zum Camp ist selbst zu finanzieren und erfolgt auf eigenes
   Risiko.</p>
  <p>Im Notfall soll</p>
  <dl>
   <dt>Name:</dt>
   <dd><input type="text" name="notfallname" value="<?php echo val("notfallname"); ?>" size="40" /></dd>
   <dt>Telefonnummer:</dt>
   <dd><input type="text" name="notfallnummer" value="<?php echo val("notfallnummer"); ?>" size="40" /></dd>
  </dl>
  <p>informiert werden.</p>
 </fieldset>
 <fieldset>
  <p>Spezielle Essensw&uuml;nsche:</p>
  <p>
   <input type="radio" name="ernaehrung" value="alles" <?php if (val_checked("ernaehrung", "alles")) { echo "checked=\"checked\""; } ?> /> Alles
   <input type="radio" name="ernaehrung" value="vegi" <?php if (val_checked("ernaehrung", "vegi")) { echo "checked=\"checked\""; } ?> /> Vegetarisch
   <input type="radio" name="ernaehrung" value="vegan" <?php if (val_checked("ernaehrung", "vegan")) { echo "checked=\"checked\""; } ?> /> Vegan
  </p>
  <p>
   <input type="checkbox" name="unvertraeglichkeiten" <?php if (val_checked("unvertraeglichkeiten")) { echo "checked=\"checked\""; } ?> />Lebensmittelunvertr&auml;glichkeiten:
   <input type="text" name="unvertraeglichkeiten2" value="<?php echo val("unvertraeglichkeiten2"); ?>" size="40" />
  </p>
  <p>Bekannte Allergien:</p>
  <input type="text" name="allergien" value="<?php echo val("allergien"); ?>" size="60" />
  <p>Mir ist bewusst, dass bei einer Stornierung der Campteilnahme 25 % des
   Teilnahmebeitrags (18,75 &euro;) f&auml;llig werden. Bei einer Stornierung weniger als 5 Tage vor
   Campbeginn werden 75 % (56,25 &euro;) f&auml;llig.</p>
  <p>Au&szlig;erdem ist mir bewusst, dass ich<!-- / mein Kind -->, sofern ich mich<!-- / es sich --> mehrfach den
   Anweisungen des Aufsichtspersonals widersetze<!-- / widersetzt --> und die Campordnung
   mutwillig st&ouml;re<!-- / st&ouml;rt -->, auf eigene Kosten nach Hause geschickt werden kann.<br />
   Aus dem Ausschlu&szlig; w&auml;hrend des Camps ergibt sich kein R&uuml;ckzahlungsanspruch des
   Teilnahmebeitrags.</p>
  <p>Weitere Anmerkungen:</p>
  <textarea name="anmerkungen" rows="5" cols="60"><?php echo htmlentities(val("anmerkungen"), ENT_COMPAT | ENT_HTML401, "UTF8"); ?></textarea>
  <!--
   <input type="checkbox" name="schwimmen" <?php if (val_checked("schwimmen")) { echo "checked=\"checked\""; } ?> /> Mein Kind darf ohne Aufsicht schwimmen.
  -->
  <p>Den Teilnahmebeitrag von 75 &euro; &uuml;berweise ich bis zum 5. Juli 2012 auf folgendes Konto:</p>
  <dl class="konto">
   <dt>Kontoinhaber:</dt>
   <dd>Junge Piraten</dd>
   <dt>Kontonummer:</dt>
   <dd>6016506900</dd>
   <dt>Bank:</dt>
   <dd>GLS Gemeinschaftsbank</dd>
   <dt>Bankleitzahl:</dt>
   <dd>43060967</dd>
   <dt>&Uuml;berweisungszweck:</dt>
   <dd>JuPi-Camp 2012 <i>&lt;Teilnehmername&gt;</i>, <i>&lt;Teilnehmervorname&gt;</I></dd>
  </dl>
  <input type="submit" name="send" value="Fortfahren" />
 </fieldset>
</form>
</body>
</html>
<?php
}
?>
