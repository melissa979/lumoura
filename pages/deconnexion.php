<?php
session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Lumoura Joaillerie</title>
    <meta http-equiv="refresh" content="2;url=../index.php">
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600&family=Didact+Gothic&display=swap" rel="stylesheet">
    <style>
        :root{--g1:#D4A843;--g2:#F5D78E;--ink:#0D0A06;--smoke:#F8F5EF;--stone:#E8E0D0;}
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            font-family:'Didact Gothic',sans-serif;
            background:var(--ink);
            height:100vh;
            display:flex;align-items:center;justify-content:center;
            text-align:center;
            position:relative;overflow:hidden;
        }
        body::before{
            content:'';position:absolute;inset:0;
            background:radial-gradient(ellipse at center, rgba(212,168,67,.12) 0%, transparent 70%);
        }
        .logout-box{
            position:relative;z-index:1;
            max-width:460px;width:90%;
            background:rgba(255,255,255,.04);
            border:1px solid rgba(212,168,67,.2);
            padding:60px 50px;
        }
        .gem{
            font-size:3rem;margin-bottom:24px;
            animation:pulse 2s ease-in-out infinite;
            display:block;
        }
        @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.6;}}
        h1{
            font-family:'EB Garamond',serif;
            font-size:2rem;color:#fff;font-weight:400;
            margin-bottom:14px;letter-spacing:1px;
        }
        p{color:rgba(255,255,255,.45);font-size:.88rem;line-height:1.7;margin-bottom:8px;}
        .redirect-info{
            font-family:'Cinzel',serif;font-size:.55rem;
            letter-spacing:2.5px;text-transform:uppercase;
            color:var(--g1);margin:20px 0;
        }
        .progress{
            width:100%;height:2px;background:rgba(255,255,255,.08);
            margin:16px 0 28px;overflow:hidden;
        }
        .progress-fill{
            height:100%;background:var(--g1);width:0%;
            animation:fill 2s linear forwards;
        }
        @keyframes fill{from{width:0%;}to{width:100%;}}
        .btn-home{
            display:inline-block;
            background:var(--g1);color:var(--ink);
            padding:13px 32px;
            font-family:'Cinzel',serif;font-size:.62rem;
            letter-spacing:2.5px;text-transform:uppercase;
            text-decoration:none;transition:background .3s;
        }
        .btn-home:hover{background:var(--g2);}
    </style>
</head>
<body>
    <div class="logout-box">
        <span class="gem">💎</span>
        <h1>À bientôt !</h1>
        <p>Vous avez été déconnecté de votre espace client.<br>Merci pour votre visite chez Lumoura.</p>
        <div class="redirect-info">Redirection automatique dans 2 secondes...</div>
        <div class="progress"><div class="progress-fill"></div></div>
        <a href="../index.php" class="btn-home">Retour à l'accueil</a>
    </div>
    <script>
        setTimeout(() => { window.location.href = "../index.php"; }, 2000);
    </script>
</body>
</html>