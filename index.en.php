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
		file_put_contents("temp/vorlage.pdf", file_get_contents("http://wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Camp-2012-Application.pdf"));
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
<title>Application for JuPi-Camp 2012</title>
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
 <a href="//wiki.junge-piraten.de/wiki/Spezial:Dateipfad/JuPi-Camp-2012-Application.pdf">
 application</a> by letter or fax.</p>
<?php
	foreach (Util::get_errors() as $err) {
		echo "<div class=\"error\">" . $err . "</div>";
	}
?>
<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" accept-charset="utf-8">
 <fieldset>
  <img src="//www.junge-piraten.de/logo.png" id="logo" />
  <h1>Junge Piraten</h1>
  <p>Application for Camp 2012</p>
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
  <p>for Junge Piraten Camp 2011 in 49377 Vechta (Lower Saxony) from August 5<sup>th</sup> to 12<sup>th</sup>.
   Participation fee is 75 &euro;, including room and board (breakfast, lunch, dinner as well as water, coffee &amp; tea).
   Other non-alcoholic drinks can be purchased at the campsite at cost price. Travel to and from has to be paid
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
   <input type="radio" name="ernaehrung" value="alles" <?php if (Util::val_checked("ernaehrung", "alles")) { echo "checked=\"checked\""; } ?> /> everything
   <input type="radio" name="ernaehrung" value="vegi" <?php if (Util::val_checked("ernaehrung", "vegi")) { echo "checked=\"checked\""; } ?> /> vegetarian
   <input type="radio" name="ernaehrung" value="vegan" <?php if (Util::val_checked("ernaehrung", "vegan")) { echo "checked=\"checked\""; } ?> /> vegan
  </p>
  <p>
   <input type="checkbox" name="unvertraeglichkeiten" <?php if (Util::val_checked("unvertraeglichkeiten")) { echo "checked=\"checked\""; } ?> />Food intolerances:
   <input type="text" name="unvertraeglichkeiten2" value="<?php echo Util::val("unvertraeglichkeiten2"); ?>" size="40" />
  </p>
  <p>Known Allergies:</p>
  <input type="text" name="allergien" value="<?php echo Util::val("allergien"); ?>" size="60" />
  <p>I am aware that there is a cancellation fee of 25% of the original participation cost (18,75 &euro;),
   which will rise to 75% (56,25 &euro;) in case of rescession five days or less prior to begin.
	</p>

	<p>
   I am further aware that if I <!-- or my child --> do not follow the directions of the supervisory staff and/or
   willingly disrupt camp order on multiple occasions, I<!-- /it --> can be sent home at own costs.
   Payments on account will not be refunded.</p>
  <p>Former notes:</p>
  <textarea name="anmerkungen" rows="5" cols="60"><?php echo htmlentities(Util::val("anmerkungen"), ENT_COMPAT | ENT_HTML401, "UTF8"); ?></textarea>
  <!--
   <input type="checkbox" name="schwimmen" <?php if (Util::val_checked("schwimmen")) { echo "checked=\"checked\""; } ?> /> My child is allowed to swim without supervision
  -->
  <p>Please transfer the application fee of 75€ to the following bank account until June 5<sup>th</sup> 2012:</p>
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
   <dd>JuPi-Camp <i>&lt;family name&gt;</i>, <i>&lt;given name&gt;</i></dd>
  </dl>
  <input type="submit" name="send" value="Fortfahren" />
 </fieldset>
</form>
</body>
</html>
<?php
}
