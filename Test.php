<?php
/**
 * Test de Compatibilité — inspiré du principe du "double test" (façon Mojo Upgrade)
 * -----------------------------------------------------------------------------
 * Fichier PHP unique et autonome — SANS base de données.
 *
 * Principe : les deux partenaires répondent à la suite, sur le même appareil.
 *  1. On saisit le surnom + le sexe des deux partenaires.
 *  2. Le partenaire 1 répond seul (écran de transition avant, pour que l'autre
 *     s'éloigne de l'écran).
 *  3. L'appareil est ensuite passé au partenaire 2, qui répond à son tour
 *     (nouvel écran de transition).
 *  4. Le bilan des points communs s'affiche enfin — uniquement les réponses
 *     "Oui" ou "Oui, si l'autre le souhaite" des DEUX côtés. Les réponses
 *     individuelles ne sont jamais affichées telles quelles.
 *
 * Tout est conservé dans la session PHP le temps du parcours (aucune donnée
 * n'est écrite sur disque). Fermer l'onglet ou cliquer sur "Recommencer"
 * efface tout.
 */

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/* ============================================================
   1. BANQUE DE QUESTIONS
   ------------------------------------------------------------
   Jetons remplacés à l'affichage :
     {N}                -> surnom de l'AUTRE partenaire
     {SELF:h|f|n}       -> accord grammatical selon MON sexe
     {OTHER:h|f|n}      -> accord grammatical selon le sexe de l'AUTRE
   (h = homme, f = femme, n = neutre/autre)
   ============================================================ */

$QUESTIONS = [
    [
        'title' => 'Préliminaires & Ambiance',
        'items' => [
            "Avoir de plus longues sessions de taquineries et de préliminaires avec {N}.",
            "Donner un massage sensuel à {N}.",
            "Recevoir un massage sensuel de {N}.",
            "Écouter de la musique romantique pendant l'acte.",
            "Écouter de la musique plus agressive (rap/rock) pendant l'acte.",
            "Raser {N}, ou me faire raser par {N}.",
            "Me faire réveiller par un rapport ou du sexe oral de la part de {N} (ou réveiller {N} de cette façon).",
            "Me faire enduire — ou enduire {N} — d'huile, de peinture, de lait ou de nourriture pendant l'acte.",
        ],
    ],
    [
        'title' => 'Sexe oral, Masturbation & Pratiques corporelles',
        'items' => [
            "Pratiquer la masturbation mutuelle avec {N}.",
            "Regarder {N} se masturber, ou être {SELF:regardé|regardée|regardé·e} en train de le faire.",
            "Donner ou recevoir du sexe oral en position 69 avec {N}.",
            "M'asseoir sur le visage de {N} pendant qu'{OTHER:il|elle|iel} me donne du sexe oral (ou l'inverse).",
            "Donner ou recevoir un footjob (stimulation avec les pieds) avec {N}.",
            "Éjaculer sur la poitrine ou le cou de {N} (pearl necklace).",
            "Éjaculer sur le visage de {N}, ou recevoir une éjaculation faciale de sa part.",
            "Échanger du sperme avec {N}.",
        ],
    ],
    [
        'title' => 'Exhibitionnisme, Caméra & Pornographie',
        'items' => [
            "Prendre des photos de {N}.",
            "Prendre des photos de nous en train d'avoir des rapports.",
            "Se filmer avec {N} pendant l'acte.",
            "Utiliser des miroirs pendant les rapports avec {N}.",
            "Faire un effeuillage (strip-tease) ou un lap dance pour {N}, ou en recevoir un de sa part.",
            "Regarder de la pornographie ensemble avec {N}.",
            "Montrer à {N} une scène pornographique pour lui expliquer ce que j'aime.",
            "Partager des photos ou vidéos privées de nous sur Internet.",
        ],
    ],
    [
        'title' => 'Sexe anal & Jouets sexuels',
        'items' => [
            "Utiliser des vibromasseurs pendant l'acte avec {N}.",
            "Porter un anneau pénien (vibrant ou non) avec {N}.",
            "Utiliser des pinces à tétons avec {N}.",
            "Utiliser un plug anal pendant le sexe, sur moi ou sur {N}.",
            "Pratiquer l'analingus (rimming) avec {N} : lécher son anus, ou être léché·e par {OTHER:lui|elle|iel}.",
            "Utiliser un meuble spécialisé (balançoire sexuelle, rampe, etc.) avec {N}.",
            "Utiliser un strap-on (dildo ceinture) pour pénétrer {N}.",
            "Pratiquer une double pénétration, avec {N} et un jouet sexuel.",
        ],
    ],
    [
        'title' => 'Jeux de rôle, Mots & Dominance légère',
        'items' => [
            "Parler de manière plus crue (dirty talk) à {N}, ou demander à {OTHER:lui|elle|iel} de le faire.",
            "Être plus {SELF:vocal|vocale|vocal·e} pendant l'acte (gémissements, ordres).",
            "Traiter {N} de mots obscènes, ou être {SELF:appelé|appelée|appelé·e} ainsi par {OTHER:lui|elle|iel}.",
            "Faire un jeu de rôle (roleplay) en costumes avec {N}.",
            "Porter des bas et des talons hauts pendant le sexe.",
            "Être plus {SELF:brutal|brutale|brutal·e} pendant l'acte, ou que {N} le soit avec moi.",
        ],
    ],
    [
        'title' => 'BDSM, Soumission & Fétichisme',
        'items' => [
            "Porter, ou faire porter à {N}, un bandeau sur les yeux (blindfold).",
            "Mettre des bouchons d'oreilles pendant le sexe.",
            "Donner ou recevoir des gifles au visage avec {N}.",
            "Donner ou recevoir des fessées à la main avec {N}.",
            "Utiliser un fouet, une cravache, une canne ou une palette (paddle) avec {N}.",
            "Porter, ou faire porter à {N}, un collier pour chien avec une laisse.",
            "Porter, ou faire porter à {N}, un bâillon (ball gag).",
            "Être {SELF:suspendu|suspendue|suspendu·e} (shibari / bondage de suspension).",
            "Adorer ou vénérer une partie du corps de {N}.",
            "Jouer les rôles de maître/esclave ou de serviteur/servante avec {N}.",
            "Utiliser une cage ou une ceinture de chasteté avec {N}.",
        ],
    ],
    [
        'title' => 'Lieux publics & Inhabituels',
        'items' => [
            "Avoir un rapport sexuel dans une voiture avec {N}.",
            "Avoir un rapport dans la forêt ou un parc avec {N}.",
            "Avoir un rapport sur le lieu de travail (bureau, etc.) avec {N}.",
            "Avoir un rapport devant une fenêtre donnant sur l'extérieur avec {N}.",
            "Avoir un rapport avec {N} dans un endroit où l'on risque de se faire surprendre.",
            "Caresser {N} dans un lieu public (restaurant, cinéma).",
            "Porter un vibromasseur télécommandé en public, contrôlé par {N}.",
            "Aller dans une plage ou un centre nudiste avec {N}.",
        ],
    ],
    [
        'title' => 'Échangisme, Triolisme & Non-exclusivité',
        'items' => [
            "Aller dans un club de strip-tease avec {N}.",
            "Aller dans un club libertin / échangiste avec {N}.",
            "Regarder d'autres couples avoir des rapports en direct, avec {N}.",
            "Laisser d'autres personnes ou couples nous regarder, {N} et moi.",
            "Pratiquer le libertinage « doux » (côtoyer d'autres couples sans échange physique) avec {N}.",
            "Inclure une deuxième femme dans le rapport avec {N} (trio / ménage à trois).",
            "Inclure un deuxième homme dans le rapport avec {N}.",
            "Inclure un autre couple avec {N} (petite orgie).",
            "Participer à une grande orgie (plus de 4 personnes) avec {N}.",
            "Regarder {N} avoir un rapport avec une autre personne.",
            "Avoir un rapport avec une autre personne sous les yeux de {N}.",
            "Pratiquer une double ou triple pénétration avec {N} et d'autres partenaires.",
        ],
    ],
    [
        'title' => 'Fétichismes avancés & Thématiques spécifiques',
        'items' => [
            "Avoir un rapport avec {N} pendant les règles.",
            "Utiliser des armes factices ou jouer avec un couteau/pistolet (knife/gun play) avec {N}.",
            "Pratiquer des jeux de torture ou de stimulation intense des organes génitaux avec {N}.",
            "Pratiquer l'urophilie ou la coprophilie (golden shower / brown shower) avec {N}.",
            "Utiliser des sensations thermiques ou chimiques (baume mentholé, glace, etc.) avec {N}.",
        ],
    ],
];

/** 4 niveaux de réponse. Les valeurs 2 et 3 sont considérées "positives" pour le bilan. */
const ANSWER_LABELS = [
    0 => "Non, pas pour moi",
    1 => "Curieux·se, peut-être un jour",
    2 => "Oui, si {N} en a envie",
    3 => "Oui, avec grand plaisir !",
];

/* ============================================================
   2. AIDES : rendu de texte, sexe, échappement
   ============================================================ */

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/** h = homme, f = femme, n = autre/neutre */
function sexKey(string $sex): string
{
    $sex = strtoupper($sex);
    if ($sex === 'H') return 'h';
    if ($sex === 'F') return 'f';
    return 'n';
}

function startsWithVowelSound(string $name): bool
{
    $first = mb_strtolower(mb_substr($name, 0, 1));
    return in_array($first, ['a', 'e', 'i', 'o', 'u', 'y', 'à', 'â', 'é', 'è', 'ê', 'ë', 'î', 'ï', 'ô', 'û', 'ù'], true);
}

function renderTemplate(string $tpl, string $otherName, string $ownSex, string $otherSex): string
{
    $safeName = e($otherName);

    if (startsWithVowelSound($otherName)) {
        $tpl = preg_replace('/\bde \{N\}/u', "d'{N}", $tpl);
        $tpl = preg_replace('/\bque \{N\}/u', "qu'{N}", $tpl);
    }

    $out = str_replace('{N}', $safeName, $tpl);

    $out = preg_replace_callback('/\{SELF:([^|}]*)\|([^|}]*)\|([^}]*)\}/u', function ($m) use ($ownSex) {
        $k = sexKey($ownSex);
        $map = ['h' => $m[1], 'f' => $m[2], 'n' => $m[3]];
        return $map[$k];
    }, $out);

    $out = preg_replace_callback('/\{OTHER:([^|}]*)\|([^|}]*)\|([^}]*)\}/u', function ($m) use ($otherSex) {
        $k = sexKey($otherSex);
        $map = ['h' => $m[1], 'f' => $m[2], 'n' => $m[3]];
        return $map[$k];
    }, $out);

    return $out;
}

/* ============================================================
   3. MISE EN PAGE COMMUNE (HTML + CSS)
   ============================================================ */

function layout(string $title, string $content): void
{
    $t = e($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{$t}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --bg-1:#2a0e2c;
  --bg-2:#4a1240;
  --bg-3:#7a1f4b;
  --accent:#ff6f91;
  --accent-2:#ffb26b;
  --card:#ffffff;
  --card-soft:#fff6f2;
  --ink:#2b1a2e;
  --ink-soft:#6b5b6e;
  --line:#f0e2e6;
  --radius:22px;
  --shadow:0 20px 45px -20px rgba(74,18,64,.45);
}
*{box-sizing:border-box;}
html,body{margin:0;padding:0;}
body{
  min-height:100vh;
  font-family:'Sora',system-ui,sans-serif;
  color:var(--ink);
  background:
    radial-gradient(1200px 600px at 10% -10%, rgba(255,178,107,.35), transparent 60%),
    radial-gradient(1000px 700px at 110% 10%, rgba(255,111,145,.35), transparent 55%),
    linear-gradient(160deg, var(--bg-1), var(--bg-2) 55%, var(--bg-3));
  background-attachment:fixed;
  display:flex;
  flex-direction:column;
  align-items:center;
  padding:32px 16px 64px;
}
h1,h2,h3{font-family:'Fraunces',serif; font-weight:600; margin:0 0 .4em;}
a{color:inherit;}
.wrap{width:100%; max-width:720px;}
.brand{
  display:flex; align-items:center; gap:10px;
  color:#ffe6ee; opacity:.9; margin-bottom:26px; justify-content:center;
  font-family:'Fraunces',serif; letter-spacing:.02em;
}
.brand .dot{width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:0 0 16px 2px rgba(255,111,145,.7);}
.card{
  background:var(--card);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:34px 30px;
  margin-bottom:22px;
}
.card.soft{background:var(--card-soft);}
.lead{color:var(--ink-soft); margin:0 0 22px; line-height:1.55;}
label{display:block; font-weight:600; font-size:.92rem; margin:18px 0 6px;}
label.first{margin-top:0;}
input[type=text]{
  width:100%; padding:14px 16px; border-radius:14px; border:2px solid var(--line);
  font-family:inherit; font-size:1rem; background:#fff; transition:border-color .15s;
}
input[type=text]:focus{outline:none; border-color:var(--accent);}
.sex-row{display:flex; gap:10px; flex-wrap:wrap;}
.sex-opt{flex:1; min-width:110px;}
.sex-opt input{position:absolute; opacity:0; pointer-events:none;}
.sex-opt span{
  display:block; text-align:center; padding:12px 8px; border-radius:14px;
  border:2px solid var(--line); font-weight:600; font-size:.9rem; color:var(--ink-soft);
  cursor:pointer; transition:.15s;
}
.sex-opt input:checked + span{
  border-color:var(--accent); color:#b3184f; background:#fff0f4;
}
.partner-block{border:1px solid var(--line); border-radius:18px; padding:20px 20px 6px; margin-top:18px;}
.partner-block h3{font-size:1.05rem; color:#b3184f;}
.btn{
  appearance:none; border:none; cursor:pointer; font-family:inherit;
  font-weight:700; font-size:1rem; padding:15px 26px; border-radius:16px;
  background:linear-gradient(135deg,var(--accent),var(--accent-2)); color:#3a0b23;
  box-shadow:0 12px 24px -10px rgba(255,111,145,.7);
  transition:transform .12s ease, box-shadow .12s ease;
  width:100%; margin-top:26px;
}
.btn:hover{transform:translateY(-1px); box-shadow:0 16px 30px -10px rgba(255,111,145,.85);}
.btn:active{transform:translateY(0);}
.btn.secondary{background:#fff; color:#b3184f; border:2px solid #ffd3e0; box-shadow:none;}
.btn.ghost{background:transparent; color:#fff; border:2px solid rgba(255,255,255,.4); box-shadow:none; width:auto; padding:10px 20px; font-size:.85rem; margin-top:0;}
.progress-wrap{position:sticky; top:0; z-index:10; padding-bottom:14px;}
.progress-track{height:10px; border-radius:99px; background:rgba(255,255,255,.25); overflow:hidden;}
.progress-fill{height:100%; background:linear-gradient(90deg,var(--accent),var(--accent-2)); width:0%; transition:width .35s ease;}
.progress-label{color:#ffe6ee; font-size:.82rem; margin-top:8px; text-align:center; opacity:.9;}
.cat-badge{
  display:inline-block; background:#fff0f4; color:#b3184f; font-size:.78rem; font-weight:700;
  padding:5px 12px; border-radius:99px; margin-bottom:14px; letter-spacing:.02em;
}
.category{display:none;}
.category.active{display:block; animation:fade .35s ease;}
@keyframes fade{from{opacity:0; transform:translateY(6px);} to{opacity:1; transform:translateY(0);}}
.question{border-bottom:1px solid var(--line); padding:20px 0;}
.question:last-child{border-bottom:none;}
.qtext{font-size:1.02rem; line-height:1.5; margin-bottom:14px;}
.opts{display:grid; grid-template-columns:1fr 1fr; gap:10px;}
@media (max-width:480px){.opts{grid-template-columns:1fr;}}
.opt{position:relative;}
.opt input{position:absolute; opacity:0; pointer-events:none;}
.opt span{
  display:block; padding:12px 14px; border-radius:14px; border:2px solid var(--line);
  font-size:.9rem; color:var(--ink-soft); cursor:pointer; transition:.15s; text-align:center;
}
.opt input:checked + span{border-color:var(--accent); background:#fff0f4; color:#b3184f; font-weight:600;}
.nav-row{display:flex; gap:12px; margin-top:8px;}
.nav-row .btn{margin-top:0;}
.handoff{text-align:center; padding:16px 0 6px;}
.handoff .icon{
  width:78px; height:78px; border-radius:50%; margin:0 auto 22px;
  background:linear-gradient(135deg,var(--accent),var(--accent-2));
  display:flex; align-items:center; justify-content:center; font-size:2.1rem;
  box-shadow:0 14px 30px -10px rgba(255,111,145,.7);
}
.score-hero{text-align:center; padding:10px 0 6px;}
.score-num{font-family:'Fraunces',serif; font-size:3.4rem; color:#b3184f; line-height:1;}
.score-sub{color:var(--ink-soft); margin-top:6px;}
.match-cat{margin-top:26px;}
.match-cat h3{font-size:1rem; color:#b3184f; border-bottom:2px solid #ffd3e0; padding-bottom:8px;}
.match-item{
  display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid var(--line);
}
.match-item:last-child{border-bottom:none;}
.tag{
  flex-shrink:0; font-size:.68rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase;
  padding:5px 9px; border-radius:8px; margin-top:2px;
}
.tag.hot{background:#ffe1e9; color:#b3184f;}
.tag.warm{background:#fff2df; color:#a4650b;}
.footer-note{color:#ffe6ee; opacity:.75; font-size:.8rem; text-align:center; margin-top:26px; line-height:1.6;}
.error-box{background:#fff0ef; border:1px solid #ffc9c2; color:#b3271c; padding:14px 16px; border-radius:14px; margin-bottom:20px; font-size:.9rem;}
.reset-row{text-align:center; margin-top:6px;}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand"><span class="dot"></span> Test de Compatibilité</div>
  {$content}
</div>
</body>
</html>
HTML;
}

/* ============================================================
   4. ÉTAT (session, sans base de données)
   ============================================================ */

if (isset($_GET['reset'])) {
    $_SESSION['quiz'] = null;
    unset($_SESSION['quiz']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (!isset($_SESSION['quiz']) || !is_array($_SESSION['quiz'])) {
    $_SESSION['quiz'] = ['stage' => 'setup'];
}
$S = &$_SESSION['quiz'];

$totalQuestions = array_sum(array_map(fn($c) => count($c['items']), $QUESTIONS));

/* ============================================================
   5. TRAITEMENT DES SOUMISSIONS (POST)
   ============================================================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['form'] ?? '') === 'setup') {
        $errors = [];
        $p1name = trim((string)($_POST['p1_name'] ?? ''));
        $p1sex  = (string)($_POST['p1_sex'] ?? '');
        $p2name = trim((string)($_POST['p2_name'] ?? ''));
        $p2sex  = (string)($_POST['p2_sex'] ?? '');

        if ($p1name === '' || $p2name === '') {
            $errors[] = "Merci de renseigner un surnom pour les deux partenaires.";
        }
        if (!in_array(strtoupper($p1sex), ['H', 'F', 'N'], true) || !in_array(strtoupper($p2sex), ['H', 'F', 'N'], true)) {
            $errors[] = "Merci de sélectionner un sexe pour les deux partenaires.";
        }
        if (strlen($p1name) > 60 || strlen($p2name) > 60) {
            $errors[] = "Les surnoms doivent faire moins de 60 caractères.";
        }

        if (!$errors) {
            $S['p1_name'] = $p1name;
            $S['p1_sex']  = strtoupper($p1sex);
            $S['p2_name'] = $p2name;
            $S['p2_sex']  = strtoupper($p2sex);
            $S['stage']   = 'handoff1';
        } else {
            $S['setup_errors'] = $errors;
            $S['setup_repost'] = $_POST;
        }
    }

    if (($_POST['form'] ?? '') === 'go_quiz1' && $S['stage'] === 'handoff1') {
        $S['stage'] = 'quiz1';
    }

    if (($_POST['form'] ?? '') === 'quiz1' && $S['stage'] === 'quiz1') {
        $answers = [];
        for ($i = 0; $i < $totalQuestions; $i++) {
            $val = $_POST['q' . $i] ?? null;
            $answers[$i] = in_array($val, ['0', '1', '2', '3'], true) ? (int)$val : 0;
        }
        $S['p1_answers'] = $answers;
        $S['stage'] = 'handoff2';
    }

    if (($_POST['form'] ?? '') === 'go_quiz2' && $S['stage'] === 'handoff2') {
        $S['stage'] = 'quiz2';
    }

    if (($_POST['form'] ?? '') === 'quiz2' && $S['stage'] === 'quiz2') {
        $answers = [];
        for ($i = 0; $i < $totalQuestions; $i++) {
            $val = $_POST['q' . $i] ?? null;
            $answers[$i] = in_array($val, ['0', '1', '2', '3'], true) ? (int)$val : 0;
        }
        $S['p2_answers'] = $answers;
        $S['stage'] = 'results';
    }
}

/* ============================================================
   6. AFFICHAGE SELON L'ÉTAPE
   ============================================================ */

switch ($S['stage']) {
    case 'handoff1':
        render_handoff(
            $S['p1_name'],
            "C'est parti, " . $S['p1_name'] . " !",
            "Installe-toi confortablement. " . e($S['p2_name']) . " ne verra jamais tes réponses individuelles — seulement vos points communs, à la fin, une fois que vous aurez tous les deux répondu.",
            'go_quiz1',
            "Je commence →"
        );
        break;

    case 'quiz1':
        render_quiz_form(1, $S['p1_name'], $S['p1_sex'], $S['p2_name'], $S['p2_sex'], $totalQuestions, $QUESTIONS);
        break;

    case 'handoff2':
        render_handoff(
            $S['p2_name'],
            "Merci " . $S['p1_name'] . " !",
            "À toi maintenant, <strong>" . e($S['p2_name']) . "</strong> ! " . e($S['p1_name']) . " doit s'éloigner de l'écran avant que tu continues. Personne ne verra tes réponses individuelles.",
            'go_quiz2',
            "C'est mon tour →"
        );
        break;

    case 'quiz2':
        render_quiz_form(2, $S['p2_name'], $S['p2_sex'], $S['p1_name'], $S['p1_sex'], $totalQuestions, $QUESTIONS);
        break;

    case 'results':
        render_results($S, $QUESTIONS);
        break;

    case 'setup':
    default:
        render_setup($S['setup_errors'] ?? [], $S['setup_repost'] ?? []);
        break;
}

/* ============================================================
   7. PAGE D'ACCUEIL
   ============================================================ */

function render_setup(array $errors, array $repost): void
{
    $errHtml = '';
    if ($errors) {
        $errHtml = '<div class="error-box">' . implode('<br>', array_map('e', $errors)) . '</div>';
    }

    $sexOptions = function (string $name, string $picked = '') {
        $out = '';
        foreach (['H' => 'Homme', 'F' => 'Femme', 'N' => 'Autre'] as $val => $lab) {
            $checked = $picked === $val ? 'checked' : '';
            $out .= '<label class="sex-opt"><input type="radio" name="' . e($name) . '" value="' . $val . '" ' . $checked . ' required><span>' . e($lab) . '</span></label>';
        }
        return $out;
    };

    ob_start();
    ?>
    <div class="card">
      <h1>💗 Découvrez vos points communs</h1>
      <p class="lead">Vous répondez chacun·e à votre tour, <strong>sur ce même appareil</strong>. Entre les deux questionnaires, on vous demandera de passer l'appareil à l'autre. Aucune réponse individuelle n'est jamais montrée : seuls vos points communs apparaissent à la fin.</p>
      <?= $errHtml ?>
      <form method="post" action="">
        <input type="hidden" name="form" value="setup">
        <div class="partner-block">
          <h3>Partenaire 1</h3>
          <label class="first">Surnom</label>
          <input type="text" name="p1_name" maxlength="60" placeholder="Ex : Alex" value="<?= e($repost['p1_name'] ?? '') ?>" required>
          <label>Sexe</label>
          <div class="sex-row"><?= $sexOptions('p1_sex', $repost['p1_sex'] ?? '') ?></div>
        </div>
        <div class="partner-block">
          <h3>Partenaire 2</h3>
          <label class="first">Surnom</label>
          <input type="text" name="p2_name" maxlength="60" placeholder="Ex : Sam" value="<?= e($repost['p2_name'] ?? '') ?>" required>
          <label>Sexe</label>
          <div class="sex-row"><?= $sexOptions('p2_sex', $repost['p2_sex'] ?? '') ?></div>
        </div>
        <button class="btn" type="submit">Commencer →</button>
      </form>
    </div>
    <p class="footer-note">Partenaire 1 répondra en premier, puis l'appareil sera passé au Partenaire 2.<br>Vos réponses individuelles ne sont jamais affichées telles quelles — seuls les points de rencontre le sont, à la toute fin.</p>
    <?php
    $content = ob_get_clean();
    layout('Test de Compatibilité — Accueil', $content);
}

/* ============================================================
   8. ÉCRAN DE TRANSITION (passation de l'appareil)
   ============================================================ */

function render_handoff(string $forName, string $title, string $bodyHtml, string $formName, string $btnLabel): void
{
    ob_start();
    ?>
    <div class="card handoff">
      <div class="icon">📱</div>
      <h2><?= e($title) ?></h2>
      <p class="lead"><?= $bodyHtml ?></p>
      <form method="post" action="">
        <input type="hidden" name="form" value="<?= e($formName) ?>">
        <button class="btn" type="submit"><?= e($btnLabel) ?></button>
      </form>
    </div>
    <div class="reset-row"><a href="?reset=1" class="btn ghost" style="display:inline-block;">Tout recommencer</a></div>
    <?php
    $content = ob_get_clean();
    layout('À toi de jouer, ' . $forName, $content);
}

/* ============================================================
   9. FORMULAIRE DE QUESTIONNAIRE
   ============================================================ */

function render_quiz_form(int $whichPartner, string $myName, string $mySex, string $otherName, string $otherSex, int $totalQuestions, array $QUESTIONS): void
{
    $formName = $whichPartner === 1 ? 'quiz1' : 'quiz2';
    $qGlobalIndex = 0;

    ob_start();
    ?>
    <div class="progress-wrap">
      <div class="progress-track"><div class="progress-fill" id="progressFill"></div></div>
      <div class="progress-label" id="progressLabel">0 / <?= $totalQuestions ?> réponses</div>
    </div>

    <div class="card">
      <p class="lead" style="margin-bottom:6px;">Bonjour <strong><?= e($myName) ?></strong> 👋 Réponds sincèrement — <?= e($otherName) ?> ne verra jamais tes réponses individuelles, seulement vos points communs à la fin.</p>

      <form method="post" action="" id="quizForm">
        <input type="hidden" name="form" value="<?= e($formName) ?>">
        <?php foreach ($QUESTIONS as $catIndex => $cat): ?>
        <div class="category<?= $catIndex === 0 ? ' active' : '' ?>" data-cat="<?= $catIndex ?>">
          <span class="cat-badge"><?= e($cat['title']) ?></span>
          <?php foreach ($cat['items'] as $tpl): $qi = $qGlobalIndex; ?>
            <div class="question" data-qi="<?= $qi ?>">
              <div class="qtext"><?= renderTemplate($tpl, $otherName, $mySex, $otherSex) ?></div>
              <div class="opts">
                <?php foreach (ANSWER_LABELS as $val => $labelTpl): ?>
                  <label class="opt">
                    <input type="radio" name="q<?= $qi ?>" value="<?= $val ?>" required>
                    <span><?= renderTemplate($labelTpl, $otherName, $mySex, $otherSex) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php $qGlobalIndex++; endforeach; ?>
          <div class="nav-row">
            <?php if ($catIndex > 0): ?><button type="button" class="btn secondary" onclick="goCat(<?= $catIndex - 1 ?>)">← Précédent</button><?php endif; ?>
            <?php if ($catIndex < count($QUESTIONS) - 1): ?>
              <button type="button" class="btn" onclick="tryNext(<?= $catIndex ?>, <?= $catIndex + 1 ?>)">Suivant →</button>
            <?php else: ?>
              <button type="submit" class="btn" onclick="return confirmSubmit(<?= $catIndex ?>)">
                <?= $whichPartner === 1 ? "Valider mes réponses →" : "Voir si on est compatibles 💗" ?>
              </button>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </form>
    </div>

    <div class="reset-row"><a href="?reset=1" class="btn ghost" style="display:inline-block;">Tout recommencer</a></div>

    <script>
    const TOTAL = <?= $totalQuestions ?>;

    function updateProgress(){
      const answered = document.querySelectorAll('#quizForm input[type=radio]:checked').length;
      const pct = Math.round((answered / TOTAL) * 100);
      document.getElementById('progressFill').style.width = pct + '%';
      document.getElementById('progressLabel').textContent = answered + ' / ' + TOTAL + ' réponses';
    }

    function goCat(idx){
      document.querySelectorAll('.category').forEach(c => c.classList.remove('active'));
      document.querySelector('.category[data-cat="' + idx + '"]').classList.add('active');
      window.scrollTo({top: 0, behavior: 'smooth'});
      updateProgress();
    }

    function catIsComplete(idx){
      const cat = document.querySelector('.category[data-cat="' + idx + '"]');
      const groups = new Set();
      cat.querySelectorAll('input[type=radio]').forEach(i => groups.add(i.name));
      for (const name of groups) {
        if (!cat.querySelector('input[name="' + name + '"]:checked')) return false;
      }
      return true;
    }

    function tryNext(fromIdx, toIdx){
      if (!catIsComplete(fromIdx)) {
        alert('Merci de répondre à toutes les questions de cette catégorie avant de continuer 🙂');
        return;
      }
      goCat(toIdx);
    }

    function confirmSubmit(lastIdx){
      if (!catIsComplete(lastIdx)) {
        alert('Merci de répondre à toutes les questions avant de valider.');
        return false;
      }
      return true;
    }

    document.getElementById('quizForm').addEventListener('change', updateProgress);
    updateProgress();
    </script>
    <?php
    $content = ob_get_clean();
    layout('Ton questionnaire', $content);
}

/* ============================================================
   10. BILAN DE COMPATIBILITÉ
   ============================================================ */

function render_results(array $S, array $QUESTIONS): void
{
    $p1Name = $S['p1_name'];
    $p2Name = $S['p2_name'];
    $p1Sex  = $S['p1_sex'];
    $p2Sex  = $S['p2_sex'];
    $p1Answers = $S['p1_answers'] ?? [];
    $p2Answers = $S['p2_answers'] ?? [];

    $POSITIVE = [2, 3];
    $totalMatches = 0;
    $totalQuestions = 0;
    $catBlocks = [];

    $qi = 0;
    foreach ($QUESTIONS as $cat) {
        $lines = [];
        foreach ($cat['items'] as $tpl) {
            $totalQuestions++;
            $a = $p1Answers[$qi] ?? 0;
            $b = $p2Answers[$qi] ?? 0;
            if (in_array($a, $POSITIVE, true) && in_array($b, $POSITIVE, true)) {
                $totalMatches++;
                $bothEnthusiastic = ($a === 3 && $b === 3);
                // On affiche la phrase du point de vue neutre : {N} = partenaire 2 pour la lisibilité,
                // avec les accords calés sur le partenaire 1 (les deux se lisent bien dans les deux sens).
                $text = renderTemplate($tpl, $p2Name, $p1Sex, $p2Sex);
                $lines[] = ['text' => $text, 'hot' => $bothEnthusiastic];
            }
            $qi++;
        }
        if ($lines) {
            $catBlocks[] = ['title' => $cat['title'], 'lines' => $lines];
        }
    }

    $pct = $totalQuestions > 0 ? round(($totalMatches / $totalQuestions) * 100) : 0;

    ob_start();
    ?>
    <div class="card">
      <div class="score-hero">
        <div class="score-num"><?= $totalMatches ?></div>
        <div class="score-sub">points communs trouvés entre <?= e($p1Name) ?> et <?= e($p2Name) ?><br>(sur <?= $totalQuestions ?> envies explorées — soit <?= $pct ?>%)</div>
      </div>
    </div>

    <?php if (!$catBlocks): ?>
      <div class="card">
        <p class="lead" style="margin:0;">Aucun point commun n'est ressorti cette fois-ci parmi les envies proposées — et ce n'est pas grave du tout ! C'est aussi l'occasion d'en discuter ensemble, à votre rythme. 💛</p>
      </div>
    <?php else: ?>
      <div class="card">
        <p class="lead" style="margin-bottom:0;">Voici ce que vous avez tous les deux envie d'explorer. Vos réponses individuelles restent privées : seuls ces points de rencontre sont révélés.</p>
        <?php foreach ($catBlocks as $block): ?>
          <div class="match-cat">
            <h3><?= e($block['title']) ?></h3>
            <?php foreach ($block['lines'] as $line): ?>
              <div class="match-item">
                <span class="tag <?= $line['hot'] ? 'hot' : 'warm' ?>"><?= $line['hot'] ? 'Envie partagée' : 'Terrain d\'entente' ?></span>
                <span><?= $line['text'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="footer-note">« Envie partagée » : vous avez tous les deux répondu avec enthousiasme.<br>« Terrain d'entente » : l'un·e de vous est partant·e si l'autre en a envie — parlez-en !</p>
    <div class="reset-row"><a href="?reset=1" class="btn ghost" style="display:inline-block;">Recommencer un test</a></div>
    <?php
    $content = ob_get_clean();
    layout('Votre bilan de compatibilité', $content);
}
