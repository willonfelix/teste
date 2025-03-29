<?php
header("Content-Type: application/json");

$ip = $_SERVER['REMOTE_ADDR'];

//echo "IP do cliente: $ip";
//exit("");
    
//if ($ip != 'a208.98.35.208' && $ip != 'a189.6.7.105' && $ip != 'a179.131.212.175') {
//    http_response_code(401);
//   echo json_encode(["message" => "Unauthorized temp"]);
//    exit("");
//}

// Definindo a rota e o método HTTP
$rota = $_GET['rota'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'];

if ($rota == 'consulta') {
    if ($metodo == 'POST') {
        try {
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Configurando o protocolo de segurança
            stream_context_set_default([
                'ssl' => [
                    'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $oProdutoId = $input['ProdutoId'];                        
            $oCpfcnpj = $input['CpfCnpj'];
            $oTipoPessoa = strlen($oCpfcnpj) == 11 ? 'F' : 'J';
            $oUrl = 'https://servicos.spc.org.br:443/spc/remoting/ws/consulta/consultaWebService?wsdl';
            $oGrupo1 = '';
            $oGrupo2 = '';
            $oGrupo3 = '';            
            $pBody = ['Cmc7' => $input['Cmc7'] ];
            $oCodigoInsumoOpcional = $input['CodigoInsumoOpcional'];
            
            $dadosEnvio = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservice.consulta.spcjava.spcbrasil.org/">';
            $dadosEnvio .= '   <soapenv:Header/>';
            $dadosEnvio .= '   <soapenv:Body>';
            $dadosEnvio .= '      <web:filtro>';
            $dadosEnvio .= '         <codigo-produto>' .$oProdutoId .'</codigo-produto>';
            $dadosEnvio .= "         <tipo-consumidor>{$oTipoPessoa}</tipo-consumidor>";
            $dadosEnvio .= "         <documento-consumidor>{$oCpfcnpj}</documento-consumidor>";
            if ($pBody['Cmc7'] != null && strlen($pBody['Cmc7']) > 0) {
                $oGrupo1 = substr($pBody['Cmc7'], 0, 8);
                $oGrupo2 = substr($pBody['Cmc7'], 8, 10);
                $oGrupo3 = substr($pBody['Cmc7'], 18, 12);
                $dadosEnvio .= '         <utiliza-CMC7>' . (!empty($pBody['Cmc7']) ? 'true' : 'false') . '</utiliza-CMC7>';
                $dadosEnvio .= "         <cmc71-cheque-inicial>{$oGrupo1}</cmc71-cheque-inicial>";
                $dadosEnvio .= "         <cmc72-cheque-inicial>{$oGrupo2}</cmc72-cheque-inicial>";
                $dadosEnvio .= "         <cmc73-cheque-inicial>{$oGrupo3}</cmc73-cheque-inicial>";
                $dadosEnvio .= '         <quantidade-cheque>' . (!empty($pBody['Cmc7']) ? '1' : '0') . '</quantidade-cheque>';
            }
            if ($oCodigoInsumoOpcional != null && $oCodigoInsumoOpcional == 78) {
                $dadosEnvio .= "         <codigo-insumo-opcional>{$oCodigoInsumoOpcional}</codigo-insumo-opcional>";
            }
            $dadosEnvio .= '      </web:filtro>';
            $dadosEnvio .= '   </soapenv:Body>';
            $dadosEnvio .= '</soapenv:Envelope>';

            $oToken = $input['Token'];            
            //echo $oToken;
            //exit("");
            
            if($oToken == ''){
                http_response_code(201);
                echo json_encode(["message" => "Sem conta consulta definida."]);
                exit("");
            }
            
            //echo $dadosEnvio;
            //exit("");
            
            $contextOptions = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: text/xml; charset=utf-8',
                        "Authorization: Basic {$oToken}"
                    ],
                    'content' => $dadosEnvio
                ]
            ];
            
            //echo $dadosEnvio;
            //exit("");
            
            $context = stream_context_create($contextOptions);
            $response = file_get_contents($oUrl, false, $context);
            
            if ($response === false) {
                //echo json_encode(["message" => "houve erro na resposta vinda do fornecedor." .$response]);
                //http_response_code(400);
                echo json_encode("houve erro na resposta vinda do fornecedor.");
                exit("");
            } else {
                echo $response;
            }
            
        } catch (Exception $e) {
            echo json_encode(["message" => $e->getMessage()]);
            exit("");
        }
        
    } else {
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido"]);
    }
} 
 elseif ($rota == 'teste') {
    if ($metodo == 'POST') {
            
        $input = json_decode(file_get_contents('php://input'), true);
        
        $oUrlToken = 'https://api.simconsulta.com.br/autorizacao/token';
        
        $dadosToken = [
            "ClienteId"     => $input['ClienteId'],
            "email"         => $input['Email'],
            "PrivateKey"    => $input['PrivateKey']
        ];
        
        //echo json_encode($dadosToken);
        //exit("");
        
        $contextOptionsToken = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json'
            ],
            'content' => json_encode($dadosToken)
                ]
            ];

        $contextToken = stream_context_create($contextOptionsToken);
        $responseToken = file_get_contents($oUrlToken, false, $contextToken);
    
        if ($responseToken === false) {
            http_response_code(400);
            echo json_encode(["message" => "Erro ao consultar o serviço."]);
        } else {
        
            $oToken = $responseToken;

            $oUrl = 'https://api.simconsulta.com.br/SPCMixMais/testa';
            $dados = [
                "CpfCnpj"     => $input['CpfCnpj'],
                "PrivateKey"  => $input['PrivateKey'],
                "Produto"     => $input['ProdutoId']
            ];
            
            $contextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    "Authorization: Bearer {$oToken}"
                ],
                'content' => json_encode($dados)
                    ]
                ];
                
            $context = stream_context_create($contextOptions);
            $response = file_get_contents($oUrl, false, $context);
        
            if ($response === false) {
                http_response_code(400);
                echo json_encode(["message" => "Erro ao consultar o serviço."]);
            } else {
                echo $response;
            }
        } 
    } 
}
else {
    http_response_code(404);
    echo json_encode(["message" => "Rota não encontrada"]);
}
?>
