<?php
session_start();
require_once 'core/db.php';

$user_id   = $_SESSION['user_id']   ?? 1;
$user_name = $_SESSION['user_name'] ?? 'San Toi';

// Fetch accounts
$stmt = $conn->prepare("SELECT * FROM account WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_accounts = count($accounts);
$active_count   = count(array_filter($accounts, fn($a) => $a['status'] === 'active'));
$frozen_count   = count(array_filter($accounts, fn($a) => $a['status'] === 'frozen'));

$primary = null;
foreach ($accounts as $a) { if ($a['status'] === 'active') { $primary = $a; break; } }
if (!$primary && !empty($accounts)) $primary = $accounts[0];

$totals = [];
foreach ($accounts as $a) {
    $totals[$a['currency']] = ($totals[$a['currency']] ?? 0) + $a['balance'];
}

$currency_sym = ['LAK'=>'₭','USD'=>'$','THB'=>'฿','EUR'=>'€','CNY'=>'¥'];
$sym    = $currency_sym[$primary['currency'] ?? 'LAK'] ?? '₭';
$bal    = $sym . ' ' . number_format($primary['balance'] ?? 0, 0, '.', ',');
$acc    = $primary['account_number'] ?? '—';
$masked = $primary ? ('**** **** **** ' . substr(str_replace(['-',' '],'', $acc), -4)) : '—';

// Mock transactions
$txns = [
    ['icon'=>'shopping_cart', 'title'=>'Grocery Store',    'sub'=>'Card Payment',    'date'=>'TODAY · 10:24',     'status'=>'completed','amount'=>'-₭ 450,000','pos'=>false],
    ['icon'=>'arrow_downward','title'=>'Salary Deposit',   'sub'=>'Tech Corp Inc.',  'date'=>'YESTERDAY · 09:00', 'status'=>'completed','amount'=>'+₭ 5,000,000','pos'=>true],
    ['icon'=>'sync_alt',      'title'=>'Internal Transfer','sub'=>'To Savings',      'date'=>'OCT 12 · 14:15',   'status'=>'completed','amount'=>'-₭ 1,000,000','pos'=>false],
    ['icon'=>'bolt',          'title'=>'Electric Bill',    'sub'=>'Utility Payment', 'date'=>'OCT 10 · 08:30',   'status'=>'pending',  'amount'=>'-₭ 250,000','pos'=>false],
    ['icon'=>'restaurant',    'title'=>'Night Market',     'sub'=>'POS Terminal',    'date'=>'OCT 09 · 19:45',   'status'=>'completed','amount'=>'-₭ 85,000','pos'=>false],
    ['icon'=>'arrow_downward','title'=>'Freelance Pay',    'sub'=>'Wire Transfer',   'date'=>'OCT 08 · 11:00',   'status'=>'completed','amount'=>'+₭ 800,000','pos'=>true],
];
?>
<!DOCTYPE html>
<html lang="lo">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>BANKIFY_SYS // DASHBOARD</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;500;600;700&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
:root{--c:#00fff5;--p:#ff006e;--y:#f5ff00;--g:#00ff88;--o:#ff8800;--bg0:#020408;--bg1:#080f1a;--bd:#0d2235;--dim:#3a5a6a;--mid:#7ab8cc;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Rajdhani',sans-serif;background:var(--bg0);color:var(--c);min-height:100vh;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,255,245,.01) 2px,rgba(0,255,245,.01) 4px);pointer-events:none;z-index:9999;}
body::after{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(0,255,245,.022) 1px,transparent 1px),linear-gradient(90deg,rgba(0,255,245,.022) 1px,transparent 1px);background-size:44px 44px;pointer-events:none;z-index:0;}
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;}
.mono{font-family:'Share Tech Mono',monospace;}
.orb{font-family:'Orbitron',monospace;}
.gc{color:var(--c);text-shadow:0 0 8px var(--c),0 0 20px rgba(0,255,245,.35);}
.gp{color:var(--p);text-shadow:0 0 8px var(--p);}
.gy{color:var(--y);text-shadow:0 0 8px var(--y);}
.gg{color:var(--g);text-shadow:0 0 8px var(--g);}
.go{color:var(--o);text-shadow:0 0 8px var(--o);}
.pnl{background:var(--bg1);border:1px solid var(--bd);}
aside{background:var(--bg1);border-right:1px solid var(--bd);z-index:10;}
.nav-a{display:flex;align-items:center;gap:10px;padding:9px 12px;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--dim);transition:all .15s;border:1px solid transparent;text-decoration:none;border-radius:1px;}
.nav-a:hover{color:var(--c);background:rgba(0,255,245,.05);border-color:rgba(0,255,245,.18);}
.nav-a.act{color:var(--c);background:rgba(0,255,245,.08);border-color:rgba(0,255,245,.28);box-shadow:inset 3px 0 0 var(--c);}
.btn{background:transparent;border:1px solid var(--c);color:var(--c);font-family:'Orbitron',monospace;font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:9px 16px;cursor:pointer;transition:all .2s;position:relative;overflow:hidden;clip-path:polygon(6px 0%,100% 0%,calc(100% - 6px) 100%,0% 100%);display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn::before{content:'';position:absolute;inset:0;background:var(--c);transform:translateX(-100%);transition:.2s;z-index:0;}
.btn:hover::before{transform:translateX(0);}
.btn:hover{color:var(--bg0);box-shadow:0 0 25px rgba(0,255,245,.5);}
.btn>*{position:relative;z-index:1;}
.qbtn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:14px 8px;background:rgba(0,255,245,.03);border:1px solid rgba(0,255,245,.1);cursor:pointer;transition:all .2s;text-decoration:none;border-radius:1px;}
.qbtn:hover{background:rgba(0,255,245,.08);border-color:rgba(0,255,245,.35);box-shadow:0 0 20px rgba(0,255,245,.08);}
.holo{background:linear-gradient(135deg,rgba(0,255,245,.08),rgba(255,0,110,.05) 40%,rgba(0,255,136,.07));border:1px solid rgba(0,255,245,.25);position:relative;overflow:hidden;}
.holo::before{content:'';position:absolute;top:-60%;left:-60%;width:220%;height:220%;background:conic-gradient(from 0deg,transparent,rgba(0,255,245,.04) 60deg,transparent 120deg,rgba(255,0,110,.03) 180deg,transparent 240deg,rgba(0,255,136,.04) 300deg,transparent);animation:holoSpin 10s linear infinite;}
@keyframes holoSpin{to{transform:rotate(360deg);}}
.card-scan::after{content:'';position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,rgba(0,255,245,.6),transparent);animation:scanD 3s linear infinite;pointer-events:none;}
@keyframes scanD{0%{top:-5%}100%{top:110%}}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes blink{0%,100%{opacity:1}50%{opacity:0}}
@keyframes glitch{0%,94%,100%{transform:translateX(0) skewX(0)}95%{transform:translateX(-3px) skewX(-1deg)}97%{transform:translateX(3px)}99%{transform:translateX(-1px)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.fu {animation:fadeUp .45s ease both;}
.fu1{animation:fadeUp .45s .07s ease both;}
.fu2{animation:fadeUp .45s .14s ease both;}
.fu3{animation:fadeUp .45s .21s ease both;}
.fu4{animation:fadeUp .45s .28s ease both;}
.fu5{animation:fadeUp .45s .35s ease both;}
.blink{animation:blink 1s step-end infinite;}
.glitch{animation:glitch 4s infinite;}
.pulse{animation:pulse 2s ease infinite;}
.txn-row{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid rgba(13,34,53,.6);transition:background .12s;}
.txn-row:hover{background:rgba(0,255,245,.025);}
.txn-row:last-child{border-bottom:none;}
.bdg-ok{font-family:'Share Tech Mono',monospace;font-size:8px;letter-spacing:.1em;padding:2px 8px;border:1px solid rgba(0,255,136,.4);color:var(--g);background:rgba(0,255,136,.06);}
.bdg-pend{font-family:'Share Tech Mono',monospace;font-size:8px;letter-spacing:.1em;padding:2px 8px;border:1px solid rgba(245,255,0,.4);color:var(--y);background:rgba(245,255,0,.06);}
::-webkit-scrollbar{width:3px;}
::-webkit-scrollbar-track{background:var(--bg0);}
::-webkit-scrollbar-thumb{background:rgba(0,255,245,.25);}
</style>
</head>
<body>
<div class="flex h-screen overflow-hidden" style="position:relative;z-index:1">

<!-- SIDEBAR -->
<aside class="hidden md:flex w-56 flex-shrink-0 flex-col">
    <div style="padding:16px 18px;border-bottom:1px solid var(--bd)">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:32px;height:30px;border:1px solid rgba(0,255,245,.4);display:flex;align-items:center;justify-content:center;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%)">
                <span class="material-symbols-outlined gc" style="font-size:15px">account_balance</span>
            </div>
            <div>
                <p class="orb gc glitch" style="font-size:14px;font-weight:900;letter-spacing:.12em">BANKIFY</p>
                <p class="mono" style="font-size:7px;color:var(--dim);letter-spacing:.12em">NEURAL_FINANCE_v2.077</p>
            </div>
        </div>
    </div>
    <div style="padding:12px 16px;border-bottom:1px solid var(--bd)">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:32px;border:1px solid rgba(0,255,245,.3);display:flex;align-items:center;justify-content:center;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%);font-family:'Orbitron',monospace;font-size:13px;font-weight:700" class="gc">
                <?= strtoupper(substr($user_name,0,1)) ?>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:white"><?= htmlspecialchars($user_name) ?></p>
                <div style="display:flex;align-items:center;gap:5px;margin-top:1px">
                    <span class="gg pulse" style="font-size:8px">●</span>
                    <span class="mono" style="font-size:8px;color:var(--dim)">UID_#<?= str_pad($user_id,4,'0',STR_PAD_LEFT) ?></span>
                </div>
            </div>
        </div>
    </div>
    <nav style="padding:10px;flex:1;display:flex;flex-direction:column;gap:2px">
        <p class="mono" style="font-size:7px;color:var(--dim);letter-spacing:.16em;padding:6px 12px">// NAVIGATION_NODES</p>
        <?php foreach([
            ['dashboard',             'DASHBOARD',   'index.php',          true],
            ['account_balance_wallet','ACCOUNTS',    'account/index.php',  false],
            ['add_circle',            'NEW_ACCOUNT', 'account/create.php', false],
            ['swap_horiz',            'TRANSACTIONS','#',                  false],
            ['manage_accounts',       'USERS_MGMT',  'account/users.php',  false],
            ['settings',              'SETTINGS',    '#',                  false],
        ] as [$ico,$lbl,$href,$act]): ?>
        <a href="<?=$href?>" class="nav-a <?=$act?'act':''?>">
            <span class="material-symbols-outlined" style="font-size:15px"><?=$ico?></span><?=$lbl?>
            <?php if($act):?><span style="margin-left:auto" class="mono blink gc" style="font-size:9px">█</span><?php endif;?>
        </a>
        <?php endforeach;?>
    </nav>
    <div style="padding:10px;border-top:1px solid var(--bd)">
        <a href="login.php" class="nav-a" style="color:rgba(255,0,110,.5)">
            <span class="material-symbols-outlined" style="font-size:15px">logout</span>DISCONNECT
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="flex-1 flex flex-col overflow-hidden">

    <!-- TOPBAR -->
    <header style="background:linear-gradient(90deg,rgba(0,255,245,.1),rgba(0,255,245,.03) 60%,transparent);border-bottom:1px solid rgba(0,255,245,.14);border-left:3px solid var(--c);padding:10px 28px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <div>
            <div style="display:flex;align-items:center;gap:10px">
                <p class="mono" style="font-size:8px;color:var(--dim);letter-spacing:.12em">BANKIFY_SYS // MODULE:</p>
                <h1 class="orb gc" style="font-size:15px;font-weight:900;letter-spacing:.14em">DASHBOARD</h1>
                <span class="blink gc mono" style="font-size:13px">_</span>
            </div>
            <p class="mono" style="font-size:8px;color:var(--dim);margin-top:2px;letter-spacing:.08em">
                WELCOME_BACK: <?= htmlspecialchars($user_name) ?> &nbsp;|&nbsp;
                <span id="live-time"><?= date('Y-m-d · H:i:s') ?></span> &nbsp;|&nbsp;
                <span class="gg">SYS_ONLINE</span>
            </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:30px;border:1px solid rgba(0,255,245,.2);display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%)">
                <span class="material-symbols-outlined" style="font-size:16px;color:var(--mid)">notifications</span>
                <span style="position:absolute;top:5px;right:5px;width:5px;height:5px;background:var(--p);border-radius:50%;box-shadow:0 0 6px var(--p)" class="pulse"></span>
            </div>
            <a href="account/create.php" class="btn">
                <span class="material-symbols-outlined" style="font-size:14px">add</span>
                <span>NEW_ACCOUNT</span>
            </a>
        </div>
    </header>

    <!-- BODY -->
    <main class="flex-1 overflow-y-auto" style="padding:20px 26px">
    <div style="max-width:1200px;margin:0 auto;display:flex;flex-direction:column;gap:16px">

        <!-- STAT CARDS -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
            <?php
            $stats=[
                ['TOTAL_BALANCE',   $bal,           'var(--c)', 'account_balance'],
                ['ACTIVE_ACCOUNTS', $active_count,  'var(--g)', 'check_circle'],
                ['TOTAL_ACCOUNTS',  $total_accounts,'var(--y)', 'account_balance_wallet'],
                ['FROZEN_NODES',    $frozen_count,  'var(--p)', 'lock'],
            ];
            foreach($stats as $i=>[$lbl,$val,$col,$ico]):
            ?>
            <div class="pnl fu<?=$i?>" style="border-radius:2px;padding:16px 18px;overflow:hidden;position:relative;box-shadow:0 0 0 1px <?=$col?>22,0 0 20px <?=$col?>08">
                <div style="position:absolute;top:0;left:0;width:3px;height:100%;background:<?=$col?>;box-shadow:0 0 10px <?=$col?>"></div>
                <div style="position:absolute;right:-6px;bottom:-6px;opacity:.04">
                    <span class="material-symbols-outlined" style="font-size:60px"><?=$ico?></span>
                </div>
                <div style="padding-left:6px">
                    <p class="mono" style="font-size:8px;color:var(--dim);letter-spacing:.16em;margin-bottom:8px">// <?=$lbl?></p>
                    <?php $fs = strlen((string)$val) > 7 ? '17px' : '22px'; ?>
                    <p class="orb" style="font-size:<?=$fs?>;font-weight:900;color:<?=$col?>;text-shadow:0 0 12px <?=$col?>"><?=$val?></p>
                    <p class="mono" style="font-size:8px;color:var(--dim);margin-top:6px"><span style="color:<?=$col?>">▲</span> LIVE_DATA</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CARD + QUICK ACTIONS -->
        <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:14px">

            <!-- Holo Card -->
            <div class="holo card-scan fu1" style="border-radius:2px;padding:22px;min-height:180px;display:flex;flex-direction:column;justify-content:space-between">
                <div style="position:relative;z-index:1;display:flex;justify-content:space-between;align-items:flex-start">
                    <div>
                        <p class="mono" style="font-size:8px;color:rgba(0,255,245,.45);letter-spacing:.14em">BANKIFY_NET // PRIMARY_NODE</p>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--g);box-shadow:0 0 6px var(--g)" class="pulse"></span>
                            <span class="mono gg" style="font-size:9px;letter-spacing:.1em">AUTHORIZED</span>
                        </div>
                    </div>
                    <div style="border:1px solid rgba(245,255,0,.4);padding:3px 7px">
                        <span class="mono gy" style="font-size:8px">IC</span>
                    </div>
                </div>
                <div style="position:relative;z-index:1">
                    <p class="orb gc" style="font-size:26px;font-weight:900;letter-spacing:.04em"><?= $bal ?></p>
                    <p class="mono" style="font-size:9px;color:rgba(0,255,245,.3);letter-spacing:.22em;margin-top:9px"><?= $masked ?></p>
                    <p class="mono gc" style="font-size:9px;margin-top:3px"><?= htmlspecialchars($acc) ?></p>
                </div>
                <div style="position:absolute;top:8px;left:8px;width:12px;height:12px;border-top:2px solid rgba(0,255,245,.5);border-left:2px solid rgba(0,255,245,.5)"></div>
                <div style="position:absolute;top:8px;right:8px;width:12px;height:12px;border-top:2px solid rgba(255,0,110,.5);border-right:2px solid rgba(255,0,110,.5)"></div>
                <div style="position:absolute;bottom:8px;left:8px;width:12px;height:12px;border-bottom:2px solid rgba(255,0,110,.5);border-left:2px solid rgba(255,0,110,.5)"></div>
                <div style="position:absolute;bottom:8px;right:8px;width:12px;height:12px;border-bottom:2px solid rgba(0,255,245,.5);border-right:2px solid rgba(0,255,245,.5)"></div>
            </div>

            <!-- Quick Actions + Balances -->
            <div class="pnl fu2" style="border-radius:2px;padding:18px;box-shadow:0 0 0 1px rgba(0,255,245,.1)">
                <p class="orb gc" style="font-size:10px;font-weight:700;letter-spacing:.14em;margin-bottom:14px">QUICK_ACCESS_NODES</p>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px">
                    <?php foreach([
                        ['send',        'TRANSFER',  'var(--c)', 'account/create.php'],
                        ['add_circle',  'DEPOSIT',   'var(--g)', 'account/create.php'],
                        ['payments',    'WITHDRAW',  'var(--y)', '#'],
                        ['history',     'HISTORY',   'var(--mid)','#'],
                        ['receipt_long','STATEMENT', 'var(--p)', '#'],
                        ['qr_code',     'QR_PAY',    'var(--o)', '#'],
                    ] as [$ico,$lbl,$col,$href]): ?>
                    <a href="<?=$href?>" class="qbtn">
                        <div style="width:36px;height:34px;border:1px solid <?=$col?>;display:flex;align-items:center;justify-content:center;clip-path:polygon(4px 0%,100% 0%,calc(100% - 4px) 100%,0% 100%);background:rgba(0,0,0,.3)">
                            <span class="material-symbols-outlined" style="font-size:17px;color:<?=$col?>"><?=$ico?></span>
                        </div>
                        <p class="mono" style="font-size:8px;letter-spacing:.1em;color:var(--dim)"><?=$lbl?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if(!empty($totals)): ?>
                <div style="padding-top:12px;border-top:1px solid var(--bd)">
                    <p class="mono" style="font-size:8px;color:var(--dim);letter-spacing:.12em;margin-bottom:8px">// BALANCE_BY_CURRENCY</p>
                    <div style="display:flex;flex-direction:column;gap:7px">
                        <?php foreach($totals as $cur=>$tot):
                            $s=$currency_sym[$cur]??$cur;
                        ?>
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <span class="mono" style="font-size:9px;color:var(--dim)"><?=$cur?></span>
                            <span class="mono gc" style="font-size:11px;font-weight:700"><?=$s.' '.number_format($tot,0,'.',',')?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TRANSACTIONS + ACCOUNTS + CHART -->
        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:14px">

            <!-- Transactions -->
            <div class="pnl fu3" style="border-radius:2px;overflow:hidden;box-shadow:0 0 0 1px rgba(0,255,245,.08)">
                <div style="padding:12px 18px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span class="material-symbols-outlined gc" style="font-size:15px">receipt_long</span>
                        <p class="orb gc" style="font-size:10px;font-weight:700;letter-spacing:.12em">RECENT_ACTIVITY</p>
                    </div>
                    <a href="#" class="mono" style="font-size:9px;color:var(--dim);text-decoration:none;display:flex;align-items:center;gap:3px">
                        VIEW_ALL <span class="material-symbols-outlined" style="font-size:11px">arrow_forward</span>
                    </a>
                </div>
                <?php foreach($txns as $t): ?>
                <div class="txn-row">
                    <div style="width:32px;height:32px;border:1px solid <?=$t['pos']?'rgba(0,255,136,.3)':'rgba(0,255,245,.15)'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;clip-path:polygon(3px 0%,100% 0%,calc(100% - 3px) 100%,0% 100%)">
                        <span class="material-symbols-outlined" style="font-size:14px;color:<?=$t['pos']?'var(--g)':'var(--mid)'?>"><?=$t['icon']?></span>
                    </div>
                    <div style="flex:1;min-width:0">
                        <p style="font-size:13px;font-weight:700;color:white;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($t['title'])?></p>
                        <p class="mono" style="font-size:9px;color:var(--dim)"><?=htmlspecialchars($t['sub'])?> · <?=$t['date']?></p>
                    </div>
                    <span class="<?=$t['status']==='completed'?'bdg-ok':'bdg-pend'?>"><?=strtoupper($t['status'])?></span>
                    <p class="mono" style="font-size:12px;font-weight:700;color:<?=$t['pos']?'var(--g)':'white'?>;white-space:nowrap;min-width:90px;text-align:right"><?=$t['amount']?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right column -->
            <div style="display:flex;flex-direction:column;gap:12px">

                <!-- Chart -->
                <div class="pnl fu4" style="border-radius:2px;padding:16px;box-shadow:0 0 0 1px rgba(0,255,245,.08)">
                    <p class="orb gc" style="font-size:10px;font-weight:700;letter-spacing:.12em;margin-bottom:3px">ACTIVITY_GRAPH</p>
                    <p class="mono" style="font-size:8px;color:var(--dim);margin-bottom:12px">// LAST 7 DAYS TXN VOLUME</p>
                    <div style="display:flex;align-items:flex-end;gap:4px;height:55px">
                        <?php foreach([[35,'M'],[60,'T'],[28,'W'],[80,'T'],[45,'F'],[95,'S'],[55,'S']] as [$v,$d]): ?>
                        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
                            <div style="width:100%;height:<?=$v?>%;background:linear-gradient(180deg,var(--c),rgba(0,255,245,.25));border-radius:1px 1px 0 0;box-shadow:0 0 6px rgba(0,255,245,.25);min-height:3px"></div>
                            <span class="mono" style="font-size:7px;color:var(--dim)"><?=$d?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Accounts list -->
                <div class="pnl fu5" style="border-radius:2px;overflow:hidden;flex:1;box-shadow:0 0 0 1px rgba(0,255,245,.08)">
                    <div style="padding:10px 14px;border-bottom:1px solid var(--bd);display:flex;align-items:center;justify-content:space-between">
                        <p class="orb gc" style="font-size:10px;font-weight:700;letter-spacing:.12em">LINKED_ACCOUNTS</p>
                        <a href="account/index.php" class="mono" style="font-size:8px;color:var(--dim);text-decoration:none">VIEW_ALL</a>
                    </div>
                    <?php if(empty($accounts)): ?>
                    <div style="padding:24px;text-align:center">
                        <p class="mono" style="font-size:9px;color:var(--dim)">// NO_RECORDS_IN_DB</p>
                        <a href="account/create.php" class="btn" style="margin-top:12px;display:inline-flex;font-size:9px;padding:7px 14px"><span>CREATE_FIRST</span></a>
                    </div>
                    <?php else: ?>
                    <?php foreach(array_slice($accounts,0,5) as $ac):
                        $s=$currency_sym[$ac['currency']]??$ac['currency'];
                        if($ac['status']==='active') $stcol='var(--g)';
                        elseif($ac['status']==='frozen') $stcol='#00c8ff';
                        else $stcol='var(--p)';
                    ?>
                    <div style="padding:10px 14px;border-bottom:1px solid rgba(13,34,53,.5);display:flex;align-items:center;justify-content:space-between;transition:background .12s" onmouseover="this.style.background='rgba(0,255,245,.025)'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="width:6px;height:6px;border-radius:50%;background:<?=$stcol?>;box-shadow:0 0 5px <?=$stcol?>"></div>
                            <div>
                                <p class="mono gc" style="font-size:10px;font-weight:700"><?=htmlspecialchars($ac['account_number'])?></p>
                                <p class="mono" style="font-size:8px;color:var(--dim)"><?=strtoupper($ac['status'])?></p>
                            </div>
                        </div>
                        <p class="orb" style="font-size:11px;font-weight:700;color:<?=$stcol?>"><?=$s.' '.number_format($ac['balance'],0,'.',',')?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="mono fu5" style="border-top:1px solid var(--bd);padding-top:10px;display:flex;align-items:center;justify-content:space-between;font-size:8px;color:var(--dim)">
            <span>// BANKIFY_SYS v2.077 &nbsp;|&nbsp; PHP <?= phpversion() ?></span>
            <span>SESSION: <?= substr(session_id(),0,12) ?>... &nbsp;|&nbsp; <span class="gg">ALL_SYSTEMS_NOMINAL</span></span>
        </div>

    </div>
    </main>
</div>
</div>

<script>
// Live clock
setInterval(()=>{
    const now=new Date();
    const p=n=>String(n).padStart(2,'0');
    const el=document.getElementById('live-time');
    if(el) el.textContent=`${now.getFullYear()}-${p(now.getMonth()+1)}-${p(now.getDate())} · ${p(now.getHours())}:${p(now.getMinutes())}:${p(now.getSeconds())}`;
},1000);
</script>
</body>
</html>