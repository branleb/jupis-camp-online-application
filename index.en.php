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
		Util::handle_error("The Field \"Name\" must be completed.");
	} else if (trim($anschrift) == "") {
		Util::handle_error("The Field \"Address\"  must be completed.");
	} else if (trim($wohnort) == "") {
		Util::handle_error("The Field \"Residence\"  must be completed.");
	} else if (trim($mailadresse) == "") {
		Util::handle_error("The Field \"E-Mail Address\"  must be completed.");
	} else if ($geburtsdatum == 0) {
		Util::handle_error("The Field \"Date of Birth\"  must be completed.");
	} else if ($geburtsdatum > mktime(0,0,0,date("n"),date("j"),date("Y")-18)) {
		Util::handle_error("This Form is not intended to be used by minors due to legal issues.");
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
		file_put_contents("temp/vorlage.pdf", file_get_contents("http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Camp-2013-Application.pdf"));
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
	$body .= "Schau in den Anhang - diese Anmeldung kam per englischem Webformular ;)" . "\r\n";
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
	mail("camp@junge-piraten.de", "Neue (internationale) Camp-Anmeldung", $body, $headers);

	header("Location: ?finished");
}

header("Content-Type: text/html; charset=utf-8");
?>
<html>
<head>
<title>Application for JuPi-Wintercamp 2013</title>
<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,300,700' />
<link rel='stylesheet' href='main.css' />
</head>
<body>
<?php
if (false) {
?>
<div class="error"><strong>Attention!</strong> This form is not intended for production use. It exists only for testing purposes!</div>
<?php
}
if (isset($_REQUEST["check"])) {
?>
<p>Please check the <a href="temp/<?php echo $_SESSION["rand"]; ?>.pdf">created Application</a> for mistakes and <a href="?mail">continue</a>.</p>
<?php
} else if (isset($_REQUEST["finished"])) {
?>
<p>Your Application has been filed. You'll receive a confirmation once your application has been processed!</p>
<?php
} else {
?>
<p>This online application is not valid for minors. If you're a minor or in case you don&apos;t
 like to use this online application you should send this
 <a href="//wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Wintercamp-2013-Application.pdf">
 application</a> by letter or fax.</p>
<?php
	foreach (Util::get_errors() as $err) {
		echo "<div class=\"error\">" . $err . "</div>";
	}
?>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" accept-charset="utf-8">
 <fieldset>
  <img src="//www.junge-piraten.de/logo.png" id="logo" />
  <h1>Application for Wintercamp 2013</h1>
  <p>I hereby bindingly register myself<!-- / mein Kind --></p>
  <dl>
   <dt>Full Name:</dt>
   <dd><input type="text" name="name" value="<?php echo Util::val("name"); ?>" size="20" /></dd>
   <dt>Nick:</dt>
   <dd><input type="text" name="nick" value="<?php echo Util::val("nick"); ?>" size="20" /></dd>
   <dt>Address:</dt>
   <dd><input type="text" name="anschrift" value="<?php echo Util::val("anschrift"); ?>" size="40" /></dd>
   <dt>Residence:</dt>
   <dd><input type="text" name="wohnort" value="<?php echo Util::val("wohnort"); ?>" size="40" /></dd>
   <dt>E-Mail Address:</dt>
   <dd><input type="text" name="mailadresse" value="<?php echo Util::val("mailadresse"); ?>" size="40" /></dd>
   <dt>Date of Birth:</dt>
   <dd><input type="text" name="geburtsdatum" value="<?php echo Util::val("geburtsdatum"); ?>" size="20" /></dd>
  </dl>
  <p>for Junge Piraten Winteramp 2013 in 13599 Berlin from July 25<sup>th</sup> to 31<sup>st</sup>.
   Participation fee is 75 Euro for (Supporting-)Members of Junge Piraten e.V. and (young)
   Foreign Pirates and 95 Euro for others, including room and board (breakfast, lunch,
   dinner as well as water and apple juice). Travel to and from has to be paid
   individually by the attendees.</p>
  <p>In case of emergency please contact:</p>
  <dl>
   <dt>Name:</dt>
   <dd><input type="text" name="notfallname" value="<?php echo Util::val("notfallname"); ?>" size="40" /></dd>
   <dt>Phone:</dt>
   <dd><input type="text" name="notfallnummer" value="<?php echo Util::val("notfallnummer"); ?>" size="40" /></dd>
  </dl>
  <p>&nbsp;</p>
 </fieldset>
 <fieldset>
  <p>Special food requirements:</p>
  <p>
   <input type="radio" name="ernaehrung" value="alles" id="alles" <?php if (Util::val_checked("ernaehrung", "alles")) { echo "checked=\"checked\""; } ?> /> <label for="alles">everything</label>
   <input type="radio" name="ernaehrung" value="vegi" id="vegi" <?php if (Util::val_checked("ernaehrung", "vegi")) { echo "checked=\"checked\""; } ?> /> <label for="vegi">vegetarian</label>
   <input type="radio" name="ernaehrung" value="vegan" id="vegan" <?php if (Util::val_checked("ernaehrung", "vegan")) { echo "checked=\"checked\""; } ?> /> <label for="vegan">vegan</label>
  </p>
  <p>
   <input type="checkbox" name="unvertraeglichkeiten" id="unvtr" <?php if (Util::val_checked("unvertraeglichkeiten")) { echo "checked=\"checked\""; } ?> /><label for="unvtr">Food intolerances:</label>
   <input type="text" name="unvertraeglichkeiten2" value="<?php echo Util::val("unvertraeglichkeiten2"); ?>" size="40" />
  </p>
  <p>Known Allergies:</p>
  <input type="text" name="allergien" value="<?php echo Util::val("allergien"); ?>" size="60" />
  <p>I am aware that there is a cancellation fee of 25% of the original participation cost,
   which will rise to 75% in case of rescession 30 days or less prior to begin.
	</p>

	<p>
   I am further aware that if I <!-- or my child --> do not follow the directions of the supervisory staff and/or
   willingly disrupt camp order on multiple occasions, I<!-- /it --> can be sent home at own costs.
   Payments on account will not be refunded.</p>
  <p>Former notes:</p>
  <textarea name="anmerkungen" rows="5" cols="60"><?php echo htmlentities(Util::val("anmerkungen"), ENT_COMPAT, "UTF-8"); ?></textarea>
  <!--
   <input type="checkbox" name="schwimmen" <?php if (Util::val_checked("schwimmen")) { echo "checked=\"checked\""; } ?> /> My child is allowed to swim without supervision
  -->
  <p>Please transfer the application fee of 75 &euro; / 95 &euro; to the following bank account until June 30<sup>th</sup> 2013:</p>
  <dl class="konto">
   <dt>Account Holder:</dt>
   <dd>Junge Piraten</dd>
   <dt>Name of Bank:</dt>
   <dd>GLS Gemeinschaftsbank</dd>
   <dt>IBAN:</dt>
   <dd>DE76 4306 0967 6016 5069 00</dd>
   <dt>BIC:</dt>
   <dd>GENODEM1GLS</dd>
   <dt>Reason for Payment:</dt>
   <dd>JuPi-Camp 2013<i>&lt;family name&gt;</i>, <i>&lt;given name&gt;</i></dd>
  </dl>
  <input type="submit" name="send" value="Send" />
 </fieldset>
</form>
</body>
</html>
<?php
}
