<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

/* =========================
   SMTP
========================= */
$smtp_host = 'smtp.hostinger.com';
$smtp_user = 'kontakt@xperibase.pl';
$smtp_pass = 'Xperibase1031!';
$smtp_port = 465;

/* =========================
   WALIDACJA
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: survey.html');
    exit;
}

$segment = $_POST['segment_wybrany'] ?? '';
$date    = date('Y-m-d H:i:s');
$survey_page = in_array($segment, ['hospitality', 'agencje'], true) ? 'survey_com.html' : 'survey.html';

/* =========================
   NORMALIZACJA POST
========================= */
$POST = [];
foreach ($_POST as $k => $v) {
    if ($k === 'segment_wybrany') continue;
    if (is_array($v)) $v = implode(', ', $v);
    
    // Czyszczenie: zamiana enterów na spacje (żeby nie łamać wierszy w CSV) i średników na przecinki
    $v = str_replace(["\r\n", "\r", "\n"], ' ', $v);
    $v = str_replace(';', ',', $v);
    $POST[$k] = trim($v);
}

/* =========================
   UJEDNOLICONY SCHEMAT CSV
========================= */
$CSV_SCHEMA = [
    'segment', 'data',
    'stanowisko', 'stanowisko_inne', 'profil', 'profil_inne',
    'wielkosc_organizacji', 'lokalizacja_forma', 'skala_odbiorcy',
    'wyzwania_czy_widza', 'wyzwania_jakie', 'wyzwania_inne', 'odpowiedzi_obecne',
    'pierwsze_wrazenie',
    'priorytet_1', 'priorytet_2', 'priorytet_3', 'priorytet_4', 'priorytet_5', 'priorytet_6', 'priorytet_7',
    'dopasowanie_1', 'dopasowanie_2', 'dopasowanie_3', 'dopasowanie_4', 'dopasowanie_5', 'dopasowanie_6', 'dopasowanie_7',
    'model_wspolpracy', 'model_inne', 'glowna_wartosc', 'bariery',
    'potencjal', 'dalsza_rozmowa', 'warunek_idealny',
    'budzet_deklarowany', 'list_polecajacy', 'esg_wsparcie',
    'zgoda_kontakt', 'organizacja', 'dane_kontaktowe'
];

/* =========================
   MAPOWANIE PÓL Z HTML NA CSV
========================= */
$MAP = [
    'hospitality' => [
        'stanowisko' => 'h_stanowisko', 'stanowisko_inne' => 'h_stanowisko_inne', 'profil' => 'h_profil', 'profil_inne' => 'h_profil_inne',
        'wielkosc_organizacji' => 'h_wielkosc', 'lokalizacja_forma' => 'h_lokalizacja', 'skala_odbiorcy' => 'h_skala',
        'wyzwania_czy_widza' => 'h_wyzwania_ident', 'wyzwania_jakie' => 'h_wyzwania_ist', 'wyzwania_inne' => 'h_wyzwania_inne', 'odpowiedzi_obecne' => 'h_odpowiedzi',
        'pierwsze_wrazenie' => 'h_wrazenie',
        'priorytet_1' => 'h_prio_emocje', 'priorytet_2' => 'h_prio_multi', 'priorytet_3' => 'h_prio_wow', 'priorytet_4' => 'h_prio_inno', 'priorytet_5' => 'h_prio_perso', 'priorytet_6' => 'h_prio_dna', 'priorytet_7' => 'h_prio_integra',
        'dopasowanie_1' => 'h_match_emocje', 'dopasowanie_2' => 'h_match_multi', 'dopasowanie_3' => 'h_match_wow', 'dopasowanie_4' => 'h_match_inno', 'dopasowanie_5' => 'h_match_perso', 'dopasowanie_6' => 'h_match_dna', 'dopasowanie_7' => 'h_match_integra',
        'model_wspolpracy' => 'h_model', 'model_inne' => 'h_model_inne', 'glowna_wartosc' => 'h_wartosc', 'bariery' => 'h_bariery',
        'potencjal' => 'h_potencjal', 'dalsza_rozmowa' => 'h_rozmowa', 'warunek_idealny' => 'h_warunek',
        'budzet_deklarowany' => 'h_budzet', 'list_polecajacy' => 'h_list_polecajacy', 'esg_wsparcie' => 'h_esg_wsparcie'
    ],
    'agencje' => [
        'stanowisko' => 'a_stanowisko', 'stanowisko_inne' => 'a_stanowisko_inne', 'profil' => 'a_profil', 'profil_inne' => 'a_profil_inne',
        'wielkosc_organizacji' => 'a_wielkosc', 'lokalizacja_forma' => 'a_lokalizacja', 'skala_odbiorcy' => 'a_skala',
        'wyzwania_czy_widza' => 'a_wyzwania_ident', 'wyzwania_jakie' => 'a_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'a_wrazenie',
        'priorytet_1' => 'ap_emocje', 'priorytet_2' => 'ap_multi', 'priorytet_3' => 'ap_wow', 'priorytet_4' => 'ap_inno', 'priorytet_5' => 'ap_perso', 'priorytet_6' => 'ap_dna', 'priorytet_7' => 'ap_wdro',
        'dopasowanie_1' => 'am_koncepcja', 'dopasowanie_2' => 'am_narracja', 'dopasowanie_3' => 'am_copy', 'dopasowanie_4' => 'am_val', 'dopasowanie_5' => 'am_inno', 'dopasowanie_6' => 'am_flex', 'dopasowanie_7' => 'am_integra',
        'model_wspolpracy' => 'a_model_coll', 'model_inne' => 'a_model_inne', 'glowna_wartosc' => 'a_wartosc', 'bariery' => 'a_bariery',
        'potencjal' => 'a_potencjal', 'dalsza_rozmowa' => 'a_rozmowa', 'warunek_idealny' => 'a_warunek',
        'budzet_deklarowany' => 'a_budzet', 'list_polecajacy' => 'a_list_polecajacy', 'esg_wsparcie' => 'a_esg_wsparcie'
    ],
    'kultura' => [
        'stanowisko' => 'c_stanowisko', 'stanowisko_inne' => 'c_stanowisko_inne', 'profil' => 'c_profil', 'profil_inne' => 'c_profil_inne',
        'wielkosc_organizacji' => 'c_skala', 'lokalizacja_forma' => 'c_lokalizacja', 'skala_odbiorcy' => 'c_odbiorcy',
        'wyzwania_czy_widza' => 'c_wyzwania_ident', 'wyzwania_jakie' => 'c_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'c_wrazenie',
        'priorytet_1' => 'cp_emocje', 'priorytet_2' => 'cp_multi', 'priorytet_3' => 'cp_glebia', 'priorytet_4' => 'cp_inno', 'priorytet_5' => 'cp_inkluz', 'priorytet_6' => 'cp_kurator', 'priorytet_7' => 'cp_edu',
        'dopasowanie_1' => 'cm_aktyw', 'dopasowanie_2' => 'cm_narr', 'dopasowanie_3' => 'cm_zapam', 'dopasowanie_4' => 'cm_dostep', 'dopasowanie_5' => 'cm_tech', 'dopasowanie_6' => 'cm_wart', 'dopasowanie_7' => 'cm_adapt',
        'model_wspolpracy' => 'c_model_coll', 'model_inne' => 'c_model_inne', 'glowna_wartosc' => 'c_wartosc', 'bariery' => 'c_bariery',
        'potencjal' => 'c_potencjal', 'dalsza_rozmowa' => 'c_rozmowa', 'warunek_idealny' => 'c_warunek',
        'budzet_deklarowany' => 'c_budzet', 'list_polecajacy' => 'c_list_polecajacy', 'esg_wsparcie' => ''
    ],
    'edukacja' => [
        'stanowisko' => 'e_stanowisko', 'stanowisko_inne' => 'e_stanowisko_inne', 'profil' => 'e_profil', 'profil_inne' => 'e_profil_inne',
        'wielkosc_organizacji' => 'e_skala', 'lokalizacja_forma' => 'e_forma', 'skala_odbiorcy' => 'e_uczestnicy',
        'wyzwania_czy_widza' => 'e_wyzwania_ident', 'wyzwania_jakie' => 'e_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'e_wrazenie',
        'priorytet_1' => 'ep_angaz', 'priorytet_2' => 'ep_dosw', 'priorytet_3' => 'ep_multi', 'priorytet_4' => 'ep_zapam', 'priorytet_5' => 'ep_potrzeby', 'priorytet_6' => 'ep_inkluzja', 'priorytet_7' => 'ep_program',
        'dopasowanie_1' => 'em_akt', 'dopasowanie_2' => 'em_multi', 'dopasowanie_3' => 'em_zapam', 'dopasowanie_4' => 'em_potrz', 'dopasowanie_5' => 'em_tech', 'dopasowanie_6' => 'em_wiek', 'dopasowanie_7' => 'em_prog',
        'model_wspolpracy' => 'e_model_coll', 'model_inne' => 'e_model_inne', 'glowna_wartosc' => 'e_wartosc', 'bariery' => 'e_bariery',
        'potencjal' => 'e_potencjal', 'dalsza_rozmowa' => 'e_rozmowa', 'warunek_idealny' => 'e_warunek',
        'budzet_deklarowany' => 'e_budzet', 'list_polecajacy' => 'e_list_polecajacy', 'esg_wsparcie' => ''
    ],
    'zdrowie' => [
        'stanowisko' => 'z_stanowisko', 'stanowisko_inne' => 'z_stanowisko_inne', 'profil' => 'z_profil', 'profil_inne' => 'z_profil_inne',
        'wielkosc_organizacji' => 'z_skala', 'lokalizacja_forma' => 'z_forma', 'skala_odbiorcy' => 'z_pacjenci',
        'wyzwania_czy_widza' => 'z_wyzwania_ident', 'wyzwania_jakie' => 'z_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'z_wrazenie',
        'priorytet_1' => 'zp_bezpieczenstwo', 'priorytet_2' => 'zp_regulacja', 'priorytet_3' => 'zp_multi', 'priorytet_4' => 'zp_redukcja', 'priorytet_5' => 'zp_perso', 'priorytet_6' => 'zp_dostep', 'priorytet_7' => 'zp_adaptacja',
        'dopasowanie_1' => 'zm_regulacja', 'dopasowanie_2' => 'zm_bezpieczenstwo', 'dopasowanie_3' => 'zm_angaz', 'dopasowanie_4' => 'zm_perso', 'dopasowanie_5' => 'zm_tech', 'dopasowanie_6' => 'zm_neuro', 'dopasowanie_7' => 'zm_integra',
        'model_wspolpracy' => 'z_model_coll', 'model_inne' => 'z_model_inne', 'glowna_wartosc' => 'z_wartosc', 'bariery' => 'z_bariery',
        'potencjal' => 'z_potencjal', 'dalsza_rozmowa' => 'z_rozmowa', 'warunek_idealny' => 'z_warunek',
        'budzet_deklarowany' => 'z_budzet', 'list_polecajacy' => 'z_list_polecajacy', 'esg_wsparcie' => ''
    ],
    'sektor_publiczny' => [
        'stanowisko' => 'p_stanowisko', 'stanowisko_inne' => 'p_stanowisko_inne', 'profil' => 'p_profil', 'profil_inne' => 'p_profil_inne',
        'wielkosc_organizacji' => 'p_skala', 'lokalizacja_forma' => 'p_forma', 'skala_odbiorcy' => 'p_odbiorcy',
        'wyzwania_czy_widza' => 'p_wyzwania_ident', 'wyzwania_jakie' => 'p_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'p_wrazenie',
        'priorytet_1' => 'pp_zrozumialosc', 'priorytet_2' => 'pp_aktywne', 'priorytet_3' => 'pp_wielozmyslowe', 'priorytet_4' => 'pp_zapam', 'priorytet_5' => 'pp_dostepnosc', 'priorytet_6' => 'pp_spojnosc', 'priorytet_7' => 'pp_dotarcie',
        'dopasowanie_1' => 'pm_aktyw', 'dopasowanie_2' => 'pm_edukacja', 'dopasowanie_3' => 'pm_zapam', 'dopasowanie_4' => 'pm_dostepnosc', 'dopasowanie_5' => 'pm_tech', 'dopasowanie_6' => 'pm_adapt', 'dopasowanie_7' => 'pm_zgodnosc',
        'model_wspolpracy' => 'p_model_coll', 'model_inne' => 'p_model_inne', 'glowna_wartosc' => 'p_wartosc', 'bariery' => 'p_bariery',
        'potencjal' => 'p_potencjal', 'dalsza_rozmowa' => 'p_rozmowa', 'warunek_idealny' => 'p_warunek',
        'budzet_deklarowany' => 'p_budzet', 'list_polecajacy' => 'p_list_polecajacy', 'esg_wsparcie' => ''
    ],
    'inne' => [
        'stanowisko' => 'i_rola', 'stanowisko_inne' => 'i_rola_inna', 'profil' => 'i_charakter', 'profil_inne' => 'i_charakter_inne',
        'wielkosc_organizacji' => 'i_skala', 'lokalizacja_forma' => 'i_forma', 'skala_odbiorcy' => 'i_odbiorcy',
        'wyzwania_czy_widza' => 'i_wyzwania_ident', 'wyzwania_jakie' => 'i_wyzwania_ist', 'wyzwania_inne' => '', 'odpowiedzi_obecne' => '',
        'pierwsze_wrazenie' => 'i_wrazenie',
        'priorytet_1' => 'ip_aktywne', 'priorytet_2' => 'ip_multi', 'priorytet_3' => 'ip_zapam', 'priorytet_4' => 'ip_eksperyment', 'priorytet_5' => 'ip_elastycznosc', 'priorytet_6' => 'ip_spojnosc', 'priorytet_7' => 'ip_unikalnosc',
        'dopasowanie_1' => 'im_tworzenie', 'dopasowanie_2' => 'im_elastyczne', 'dopasowanie_3' => 'im_wsparcie', 'dopasowanie_4' => 'im_custom', 'dopasowanie_5' => 'im_tech', 'dopasowanie_6' => 'im_phygital', 'dopasowanie_7' => 'im_skala',
        'model_wspolpracy' => 'i_model_coll', 'model_inne' => 'i_model_inne', 'glowna_wartosc' => 'i_wartosc', 'bariery' => 'i_bariery',
        'potencjal' => 'i_potencjal', 'dalsza_rozmowa' => 'i_rozmowa', 'warunek_idealny' => 'i_warunek',
        'budzet_deklarowany' => 'i_budzet', 'list_polecajacy' => 'i_list_polecajacy', 'esg_wsparcie' => ''
    ]
];

/* =========================
   BUDOWANIE WIERSZA I DANYCH DO MAILA
========================= */
$row = [];
$mail_content = []; // Przechowuje pary klucz-wartość do czytelnego maila

foreach ($CSV_SCHEMA as $unified_col) {
    if ($unified_col === 'segment') {
        $val = $segment;
    } elseif ($unified_col === 'data') {
        $val = $date;
    } elseif (in_array($unified_col, ['zgoda_kontakt', 'organizacja', 'dane_kontaktowe'])) {
        // Pola wspólne z HTML nie mają prefiksów
        $val = $POST[$unified_col] ?? '';
    } else {
        // Sprawdzamy mapowanie dla wybranego segmentu
        $html_field_name = $MAP[$segment][$unified_col] ?? '';
        $val = ($html_field_name !== '') ? ($POST[$html_field_name] ?? '') : '';
    }
    
    $row[] = $val;
    
    // Do maila dodajemy tylko te ujednolicone kolumny, w których jest jakaś odpowiedź
    if ($val !== '' && $unified_col !== 'segment' && $unified_col !== 'data') {
        $mail_content[$unified_col] = $val;
    }
}

/* =========================
   ZAPIS CSV
========================= */
$csvDir  = __DIR__ . '/data';
$csvFile = $csvDir . '/xperibase-survey.csv';

if (!file_exists($csvDir)) {
    mkdir($csvDir, 0755, true);
}

$writeHeader = !file_exists($csvFile);
$fp = fopen($csvFile, 'a');

if ($writeHeader) {
    fputs($fp, "\xEF\xBB\xBF"); // Wymuszenie odczytu w UTF-8 (polskie znaki) w Excelu
    fputcsv($fp, $CSV_SCHEMA, ';');
}

fputcsv($fp, $row, ';');
fclose($fp);

/* =========================
   MAIL (Ujednolicony wygląd)
========================= */
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = $smtp_host;
$mail->SMTPAuth = true;
$mail->Username = $smtp_user;
$mail->Password = $smtp_pass;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = $smtp_port;
$mail->CharSet = 'UTF-8';

$mail->setFrom($smtp_user, 'Ankieta XperiBase');
$mail->addAddress($smtp_user);
$mail->isHTML(true);
$mail->Subject = "Nowa odpowiedź [$segment] – Ankieta XperiBase";

$body = "<h2>OTRZYMANO NOWĄ ODPOWIEDŹ</h2>";
$body .= "<p><strong>Segment:</strong> " . htmlspecialchars($segment) . "</p>";
$body .= "<p><strong>Data:</strong> " . htmlspecialchars($date) . "</p>";
$body .= "<hr>";

$MAIL_LABELS = [
    'budzet_deklarowany' => 'Deklarowany budżet',
    'list_polecajacy' => 'Gotowość do wystawienia listu polecającego',
    'esg_wsparcie' => 'Gotowość do wsparcia w formule ESG'
];

foreach ($mail_content as $key => $val) {
    $clean_key = $MAIL_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    $body .= "<p><strong>$clean_key:</strong> " . htmlspecialchars($val) . "</p>";
}

$mail->Body = $body;
$mail->send();

/* =========================
   PODZIĘKOWANIE
========================= */
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dziękujemy | XperiBase</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; }
    html, body {
        margin: 0;
        min-height: 100%;
        background: #000;
        color: #fff;
        font-family: 'Inter', sans-serif;
    }
    .background-image {
        position: fixed;
        inset: 0;
        background-image: url('GRAPH/tlo.webp');
        background-size: cover;
        background-position: center;
        opacity: 0.5;
        z-index: 1;
    }
    .contrast-overlay {
        position: fixed;
        inset: 0;
        background: radial-gradient(circle at center, rgba(0,0,0,0.35) 0%, rgba(0,0,0,0.9) 100%);
        z-index: 2;
    }
    .page {
        position: relative;
        z-index: 3;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
    }
    .card {
        width: 100%;
        max-width: 760px;
        background: rgba(10, 10, 10, 0.88);
        border: 1px solid rgba(255,255,255,0.12);
        backdrop-filter: blur(18px);
        padding: 48px 36px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.45);
    }
    .eyebrow {
        color: #d4af37;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-size: 0.78rem;
        margin-bottom: 18px;
        font-weight: 600;
    }
    h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2rem, 5vw, 3.6rem);
        font-weight: 400;
        margin: 0 0 18px;
        line-height: 1.1;
    }
    .lead {
        max-width: 560px;
        margin: 0 auto 28px;
        color: rgba(255,255,255,0.86);
        font-size: 1.05rem;
        line-height: 1.75;
        font-weight: 300;
    }
    .status {
        display: inline-block;
        margin: 0 auto 28px;
        padding: 10px 16px;
        border: 1px solid rgba(212,175,55,0.32);
        color: #d4af37;
        background: rgba(212,175,55,0.08);
        font-size: 0.95rem;
    }
    .actions {
        display: flex;
        gap: 14px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .btn {
        display: inline-block;
        padding: 16px 26px;
        text-decoration: none;
        border-radius: 2px;
        transition: all 0.25s ease;
        font-size: 0.95rem;
    }
    .btn-primary {
        background: #fff;
        color: #000;
        font-weight: 600;
    }
    .btn-primary:hover {
        background: #d4af37;
        transform: translateY(-1px);
    }
    .btn-secondary {
        border: 1px solid rgba(255,255,255,0.25);
        color: #fff;
        background: transparent;
    }
    .btn-secondary:hover {
        border-color: #d4af37;
        color: #d4af37;
    }
    .footnote {
        margin-top: 26px;
        font-size: 0.86rem;
        color: rgba(255,255,255,0.55);
    }
    @media (max-width: 640px) {
        .card {
            padding: 36px 22px;
        }
        .lead {
            font-size: 1rem;
        }
        .actions {
            flex-direction: column;
        }
        .btn {
            width: 100%;
        }
    }
</style>
</head>
<body>
<div class="background-image"></div>
<div class="contrast-overlay"></div>
<div class="page">
    <div class="card">
        <div class="eyebrow">XperiBase</div>
        <h1>Dziękujemy za wypełnienie ankiety</h1>
        <p class="lead">Twoja odpowiedź została zapisana poprawnie. Przekazane informacje pomogą nam lepiej dopasowywać rozwój Multisensorycznego Nośnika Doświadczeń XperiBase do realnych potrzeb organizacji i partnerów.</p>
        <div class="status">Formularz został wysłany pomyślnie</div>
        <div class="actions">
            <a href="index.html" class="btn btn-primary">Wróć na stronę główną</a>
            <a href="<?php echo htmlspecialchars($survey_page, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">Wypełnij kolejną ankietę</a>
        </div>
        <div class="footnote">Dziękujemy za poświęcony czas i podzielenie się opinią.</div>
    </div>
</div>
</body>
</html>
