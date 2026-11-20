<?php
require __DIR__.'/config.php';
$date=$_GET['data']??date('Y-m-d');
$q=$pdo->prepare("SELECT f.id,f.data_frete,o.identificacao origem,d.identificacao destino,t.nome transportador,f.status,f.valor_frete,(SELECT GROUP_CONCAT(CONCAT(p.nome,' x',fp.quantidade) SEPARATOR '; ') FROM frete_produtos fp JOIN produtos p ON p.id=fp.produto_id WHERE fp.frete_id=f.id) produtos FROM fretes f JOIN enderecos o ON o.id=f.origem_id JOIN enderecos d ON d.id=f.destino_id JOIN transportadores t ON t.id=f.transportador_id WHERE f.data_frete=? ORDER BY f.id");
$q->execute([$date]);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="relatorio-fretes-'.$date.'.csv"');
$out=fopen('php://output','w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out,['Número','Data','Origem','Destino','Transportador','Produtos','Status','Valor do frete'],';');
while($r=$q->fetch()) fputcsv($out,[$r['id'],$r['data_frete'],$r['origem'],$r['destino'],$r['transportador'],$r['produtos'],$r['status'],$r['valor_frete']],';');
fclose($out);
