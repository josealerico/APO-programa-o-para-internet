<?php
require __DIR__ . '/config.php';
$page = $_GET['page'] ?? 'dashboard';
$allowed = ['dashboard','produtos','enderecos','transportadores','fretes','relatorios'];
if (!in_array($page, $allowed, true)) $page = 'dashboard';
verify_csrf();

function countRows(PDO $pdo, string $table): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save_product') {
            $id = (int)($_POST['id'] ?? 0);
            $data = [trim($_POST['nome'] ?? ''), trim($_POST['descricao'] ?? ''),
                max(1,(int)($_POST['quantidade'] ?? 1)), max(0,(float)($_POST['peso'] ?? 0)),
                max(0,(float)($_POST['valor'] ?? 0))];
            if ($data[0] === '') throw new RuntimeException('Informe o nome do produto.');
            if ($id) {
                $q=$pdo->prepare('UPDATE produtos SET nome=?,descricao=?,quantidade=?,peso=?,valor=? WHERE id=?');
                $q->execute([...$data,$id]);
            } else {
                $q=$pdo->prepare('INSERT INTO produtos(nome,descricao,quantidade,peso,valor) VALUES(?,?,?,?,?)');
                $q->execute($data);
            }
            $_SESSION['flash']='Produto salvo com sucesso.'; redirect('?page=produtos');
        }
        if ($action === 'delete_product') {
            $q=$pdo->prepare('DELETE FROM produtos WHERE id=?'); $q->execute([(int)$_POST['id']]);
            $_SESSION['flash']='Produto excluído.'; redirect('?page=produtos');
        }
        if ($action === 'save_address') {
            $id=(int)($_POST['id']??0);
            $data=[trim($_POST['identificacao']??''),trim($_POST['logradouro']??''),
                trim($_POST['numero']??''),trim($_POST['complemento']??''),trim($_POST['bairro']??''),
                trim($_POST['cidade']??''),strtoupper(substr(trim($_POST['estado']??''),0,2)),
                trim($_POST['cep']??'')];
            if ($data[0]==='' || $data[1]==='' || $data[5]==='' || strlen($data[6])!==2)
                throw new RuntimeException('Preencha identificação, logradouro, cidade e UF.');
            if($id){$q=$pdo->prepare('UPDATE enderecos SET identificacao=?,logradouro=?,numero=?,complemento=?,bairro=?,cidade=?,estado=?,cep=? WHERE id=?');$q->execute([...$data,$id]);}
            else{$q=$pdo->prepare('INSERT INTO enderecos(identificacao,logradouro,numero,complemento,bairro,cidade,estado,cep) VALUES(?,?,?,?,?,?,?,?)');$q->execute($data);}
            $_SESSION['flash']='Endereço salvo com sucesso.'; redirect('?page=enderecos');
        }
        if ($action === 'delete_address') {
            $q=$pdo->prepare('DELETE FROM enderecos WHERE id=?');$q->execute([(int)$_POST['id']]);
            $_SESSION['flash']='Endereço excluído.';redirect('?page=enderecos');
        }
        if ($action === 'save_carrier') {
            $id=(int)($_POST['id']??0);
            $data=[trim($_POST['nome']??''),trim($_POST['documento']??''),trim($_POST['telefone']??''),
                trim($_POST['email']??''),trim($_POST['veiculo']??''),strtoupper(trim($_POST['placa']??''))];
            if($data[0]==='') throw new RuntimeException('Informe o nome do transportador.');
            if($id){$q=$pdo->prepare('UPDATE transportadores SET nome=?,documento=?,telefone=?,email=?,veiculo=?,placa=? WHERE id=?');$q->execute([...$data,$id]);}
            else{$q=$pdo->prepare('INSERT INTO transportadores(nome,documento,telefone,email,veiculo,placa) VALUES(?,?,?,?,?,?)');$q->execute($data);}
            $_SESSION['flash']='Transportador salvo com sucesso.';redirect('?page=transportadores');
        }
        if ($action === 'delete_carrier') {
            $q=$pdo->prepare('DELETE FROM transportadores WHERE id=?');$q->execute([(int)$_POST['id']]);
            $_SESSION['flash']='Transportador excluído.';redirect('?page=transportadores');
        }
        if ($action === 'save_freight') {
            $origin=(int)($_POST['origem_id']??0); $dest=(int)($_POST['destino_id']??0);
            $carrier=(int)($_POST['transportador_id']??0); $date=$_POST['data_frete']??'';
            $products=$_POST['produto_id']??[]; $qty=$_POST['produto_quantidade']??[];
            if(!$origin||!$dest||!$carrier||!$date||$origin===$dest||!is_array($products)||count($products)===0)
                throw new RuntimeException('Informe origem, destino, transportador, data e ao menos um produto. Origem e destino devem ser diferentes.');
            $pdo->beginTransaction();
            $q=$pdo->prepare("INSERT INTO fretes(origem_id,destino_id,transportador_id,data_frete,status,observacoes,valor_frete) VALUES(?,?,?,?,?,?,?)");
            $q->execute([$origin,$dest,$carrier,$date,$_POST['status']??'Pendente',trim($_POST['observacoes']??''),max(0,(float)($_POST['valor_frete']??0))]);
            $freightId=(int)$pdo->lastInsertId();
            $insert=$pdo->prepare('INSERT INTO frete_produtos(frete_id,produto_id,quantidade) VALUES(?,?,?)');
            foreach($products as $i=>$productId){
                $productId=(int)$productId; $quantity=max(1,(int)($qty[$i]??1));
                if($productId) $insert->execute([$freightId,$productId,$quantity]);
            }
            $pdo->commit();
            $_SESSION['flash']='Frete #'.$freightId.' gerado com sucesso.';redirect('?page=fretes');
        }
        if ($action === 'update_freight_status') {
            $q=$pdo->prepare('UPDATE fretes SET status=? WHERE id=?');
            $q->execute([$_POST['status']??'Pendente',(int)$_POST['id']]);
            $_SESSION['flash']='Status do frete atualizado.';redirect('?page=fretes');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash']='Não foi possível concluir: '.$e->getMessage();
        redirect('?page='.$page);
    }
}

include __DIR__.'/includes/header.php';
if ($page === 'dashboard') {
    $stats=['Produtos'=>countRows($pdo,'produtos'),'Endereços'=>countRows($pdo,'enderecos'),
        'Transportadores'=>countRows($pdo,'transportadores'),'Fretes'=>countRows($pdo,'fretes')];
    $recent=$pdo->query("SELECT f.id,f.data_frete,f.status,t.nome transportador FROM fretes f JOIN transportadores t ON t.id=f.transportador_id ORDER BY f.id DESC LIMIT 5")->fetchAll();
    echo '<h2 class="mb-4">Visão geral</h2><div class="row g-3 mb-4">';
    foreach($stats as $label=>$number) echo '<div class="col-6 col-xl-3"><div class="stat-card"><div class="muted">'.h($label).'</div><div class="stat-number">'.h($number).'</div></div></div>';
    echo '</div><div class="panel"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0">Fretes recentes</h5><a class="btn btn-sm btn-primary" href="?page=fretes">Ver fretes</a></div>';
    if(!$recent) echo '<p class="muted mb-0">Nenhum frete cadastrado até o momento.</p>';
    else {echo '<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nº</th><th>Data</th><th>Transportador</th><th>Status</th></tr></thead><tbody>';
    foreach($recent as $r) echo '<tr><td>#'.h($r['id']).'</td><td>'.h(date('d/m/Y',strtotime($r['data_frete']))).'</td><td>'.h($r['transportador']).'</td><td><span class="badge text-bg-light">'.h($r['status']).'</span></td></tr>';
    echo '</tbody></table></div>';}
    echo '</div>';
}
if ($page === 'produtos') {
    $edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM produtos WHERE id=?');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch();}
    $rows=$pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();
    echo '<h2 class="mb-4">Produtos</h2><div class="panel mb-4"><h5>'.($edit?'Editar produto':'Novo produto').'</h5><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_product"><input type="hidden" name="id" value="'.h($edit['id']??'').'"><div class="row g-3"><div class="col-md-6"><label class="form-label">Nome *</label><input required class="form-control" name="nome" value="'.h($edit['nome']??'').'"></div><div class="col-md-6"><label class="form-label">Descrição</label><input class="form-control" name="descricao" value="'.h($edit['descricao']??'').'"></div><div class="col-md-4"><label class="form-label">Quantidade em estoque</label><input type="number" min="1" class="form-control" name="quantidade" value="'.h($edit['quantidade']??1).'"></div><div class="col-md-4"><label class="form-label">Peso (kg)</label><input type="number" min="0" step=".01" class="form-control" name="peso" value="'.h($edit['peso']??0).'"></div><div class="col-md-4"><label class="form-label">Valor (R$)</label><input type="number" min="0" step=".01" class="form-control" name="valor" value="'.h($edit['valor']??0).'"></div></div><div class="mt-3"><button class="btn btn-primary">Salvar produto</button> <a class="btn btn-light" href="?page=produtos">Limpar</a></div></form></div>';
    echo '<div class="panel"><h5 class="mb-3">Produtos cadastrados</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Produto</th><th>Qtd.</th><th>Peso</th><th>Valor</th><th>Ações</th></tr></thead><tbody>';
    foreach($rows as $r){echo '<tr><td><strong>'.h($r['nome']).'</strong><div class="muted small">'.h($r['descricao']).'</div></td><td>'.h($r['quantidade']).'</td><td>'.h($r['peso']).' kg</td><td>R$ '.number_format((float)$r['valor'],2,',','.').'</td><td><a class="btn btn-sm btn-outline-primary" href="?page=produtos&edit='.$r['id'].'">Editar</a> <form class="d-inline" method="post" onsubmit="return confirm(\'Excluir produto?\')">'.csrf_field().'<input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr>';}
    if(!$rows) echo '<tr><td colspan="5" class="muted">Nenhum produto cadastrado.</td></tr>';
    echo '</tbody></table></div></div>';
}
if ($page === 'enderecos') {
    $edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM enderecos WHERE id=?');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch();}
    $rows=$pdo->query('SELECT * FROM enderecos ORDER BY id DESC')->fetchAll();
    echo '<h2 class="mb-4">Endereços</h2><div class="panel mb-4"><h5>'.($edit?'Editar endereço':'Novo endereço').'</h5><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_address"><input type="hidden" name="id" value="'.h($edit['id']??'').'"><div class="row g-3"><div class="col-md-4"><label class="form-label">Identificação *</label><input required class="form-control" name="identificacao" placeholder="Matriz, cliente..." value="'.h($edit['identificacao']??'').'"></div><div class="col-md-5"><label class="form-label">Logradouro *</label><input required class="form-control" name="logradouro" value="'.h($edit['logradouro']??'').'"></div><div class="col-md-3"><label class="form-label">Número</label><input class="form-control" name="numero" value="'.h($edit['numero']??'').'"></div><div class="col-md-3"><label class="form-label">Complemento</label><input class="form-control" name="complemento" value="'.h($edit['complemento']??'').'"></div><div class="col-md-3"><label class="form-label">Bairro</label><input class="form-control" name="bairro" value="'.h($edit['bairro']??'').'"></div><div class="col-md-3"><label class="form-label">Cidade *</label><input required class="form-control" name="cidade" value="'.h($edit['cidade']??'').'"></div><div class="col-md-1"><label class="form-label">UF *</label><input required maxlength="2" class="form-control text-uppercase" name="estado" value="'.h($edit['estado']??'').'"></div><div class="col-md-2"><label class="form-label">CEP</label><input class="form-control" name="cep" value="'.h($edit['cep']??'').'"></div></div><div class="mt-3"><button class="btn btn-primary">Salvar endereço</button> <a class="btn btn-light" href="?page=enderecos">Limpar</a></div></form></div>';
    echo '<div class="panel"><h5 class="mb-3">Endereços cadastrados</h5><div class="table-responsive"><table class="table"><thead><tr><th>Identificação</th><th>Endereço</th><th>Cidade/UF</th><th>Ações</th></tr></thead><tbody>';
    foreach($rows as $r){$address=$r['logradouro'].' '.$r['numero'].', '.$r['bairro'];echo '<tr><td>'.h($r['identificacao']).'</td><td>'.h($address).'</td><td>'.h($r['cidade']).'/'.h($r['estado']).'</td><td><a class="btn btn-sm btn-outline-primary" href="?page=enderecos&edit='.$r['id'].'">Editar</a> <form class="d-inline" method="post" onsubmit="return confirm(\'Excluir endereço?\')">'.csrf_field().'<input type="hidden" name="action" value="delete_address"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr>';}
    if(!$rows) echo '<tr><td colspan="4" class="muted">Nenhum endereço cadastrado.</td></tr>';
    echo '</tbody></table></div></div>';
}
if ($page === 'transportadores') {
    $edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM transportadores WHERE id=?');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch();}
    $rows=$pdo->query('SELECT * FROM transportadores ORDER BY id DESC')->fetchAll();
    echo '<h2 class="mb-4">Transportadores</h2><div class="panel mb-4"><h5>'.($edit?'Editar transportador':'Novo transportador').'</h5><form method="post">'.csrf_field().'<input type="hidden" name="action" value="save_carrier"><input type="hidden" name="id" value="'.h($edit['id']??'').'"><div class="row g-3"><div class="col-md-4"><label class="form-label">Nome / Empresa *</label><input required class="form-control" name="nome" value="'.h($edit['nome']??'').'"></div><div class="col-md-2"><label class="form-label">CPF/CNPJ</label><input class="form-control" name="documento" value="'.h($edit['documento']??'').'"></div><div class="col-md-2"><label class="form-label">Telefone</label><input class="form-control" name="telefone" value="'.h($edit['telefone']??'').'"></div><div class="col-md-4"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" value="'.h($edit['email']??'').'"></div><div class="col-md-6"><label class="form-label">Veículo</label><input class="form-control" name="veiculo" value="'.h($edit['veiculo']??'').'"></div><div class="col-md-3"><label class="form-label">Placa</label><input class="form-control text-uppercase" name="placa" value="'.h($edit['placa']??'').'"></div></div><div class="mt-3"><button class="btn btn-primary">Salvar transportador</button> <a class="btn btn-light" href="?page=transportadores">Limpar</a></div></form></div>';
    echo '<div class="panel"><h5 class="mb-3">Transportadores cadastrados</h5><div class="table-responsive"><table class="table"><thead><tr><th>Nome</th><th>Contato</th><th>Veículo / placa</th><th>Ações</th></tr></thead><tbody>';
    foreach($rows as $r){echo '<tr><td>'.h($r['nome']).'<div class="muted small">'.h($r['documento']).'</div></td><td>'.h($r['telefone']).'<div class="muted small">'.h($r['email']).'</div></td><td>'.h($r['veiculo']).' / '.h($r['placa']).'</td><td><a class="btn btn-sm btn-outline-primary" href="?page=transportadores&edit='.$r['id'].'">Editar</a> <form class="d-inline" method="post" onsubmit="return confirm(\'Excluir transportador?\')">'.csrf_field().'<input type="hidden" name="action" value="delete_carrier"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr>';}
    if(!$rows) echo '<tr><td colspan="4" class="muted">Nenhum transportador cadastrado.</td></tr>';
    echo '</tbody></table></div></div>';
}
if ($page === 'fretes') {
    $products=$pdo->query('SELECT id,nome FROM produtos ORDER BY nome')->fetchAll();
    $addresses=$pdo->query('SELECT id,identificacao,cidade,estado FROM enderecos ORDER BY identificacao')->fetchAll();
    $carriers=$pdo->query('SELECT id,nome FROM transportadores ORDER BY nome')->fetchAll();
    echo '<h2 class="mb-4">Fretes</h2><div class="panel mb-4"><h5>Novo frete</h5>';
    if(!$products||count($addresses)<2||!$carriers) echo '<div class="alert alert-warning">Para gerar fretes, cadastre produtos, pelo menos dois endereços e um transportador.</div>';
    else {
      echo '<form method="post" id="freightForm">'.csrf_field().'<input type="hidden" name="action" value="save_freight"><div class="row g-3"><div class="col-md-6"><label class="form-label">Origem *</label><select required name="origem_id" class="form-select"><option value="">Selecione</option>';
      foreach($addresses as $a)echo '<option value="'.$a['id'].'">'.h($a['identificacao'].' — '.$a['cidade'].'/'.$a['estado']).'</option>';
      echo '</select></div><div class="col-md-6"><label class="form-label">Destino *</label><select required name="destino_id" class="form-select"><option value="">Selecione</option>';
      foreach($addresses as $a)echo '<option value="'.$a['id'].'">'.h($a['identificacao'].' — '.$a['cidade'].'/'.$a['estado']).'</option>';
      echo '</select></div><div class="col-md-6"><label class="form-label">Transportador *</label><select required name="transportador_id" class="form-select"><option value="">Selecione</option>';
      foreach($carriers as $c)echo '<option value="'.$c['id'].'">'.h($c['nome']).'</option>';
      echo '</select></div><div class="col-md-3"><label class="form-label">Data do frete *</label><input required type="date" name="data_frete" class="form-control" value="'.date('Y-m-d').'"></div><div class="col-md-3"><label class="form-label">Valor do frete (R$)</label><input type="number" min="0" step=".01" name="valor_frete" class="form-control" value="0"></div><div class="col-12"><label class="form-label">Produtos transportados *</label><div id="productRows"><div class="row g-2 product-row mb-2"><div class="col-md-9"><select required name="produto_id[]" class="form-select"><option value="">Selecione o produto</option>';
      foreach($products as $p)echo '<option value="'.$p['id'].'">'.h($p['nome']).'</option>';
      echo '</select></div><div class="col-md-2"><input required type="number" min="1" name="produto_quantidade[]" class="form-control" value="1" aria-label="Quantidade"></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-product" title="Remover">×</button></div></div></div><button type="button" id="addProduct" class="btn btn-sm btn-outline-primary">+ Adicionar produto</button></div><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><option>Pendente</option><option>Em trânsito</option><option>Concluído</option><option>Cancelado</option></select></div><div class="col-md-8"><label class="form-label">Observações</label><input name="observacoes" class="form-control"></div></div><div class="mt-3"><button class="btn btn-primary">Gerar frete</button></div></form>';
    }
    echo '</div>';
    $rows=$pdo->query("SELECT f.*,o.identificacao origem,d.identificacao destino,t.nome transportador,(SELECT GROUP_CONCAT(CONCAT(p.nome,' (',fp.quantidade,')') SEPARATOR ', ') FROM frete_produtos fp JOIN produtos p ON p.id=fp.produto_id WHERE fp.frete_id=f.id) produtos FROM fretes f JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id JOIN transportadores t ON t.id=f.transportador_id ORDER BY f.data_frete DESC,f.id DESC")->fetchAll();
    echo '<div class="panel"><h5 class="mb-3">Fretes cadastrados</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nº / Data</th><th>Rota</th><th>Transportador</th><th>Produtos</th><th>Status</th></tr></thead><tbody>';
    foreach($rows as $r){echo '<tr><td>#'.h($r['id']).'<div class="muted small">'.h(date('d/m/Y',strtotime($r['data_frete']))).'</div></td><td>'.h($r['origem']).' → '.h($r['destino']).'</td><td>'.h($r['transportador']).'</td><td>'.h($r['produtos']).'</td><td><form method="post" class="d-flex gap-1">'.csrf_field().'<input type="hidden" name="action" value="update_freight_status"><input type="hidden" name="id" value="'.$r['id'].'"><select name="status" class="form-select form-select-sm">';
      foreach(['Pendente','Em trânsito','Concluído','Cancelado'] as $s)echo '<option '.($r['status']===$s?'selected':'').'>'.$s.'</option>';
      echo '</select><button class="btn btn-sm btn-outline-primary">OK</button></form></td></tr>';}
    if(!$rows)echo '<tr><td colspan="5" class="muted">Nenhum frete cadastrado.</td></tr>';
    echo '</tbody></table></div></div>';
}
if ($page === 'relatorios') {
    $date=$_GET['data']??date('Y-m-d');
    $q=$pdo->prepare("SELECT f.id,f.data_frete,f.status,f.valor_frete,o.identificacao origem,d.identificacao destino,t.nome transportador,(SELECT GROUP_CONCAT(CONCAT(p.nome,' x',fp.quantidade) SEPARATOR '; ') FROM frete_produtos fp JOIN produtos p ON p.id=fp.produto_id WHERE fp.frete_id=f.id) produtos FROM fretes f JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id JOIN transportadores t ON t.id=f.transportador_id WHERE f.data_frete=? ORDER BY f.id");
    $q->execute([$date]);$rows=$q->fetchAll();
    echo '<h2 class="mb-4">Relatórios</h2><div class="panel"><form class="row g-2 align-items-end mb-3" method="get"><input type="hidden" name="page" value="relatorios"><div class="col-auto"><label class="form-label">Data dos fretes</label><input type="date" name="data" class="form-control" value="'.h($date).'"></div><div class="col-auto"><button class="btn btn-primary">Consultar</button></div><div class="col-auto"><a class="btn btn-outline-success" href="export.php?data='.h($date).'">Baixar CSV</a></div></form><p class="muted">'.count($rows).' frete(s) encontrado(s) em '.h(date('d/m/Y',strtotime($date))).'.</p><div class="table-responsive"><table class="table"><thead><tr><th>Nº</th><th>Origem → Destino</th><th>Transportador</th><th>Produtos</th><th>Status</th><th>Valor</th></tr></thead><tbody>';
    foreach($rows as $r)echo '<tr><td>#'.h($r['id']).'</td><td>'.h($r['origem']).' → '.h($r['destino']).'</td><td>'.h($r['transportador']).'</td><td>'.h($r['produtos']).'</td><td>'.h($r['status']).'</td><td>R$ '.number_format((float)$r['valor_frete'],2,',','.').'</td></tr>';
    if(!$rows)echo '<tr><td colspan="6" class="muted">Nenhum frete para a data selecionada.</td></tr>';
    echo '</tbody></table></div></div>';
}
include __DIR__.'/includes/footer.php';
