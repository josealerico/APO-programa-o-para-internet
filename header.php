<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>LogiFlow | Gerenciador Logístico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
<a class="brand" href="index.php"><span class="brand-mark">L</span><span>LogiFlow<small>GESTÃO LOGÍSTICA</small></span></a>
<div class="menu-label">MENU PRINCIPAL</div>
<a class="nav-link-custom <?= $page==='dashboard'?'active':'' ?>" href="?page=dashboard">▦ <span>Painel</span></a>
<a class="nav-link-custom <?= $page==='produtos'?'active':'' ?>" href="?page=produtos">□ <span>Produtos</span></a>
<a class="nav-link-custom <?= $page==='enderecos'?'active':'' ?>" href="?page=enderecos">⌖ <span>Endereços</span></a>
<a class="nav-link-custom <?= $page==='transportadores'?'active':'' ?>" href="?page=transportadores">♧ <span>Transportadores</span></a>
<a class="nav-link-custom <?= $page==='fretes'?'active':'' ?>" href="?page=fretes">⇄ <span>Fretes</span></a>
<a class="nav-link-custom <?= $page==='relatorios'?'active':'' ?>" href="?page=relatorios">▤ <span>Relatórios</span></a>
<div class="sidebar-bottom">Projeto acadêmico<br><strong>UNIPAR · FPI</strong></div>
</aside>
<main class="main-area">
<header class="topbar"><div><span class="muted small">SISTEMA DE CONTROLE</span><div class="fw-semibold">Gerenciador Logístico</div></div><div class="user-chip"><span class="avatar">JP</span><span>Administrador</span></div></header>
<div class="content-area">
<?php if($flash): ?><div class="alert alert-info alert-dismissible fade show" role="alert"><?= h($flash) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
