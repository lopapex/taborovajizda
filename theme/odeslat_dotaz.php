<?php

//
// Chybové kódy:
// 1 - eprihlaska: Byla zadána neplatná e-mailová adresa.
// 2 - eprihlaska: Chybí některý z povinných údajů.
// 3 - eprihlaska: Selhalo nalezení aktuálního tábora v DB.
// 4 - eprihlaska: Selhalo vložení nové přihlášky do DB.
// 5 - eprihlaska: Selhalo vložení nové přihlášky, protože již existuje v DB.
// 6 - dotaz: Byla zadána neplatná e-mailová adresa.
// 7 - dotaz: Nebyly vyplněny povinné položky.
// 8 - newsletter: Byla zadána neplatná e-mailová adresa.
// 9 - newsletter: Selhalo vložení emailu do DB.
// 10 - newsletter: Chybí některý z povinných údajů (e-mail).
// 11 - newsletter: Selhalo vložení emailu do DB - e-mail již existuje.
// 12 - newsletter: Selhalo vložení emailu do DB - e-mail již existuje (nebyl ověřen).
// 13 - newsletter: Znovuověření není možné. Byla zadána neplatná e-mailová adresa.
// 14 - newsletter: Znovuověření není možné. Adresa není v databázi nebo je již ověřena.
//
// 100 - Prázdný POST.
// 101 - Chyba při spojení s DB
// 102 - Chybné použiti skriptu - nezpracovatelný požadavek.
//////////////////////////////////////////////////////////////////////

// běží mimo wordpress - je tedy potřeba načíst aspoň základ
require_once('../../../wp-config.php');

define('IS_FULL', false);

function verifyRC($rc)
{
    // be liberal in what you receive
    if (!preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $rc, $matches)) {
        return false;
    }

    list(, $year, $month, $day, $ext, $c) = $matches;

    if ($c === '') {
        $year += $year < 54 ? 1900 : 1800;
    } else {
        // kontrolní číslice
        $mod = ($year . $month . $day . $ext) % 11;
        if ($mod === 10) {
            $mod = 0;
        }
        if ($mod !== (int) $c) {
            return false;
        }

        $year += $year < 54 ? 2000 : 1900;
    }

    // k měsíci může být připočteno 20, 50 nebo 70
    if ($month > 70 && $year > 2003) {
        $month -= 70;
    } elseif ($month > 50) {
        $month -= 50;
    } elseif ($month > 20 && $year > 2003) {
        $month -= 20;
    }

    // kontrola data
    if (!checkdate($month, $day, $year)) {
        return false;
    }

    return true;
}

// ať víme kam se vracet (rekonstrukce URL bez zbytečného balastu)
!empty($_SERVER['HTTP_REFERER']) && $url = parse_url($_SERVER['HTTP_REFERER']);
empty($url) && !empty($_SERVER['SERVER_NAME']) && $url = parse_url($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME']);
$url = empty($url) ? 'https://www.taborovajizda.cz/' : $url['scheme']."://".$url['host'].$url['path'];
//print_log($url);
if (empty($_POST)&&empty($_REQUEST['validate'])&&empty($_REQUEST['unsubscribe'])&&empty($_REQUEST['validation_resend'])) {
    // voláno s prázdným $_POST -> končíme
    //print_log($_REQUEST);
    header("Location: $url".'?error=100');
    exit(100);
}

//tady bude potřeba naše DB
if (!empty($_REQUEST['eprihlaska'])||!empty($_REQUEST['newsletter'])||!empty($_REQUEST['validate'])
    ||!empty($_REQUEST['unsubscribe'])||!empty($_REQUEST['validation_resend'])) {
    $conn = new mysqli(TJ_DB_HOST, TJ_DB_USER, TJ_DB_PASSWORD);
    // Zkontroluj připojení k DB
    if ($conn->connect_error) {
        // chyba při spojení s DB -> končíme
        //print_log($conn);
        header("Location: $url".'?error=101');
        exit(101);
    }
    // změň db na databázi s kontakty
    $conn->select_db(TJ_DB_NAME);
}

if (!empty($_REQUEST['eprihlaska'])) {
    $kontakt_email = filter_var($_POST['kontakt_email'], FILTER_VALIDATE_EMAIL);
    if (!$kontakt_email) {
        // Nebyl zadán správný email -> končíme
        header("Location: $url".'?error=1');
        exit(1);
    }

    if (empty($_POST['jmeno'])||empty($_POST['prijmeni'])||empty($_POST['datum_nar'])||empty($_POST['rodne_c'])||empty($_POST['adresa'])) {
        // Některé z povinných polí je prázdné (nemělo by se stát, kontrola probíhá již na klientu) -> končíme
        header("Location: $url".'?error=2');
        exit(2);
    }

    $sql = "SELECT * FROM `tabory` ORDER BY `id` DESC LIMIT 1;";
    if (!($result = $conn->query($sql))) {
        // Selhalo nalezení aktuálního tábora v DB -> končíme
        header("Location: $url".'?error=3');
        exit(3);
    }

    $row = $result->fetch_assoc();
    $tabor_rok = $row['rok'];
    $tabor_nazev = $row['nazev'];
    $tabor_cena = $row['cena'];
    $tabor_od = date_format(date_create_from_format('!Y-m-d', $row['termin_od']), 'j. n.');
    $tabor_do = date_format(date_create_from_format('!Y-m-d', $row['termin_do']), 'j. n.');
    $tabor_id = $row['id'];
    $jmeno = $conn->real_escape_string($_POST['jmeno']);
    $prijmeni = $conn->real_escape_string($_POST['prijmeni']);
    // echo $_POST['datum_nar'];
    $datum_nar_obj = date_create_from_format('!Y-m-d', $_POST['datum_nar']);// "!" => část času se bude rovnat 00:00:00
    $datum_nar = $conn->real_escape_string($_POST['datum_nar']);
    $rodne_c = $conn->real_escape_string($_POST['rodne_c']);
    if (!verifyRC($rodne_c)) {
	// selhalo ověření RČ
        header("Location: $url".'?error=15');
        exit(15);
    }
    $adresa = $conn->real_escape_string($_POST['adresa']);
    $nastup = $conn->real_escape_string($_POST['nastup']);
    $vystup = $conn->real_escape_string($_POST['vystup']);
    $spolubydlici = empty($_POST['spolubydlici']) ? 'NULL' : "'".$conn->real_escape_string($_POST['spolubydlici'])."'";
    $kontakt_jmeno = empty($_POST['kontakt_jmeno']) ? 'NULL' : "'".$conn->real_escape_string($_POST['kontakt_jmeno'])."'";
    $kontakt_tel = empty($_POST['kontakt_tel']) ? 'NULL' : "'".$conn->real_escape_string(preg_replace('/\s+/', '', $_POST['kontakt_tel']))."'";
    $poznamka = empty($_POST['dotaz']) ? 'NULL' : "'".$conn->real_escape_string($_POST['dotaz'])."'";

    $sql = "INSERT INTO `prihlasky` "
            ."(`jmeno`, `prijmeni`, `datum_nar`, `rc`, `adresa`, `id_tabora`, `spolubydlici`, `nastup`, `vystup`, `kontakt_jmeno`, `kontakt_tel`, `kontakt_email`, `poznamka`)"
            ." VALUES "
            ."('$jmeno', '$prijmeni', '$datum_nar', '$rodne_c', '$adresa', '$tabor_id', $spolubydlici, '$nastup', '$vystup', $kontakt_jmeno, $kontakt_tel, '$kontakt_email', $poznamka)";
    if (!($result = $conn->query($sql))) {
        // Selhalo vložení přihlášky do DB (pravděpodobně již existuje - porušení UNQ) -> končíme
        if (1062 == $conn->errno) {
            // přihláška již existuje (nebo alespoň kombinace rodné číslo a id tábora
            header("Location: $url".'?error=5');
            exit(5);
        }
        // nějaká jiná chyba při vkládání
        //header("Location: $url".'?error=4');
        print_r($conn);
        exit(4);
    }

	$is_full_string = IS_FULL ? 
		"Omlouváme se. TÁBOR JE JIŽ PLNĚ OBSAZEN!\r\n".
		"Vaše přihláška bude zařazena do seznamu náhradníku, pokud se uvolní místo, ozveme se. TÁBOR ZATÍM NEPLAŤTE! \r\n".
		"Děkujeme za zájem i za pochopení.\r\n\r\n" : "";
	
    $mail_telo = 	"Dobrý den,\n".
                    "toto je automaticky vygenerovaný e-mail ze stránek https://taborovajizda.cz a byl Vám odeslán, protože Vaše adresa byla uvedena jako kontaktní e-mail ".
                    "v elektronické přihlášce na letní dětský tábor \"$tabor_nazev\"\r\n\r\n".
		
					$is_full_string.
					
                    "Jméno dítěte: ".$jmeno." ".$prijmeni."\r\n".
                    "Datum narození: ".date_format($datum_nar_obj, 'j. n. Y')."\r\n".
                    "Rodné číslo: ".$rodne_c."\r\n".
                    "Adresa bydliště: ".$adresa."\r\n".
                    "Kontakt jméno: ".(($kontakt_jmeno != 'NULL') ? trim($kontakt_jmeno, "'") : " -")."\r\n".
                    "Kontakt telefon: ".(($kontakt_tel != 'NULL') ? trim($kontakt_tel, "'") : " -")."\r\n".
                    "Kontakt e-mail: ".$kontakt_email."\r\n".
                    "S kým chci být ve stanu: ".(($spolubydlici != 'NULL') ? trim($spolubydlici, "'") : " -")."\r\n".
                    "Kde se k vám připojím: ".$nastup."\r\n".
                    "Z tábora s Vámi pojedu: ".$vystup."\r\n".
                    "Doplňující informace:\r\n".$_POST['dotaz']."\r\n\r\n".
                    "Tábor se koná v areálu Plátěná osada v termínu $tabor_od – $tabor_do $tabor_rok. Cena tábora je $tabor_cena Kč. Za každého dalšího sourozence dítěte však zaplatíte pouze ".($tabor_cena-500)." Kč. ".
                    "Platbu proveďte nejpozději do konce *května* na účet 27-3094190207/0100 a jako variabilní symbol uveďte rodné číslo svého dítěte. Pokud provádíte platbu pro více dětí najednou (sourozenci), použijte rodné číslo jednoho z nich.\r\n\r\n".
                    "Rádi Vám vystavíme fakturu pro zaměstnavatele, pokud chcete čerpat příspěvěk z FKSP
                    nebo potvrzení pro pojišťovnu o absolvování tábora\r\n\r\n".
                    "V den odjezdu nezapomeňte potřebné dokumenty (potvrzení o zdravotní způsobilosti, potvrzení bezinfekčnosti, kartičku zdravotní pojišťovny, souhlas s využitím mediálních materiálů pro propagaci tábora, zmocnění pro zdravotníka)\r\n\r\n".
                    "Přejeme krásný den a těšíme se v srpnu na viděnou\r\nTým Táborové jízdy.";
    $headers = array();
    $headers[] = 'From: Táborová jízda <info@taborovajizda.cz>';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Cc: Táborová jízda <info@taborovajizda.cz>';
	$is_full_subject = IS_FULL ? " (TÁBOR JIŽ OBSAZEN)" : "";
    wp_mail($kontakt_email, "Elektronická přihláška na tábor \"$tabor_nazev\"" . $is_full_subject, $mail_telo, $headers);
    header("Location: $url".'?success=1');
    exit(0);
} elseif (!empty($_REQUEST['dotaz'])) {
    if (empty($_POST['jmeno'])||empty($_POST['email'])||empty($_POST['dotaz'])) {
        // nevyplněny povinné položky
        header("Location: $url".'?error=7');
        exit(7);
    }
    $jmeno = $_POST['jmeno'];
    $email = $_POST['email'];
    $dotaz = $_POST['dotaz'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Nebyl zadán správný email -> končíme
        header("Location: $url".'?error=6');
        exit(6);
    }

    $headers = array();
    $headers[] = 'From: Táborová jízda <info@taborovajizda.cz>';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = "Reply-To: $email";
    //DISABLED wp_mail("info@taborovajizda.cz", "Dotaz z www - $jmeno", $dotaz, $headers);
    header("Location: $url".'?success=2');
    exit(0);
} elseif (!empty($_REQUEST['newsletter'])) {
    if (empty($_POST['newsletter-email'])) {
        // nevyplněny povinné položky
        header("Location: $url".'?error=10');
        exit(10);
    }
    $email = filter_var($_POST['newsletter-email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        // Nebyl zadán správný email -> končíme
        header("Location: $url".'?error=8');
        exit(8);
    }
    // vytvoř unikátní validační ID
    $validate_id = bin2hex(random_bytes(10));
    $email = $conn->real_escape_string($email);
    $sql = "insert into newsletter (email, validate_id) values ('$email', '$validate_id');";
    if (!$conn->query($sql)) {
        if (1062 == $conn->errno) {
            // email již existuje
            $sql = "select is_validated from newsletter where email='$email';";
            $result = $conn->query($sql);
            $row = $result->fetch_row();
            // a nebyl ověřen
            if (!$row[0]) { // !is_validated
                header("Location: $url".'?error=12&email='.$email);
                exit(12);
            }
            // a byl ověřen
            else {
                header("Location: $url".'?error=11');
                exit(11);
            }
        }
        header("Location: $url".'?error=9'); //chyba při vkládání do tabulky
        exit(9);
    }
    //vše je v pořádku
    $conn->close();
    // odešli informační emaily
    wp_mail('info@taborovajizda.cz', 'Nová registrace odběru novinek', "Zaregistrován odběr. \nE-mail: $email");
    $headers = 'From: Táborová jízda <info@taborovajizda.cz>';
    $headers .= "\r\n".'Content-Type: text/plain; charset=UTF-8';
    $message = "Právě jste se registrovali k odběru novinek na webové stránce www.taborovajizda.cz.\n\n".
                "Pro dokončení je nutné ověřit váš e-mail na následujícím odkaze: \nhttps://www.taborovajizda.cz/odeslat-dotaz/?validate=$validate_id \n\n".
                "Pokud si již nebudete přát dostávat upozornění nebo si myslíte, že byl Váš e-mail použit neoprávněně, použijte následující odkaz k odhlášení odběru: \n".
                "https://www.taborovajizda.cz/odeslat-dotaz/?unsubscribe=$validate_id \n\n";
    $message .= "Díky za zájem.\nVaše Táborová jízda\n\nNa tento e-mail prosím neodpovídejte.";
    wp_mail($email, "Registrace na www.taborovajizda.cz", $message, $headers);

    header("Location: $url".'?success=3');
    exit(0);
}
// Validace emailové adresy
//////////////////////////////
elseif (!empty($_REQUEST['validate'])) {
    //print_log('validate');
    $validate_id = $conn->real_escape_string($_REQUEST['validate']);
    $sql = "update newsletter set is_validated=true where validate_id = '$validate_id';";
    $conn->query($sql);
    if ($conn->affected_rows == 1) {
        echo "Podařilo se ověřit e-mail. Můžete zavřít okno prohlížeče.";
    } else {
        echo "Nepodařilo se ověřit e-mail.";
    }
    exit(0);
}
// Odhlášení odběru
////////////////////////////////
elseif (!empty($_REQUEST['unsubscribe'])) {
    $validate_id = $conn->real_escape_string($_REQUEST['unsubscribe']);
    $sql = "delete from newsletter where validate_id = '$validate_id';";
    $conn->query($sql);
    if ($conn->affected_rows == 1) {
        echo "Váš e-mail byl odstraněn z databáze.";
    } else {
        echo "Nepodařilo provést odhlášení. Zkontrolujte prosím adresu odkazu. Nebo kontaktujte správce.";
    }
    exit(0);
} elseif (!empty($_REQUEST['validation_resend'])) {
    $email = filter_var($_REQUEST['validation_resend'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        // nebyl zadán platný email
        header("Location: $url".'?error=13');
        exit(13);
    }
    $email = $conn->real_escape_string($email);
    $sql = "select validate_id from newsletter where email='$email' and is_validated=false;";
    //print_log($sql);
    $result = $conn->query($sql);
    if (!$result || ($result->num_rows < 1)) {
        //pravděpodobně podvržený email (není v DB anebo už je ověřen)
        //header("Location: $url".'?error=14');
        exit(14);
    }
    $row = $result->fetch_row();
    $validate_id = $row[0];
    // odešli informační emaily
    wp_mail('info@taborovajizda.cz', 'Žádost o znovuověření', "Zpráva zaslána na e-mail: $email");
    $headers = 'From: Táborová jízda <info@taborovajizda.cz>';
    $headers .= "\r\n".'Content-Type: text/plain; charset=UTF-8';
    $message = "Právě jste se registrovali k odběru novinek na webové stránce www.taborovajizda.cz.\n\n".
                "Pro dokončení je nutné ověřit váš e-mail na následujícím odkaze: \nhttps://www.taborovajizda.cz/odeslat-dotaz/?validate=$validate_id \n\n".
                "Pokud si již nebudete přát dostávat upozornění nebo si myslíte, že byl Váš e-mail použit neoprávněně, použijte následující odkaz k odhlášení odběru: \n".
                "https://www.taborovajizda.cz/odeslat-dotaz/?unsubscribe=$validate_id \n\n";
    $message .= "Díky za zájem.\nVaše Táborová jízda\n\nNa tento e-mail prosím neodpovídejte.";
    wp_mail($email, "Registrace na www.taborovajizda.cz", $message, $headers);

    header("Location: $url".'?success=4');
    exit(0);
}
// při běžném použití, bychom tu neměli skončit, ale kdo říká, že to každý bude požívat, tak jak má :D
header("Location: $url".'?error=102');
exit(102);
