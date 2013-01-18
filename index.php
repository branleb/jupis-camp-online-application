<?php
require_once __DIR__.DIRECTORY_SEPARATOR.'util.php';
session_start();

if (isset($_POST["send"])) {
	$name = Util::val("name");
	$nick = Util::val("nick");
	$anschrift = Util::val("anschrift");
	$wohnort = Util::val("wohnort");
	$mailadresse = Util::val("mailadresse");
	$geburtsdatum = strtotime(Util::val("geburtsdatum"));
	$notfallname = Util::val("notfallname");
	$notfallnummer = Util::val("notfallnummer");
	switch (Util::val("ernaehrung")) {
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
	$unvertraeglichkeiten = Util::val_checked("unvertraeglichkeiten") ? Util::val("unvertraeglichkeiten2") : null;
	$allergien = Util::val("allergien");
	$schwimmen = true || Util::val_checked("schwimmen");
	$anmerkungen = Util::val("anmerkungen");

	if (trim($name) == "") {
		Util::handle_error("Das Feld \"Name\" muss ausgef&uuml;lt sein.");
	} else if (trim($anschrift) == "") {
		Util::handle_error("Das Feld \"Anschrift\" muss ausgef&uuml;lt sein.");
	} else if (trim($wohnort) == "") {
		Util::handle_error("Das Feld \"Wohnort\" muss ausgef&uuml;lt sein.");
	} else if (trim($mailadresse) == "") {
		Util::handle_error("Das Feld \"Mailadresse\" muss ausgef&uuml;lt sein.");
	} else if ($geburtsdatum == 0) {
		Util::handle_error("Das Feld \"Geburtsdatum\" muss ausgef&uuml;lt sein.");
	} else if ($geburtsdatum > mktime(0,0,0,date("n"),date("j"),date("Y")-18)) {
		Util::handle_error("Dieses Anmeldeformular ist nur f&uuml;r vollj&auml;hrige Teilnehmer.");
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
		file_put_contents("temp/" . $rand . ".fdf", Util::create_fdf($fdf, $fdf_opt));
		file_put_contents("temp/vorlage.pdf", file_get_contents("http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Wintercamp-2013-Anmeldung.pdf"));
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
	mail("camp@junge-piraten.de", "Neue Camp-Anmeldung", $body, $headers);

	header("Location: ?finished");
}

header("Content-Type: text/html; charset=utf-8");
?>
<html>
  <head>
    <title>Anmeldung zum JuPi-Wintercamp 2013</title>
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,300,700' />
    <link rel='stylesheet' href='main.css' />
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
 <a href="//wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Wintercamp-2013-Anmeldung.pdf">
 Anmeldung</a> als Brief oder Fax.</p>
<?php
	foreach (Util::get_errors() as $err) {
		echo "<div class=\"error\">" . $err . "</div>";
	}
?>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" accept-charset="utf-8">
 <fieldset>
  <img src="//www.junge-piraten.de/logo.png" id="logo" />
  <h1>Anmeldung zum Wintercamp 2013</h1>
  <p>Hiermit melde ich mich <!-- / mein Kind --></p>
  <dl>
   <dt>Name:</dt>
   <dd><input type="text" name="name" value="<?php echo Util::val("name"); ?>" size="20" /></dd>
   <dt>Nick:</dt>
   <dd><input type="text" name="nick" value="<?php echo Util::val("nick"); ?>" size="20" /></dd>
   <dt>Anschrift:</dt>
   <dd><input type="text" name="anschrift" value="<?php echo Util::val("anschrift"); ?>" size="40" /></dd>
   <dt>Wohnort:</dt>
   <dd><input type="text" name="wohnort" value="<?php echo Util::val("wohnort"); ?>" size="40" /></dd>
   <dt>Mailadresse:</dt>
   <dd><input type="text" name="mailadresse" value="<?php echo Util::val("mailadresse"); ?>" size="40" /></dd>
   <dt>Geburtsdatum:</dt>
   <dd><input type="text" name="geburtsdatum" value="<?php echo Util::val("geburtsdatum"); ?>" size="20" /></dd>
  </dl>
  <p>zum Camp der Jungen Piraten 2013 in 59821 Arnsberg (Nordrhein-Westfalen) vom 22. bis zum
   16. März verbindlich an. Die Campteilnahme kostet 115 Euro,
   für (F&ouml;rder-)Mitglieder der Jungen Piraten sowie für (Junge) Piraten aus dem Ausland
   kostet es nur 95 Euro. In diesem Preis enthalten sind Unterkunft und Verpflegung (Frühstück, Mittagessen, Abendessen) sowie Wasser und Apfelschorle.
   Die An- und Abreise zum Camp ist selbst zu finanzieren und erfolgt auf eigenes
   Risiko.</p>
  <p>Im Notfall soll</p>
  <dl>
   <dt>Name:</dt>
   <dd><input type="text" name="notfallname" value="<?php echo Util::val("notfallname"); ?>" size="40" /></dd>
   <dt>Telefonnummer:</dt>
   <dd><input type="text" name="notfallnummer" value="<?php echo Util::val("notfallnummer"); ?>" size="40" /></dd>
  </dl>
  <p>informiert werden.</p>
 </fieldset>
 <fieldset>
  <p>Spezielle Essensw&uuml;nsche:</p>
  <p>
   <input type="radio" name="ernaehrung" value="alles" id="alles" <?php if (Util::val_checked("ernaehrung", "alles")) { echo "checked=\"checked\""; } ?> /> <label for="alles">Alles</label>
   <input type="radio" name="ernaehrung" value="vegi" id="vegi" <?php if (Util::val_checked("ernaehrung", "vegi")) { echo "checked=\"checked\""; } ?> /> <label for="vegi">Vegetarisch</label>
   <input type="radio" name="ernaehrung" value="vegan" id="vegan" <?php if (Util::val_checked("ernaehrung", "vegan")) { echo "checked=\"checked\""; } ?> /> <label for="vegan">Vegan</label>
  </p>
  <p>
   <input type="checkbox" name="unvertraeglichkeiten" id="unvtr" <?php if (Util::val_checked("unvertraeglichkeiten")) { echo "checked=\"checked\""; } ?> /> <label for="unvtr">Lebensmittelunvertr&auml;glichkeiten:</label>
   <input type="text" name="unvertraeglichkeiten2" value="<?php echo Util::val("unvertraeglichkeiten2"); ?>" size="40" />
  </p>
  <p>Bekannte Allergien:</p>
  <input type="text" name="allergien" value="<?php echo Util::val("allergien"); ?>" size="60" />
  <p>Mir ist bewusst, dass bei einer Stornierung der Campteilnahme 25 % des
   Teilnahmebeitrags f&auml;llig werden. Bei einer Stornierung weniger als 30 Tage vor
   Campbeginn werden 75 % f&auml;llig.</p>
  <p>Au&szlig;erdem ist mir bewusst, dass ich<!-- / mein Kind -->, sofern ich mich<!-- / es sich --> mehrfach den
   Anweisungen des Aufsichtspersonals widersetze<!-- / widersetzt --> und die Campordnung
   mutwillig st&ouml;re<!-- / st&ouml;rt -->, auf eigene Kosten nach Hause geschickt werden kann.<br />
   Aus dem Ausschlu&szlig; w&auml;hrend des Camps ergibt sich kein R&uuml;ckzahlungsanspruch des
   Teilnahmebeitrags.</p>
  <p>Weitere Anmerkungen:</p>
  <textarea name="anmerkungen" rows="5" cols="60"><?php echo htmlentities(Util::val("anmerkungen"), ENT_COMPAT, "UTF-8"); ?></textarea>
  <!--
   <input type="checkbox" name="schwimmen" <?php if (Util::val_checked("schwimmen")) { echo "checked=\"checked\""; } ?> /> Mein Kind darf ohne Aufsicht schwimmen.
  -->
  <p>Den Teilnahmebeitrag von 95 &euro; / 115 &euro; &uuml;berweise ich bis zum 12. Februar 2013 auf folgendes Konto:</p>
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
   <dd>JuPi-Wintercamp 2013 <i>&lt;Teilnehmername&gt;</i>, <i>&lt;Teilnehmervorname&gt;</I></dd>
  </dl>
  <input type="submit" name="send" value="Fortfahren" />
 </fieldset>
</form>
</body>
</html>
<?php
}
