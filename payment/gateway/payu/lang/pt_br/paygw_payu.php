<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'paygw_payu', language 'pt_br'
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name and description
$string['pluginname'] = 'PayU América Latina';
$string['pluginname_desc'] = 'O plugin PayU permite receber pagamentos através da plataforma PayU para países da América Latina.';
$string['gatewayname'] = 'PayU';
$string['gatewaydescription'] = 'PayU é um provedor de gateway de pagamento autorizado para processar transações com cartão de crédito na América Latina.';

// Countries
$string['country'] = 'País de operação';
$string['country_ar'] = 'Argentina';
$string['country_br'] = 'Brasil';
$string['country_cl'] = 'Chile';
$string['country_co'] = 'Colômbia';
$string['country_mx'] = 'México';
$string['country_pa'] = 'Panamá';
$string['country_pe'] = 'Peru';

// Environment settings
$string['environment'] = 'Ambiente';
$string['environment_sandbox'] = 'Sandbox (Testes)';
$string['environment_production'] = 'Produção (Pagamentos reais)';

// Credentials
$string['merchantid'] = 'ID do Comerciante';
$string['merchantid_help'] = 'Seu ID de Comerciante no PayU. Necessário para o ambiente de produção.';
$string['accountid'] = 'ID da Conta';
$string['accountid_help'] = 'Seu ID de Conta PayU para o país selecionado. Necessário para o ambiente de produção.';
$string['apikey'] = 'Chave API';
$string['apikey_help'] = 'Sua Chave API do PayU. Mantenha-a segura! Necessária para o ambiente de produção.';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Seu API Login do PayU. Necessário para o ambiente de produção.';
$string['publickey'] = 'Chave Pública';
$string['publickey_help'] = 'Sua Chave Pública do PayU para tokenização (opcional).';

// Language settings
$string['language'] = 'Idioma da página de pagamento';
$string['language_es'] = 'Espanhol';
$string['language_en'] = 'Inglês';
$string['language_pt'] = 'Português';

// Payment settings
$string['abouttopay'] = 'Você está prestes a pagar por';
$string['payment'] = 'Pagamento';
$string['sendpaymentbutton'] = 'Pagar com PayU';
$string['redirecting'] = 'Redirecionando para PayU...';
$string['redirecting_message'] = 'Você está sendo redirecionado para a página segura de pagamento do PayU. Por favor, aguarde...';

// Status messages
$string['payment_success'] = 'Pagamento realizado com sucesso!';
$string['payment_error'] = 'Erro no pagamento';
$string['payment_declined'] = 'O pagamento foi recusado';
$string['payment_pending'] = 'O pagamento está pendente de aprovação';
$string['payment_expired'] = 'O pagamento expirou';
$string['payment_unknown'] = 'Status de pagamento desconhecido';
$string['signature_invalid'] = '(Aviso: Assinatura inválida)';

// Test mode
$string['autofilltest'] = 'Preencher dados de teste automaticamente';
$string['autofilltest_help'] = 'Preenche automaticamente os dados do cartão de teste no modo sandbox para facilitar os testes.';
$string['sandbox_note'] = '<strong>Nota:</strong> Ao usar o ambiente Sandbox, as credenciais de teste serão usadas automaticamente. Você não precisa inserir credenciais de produção.';

// Optional payment modes
$string['skipmode'] = 'Permitir pular pagamento';
$string['skipmode_help'] = 'Mostra um botão para pular o pagamento. Útil para pagamentos opcionais em cursos públicos.';
$string['skipmode_text'] = 'Se você não puder fazer um pagamento através do sistema de pagamentos, pode clicar neste botão.';
$string['skippaymentbutton'] = 'Pular pagamento';

$string['passwordmode'] = 'Habilitar senha de bypass';
$string['password'] = 'Senha de bypass';
$string['password_help'] = 'Os usuários podem pular o pagamento usando esta senha. Útil quando o sistema de pagamento não está disponível.';
$string['password_text'] = 'Se você não puder fazer um pagamento, solicite a senha ao administrador e digite-a aqui.';
$string['password_error'] = 'Senha de pagamento inválida';
$string['password_success'] = 'Senha de pagamento aceita';
$string['password_required'] = 'A senha é necessária quando o modo senha está habilitado';

// Cost settings
$string['fixcost'] = 'Modo de preço fixo';
$string['fixcost_help'] = 'Desativa a capacidade dos alunos de pagar com um valor personalizado.';
$string['suggest'] = 'Preço sugerido';
$string['maxcost'] = 'Custo máximo';
$string['maxcosterror'] = 'O preço máximo deve ser maior que o preço sugerido';
$string['paymore'] = 'Se você quiser pagar mais, simplesmente digite seu valor em vez do valor sugerido.';

// URLs
$string['callback_urls'] = 'URLs de configuração';
$string['confirmation_url'] = 'URL de confirmação';
$string['response_url'] = 'URL de resposta';

// Errors
$string['error_txdatabase'] = 'Erro ao gravar transação no banco de dados';
$string['error_notvalidtxid'] = 'ID de transação inválido';
$string['error_notvalidpayment'] = 'Pagamento inválido';
$string['error_notvalidpaymentid'] = 'ID de pagamento inválido';
$string['production_fields_required'] = 'Todas as credenciais são necessárias para o ambiente de produção';

// Privacy
$string['privacy:metadata'] = 'O plugin PayU armazena dados pessoais para processar pagamentos.';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Armazena dados de transações de pagamento';
$string['privacy:metadata:paygw_payu:userid'] = 'ID do usuário';
$string['privacy:metadata:paygw_payu:courseid'] = 'ID do curso';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Nomes dos grupos';
$string['privacy:metadata:paygw_payu:country'] = 'País da transação';
$string['privacy:metadata:paygw_payu:transactionid'] = 'ID da transação PayU';
$string['privacy:metadata:paygw_payu:referencecode'] = 'Código de referência';
$string['privacy:metadata:paygw_payu:amount'] = 'Valor do pagamento';
$string['privacy:metadata:paygw_payu:currency'] = 'Moeda';
$string['privacy:metadata:paygw_payu:state'] = 'Estado da transação';

// Notifications
$string['messagesubject'] = 'Notificação de pagamento';
$string['messageprovider:payment_receipt'] = 'Recibo de pagamento';
$string['message_payment_completed'] = 'Olá {$a->firstname},
Seu pagamento de {$a->fee} {$a->currency} (ID: {$a->orderid}) foi concluído com sucesso.
Se você não conseguir acessar o curso, entre em contato com o administrador.';
$string['message_payment_pending'] = 'Olá {$a->firstname},
Seu pagamento de {$a->fee} {$a->currency} (ID: {$a->orderid}) está pendente de aprovação.
Nós o notificaremos assim que o pagamento for confirmado.';