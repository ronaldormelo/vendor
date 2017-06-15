<?php

/**
 * PHP Boleto ZF2 - Versão Beta 
 * 
 * Este arquivo está disponível sob a Licença GPL disponível pela Web
 * em http://pt.wikipedia.org/wiki/GNU_General_Public_License 
 * Você deve ter recebido uma cópia da GNU Public License junto com
 * este pacote; se não, escreva para: 
 * 
 * Free Software Foundation, Inc.
 * 59 Temple Place - Suite 330
 * Boston, MA 02111-1307, USA.
 * 
 * Originado do Projeto BoletoPhp: http://www.boletophp.com.br 
 * 
 * Adaptação ao Zend Framework 2: João G. Zanon Jr. <jot@jot.com.br>
 * 
 */

namespace PhpBoletoZf2\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use DOMPDFModule\View\Model\PdfModel;
use PhpBoletoZf2\Model\BoletoItau;
use PhpBoletoZf2\Model\Sacado;

class ItauController extends AbstractActionController {

    public function indexAction() {
        /**
         * Definindo o layout padrão do boleto
         */
        $this->layout('layout/boleto');

        $auth = $this->getServiceLocator()->get('AuthService')->getStorage()->read();

        $id = \Estrutura\Helpers\Cript::dec($this->params('id'));

        if ($id && is_numeric($id)) {

            $pagamento = $this->getServiceLocator()->get('Pagamento/Service/PagamentoService')->listPagamentoByIdAuth($auth, $id);
        } else {

            $pagamento = $this->getServiceLocator()->get('Pagamento/Service/PagamentoService')->listPagamento($auth);
        }

        if (is_object($pagamento)) {

            $dataReferencia = new \DateTime(date('Y-m-d', strtotime($pagamento->getDtMesReferencia())));
            $dataVencimento = new \DateTime(date('Y-m-d', strtotime($pagamento->getDtVencimento())));
            $dataAtual = new \DateTime(date('Y-m-d'));

            $dataVencimentoAux = ($dataAtual > $dataVencimento ? $dataAtual : $dataVencimento);

            $data = [
                'dataVencimento' => $dataVencimentoAux->format('d/m/Y'),
                'valor' => \Estrutura\Helpers\Valor::float($pagamento->getVlDocumento()),
                'nossoNumero' => \Estrutura\Helpers\String::mascaraformato('########', $pagamento->getId()),
                'numeroDocumento' => \Estrutura\Helpers\String::mascaraformato('########', $pagamento->getId()),
                'dataDocumento' => date('d/m/Y'),
                'dataProcessamento' => date('d/m/Y'),
                'demonstrativo1' => 'GanheMais.Net',
                'demonstrativo2' => 'Pagamento de assinatura mensal, referencia: ' . $dataReferencia->format('m/Y'),
                'quantidade' => 1,
                'valorUnitario' => \Estrutura\Helpers\Valor::float($pagamento->getVlDocumento()),
                'nome' => $auth->nm_usuario . ' (' . $auth->em_email . ')',
            ];

            $boleto = new BoletoItau($data);
            $sacado = new Sacado($data);

            $itau = $this->getServiceLocator()
                    ->get('Boleto\Itau');
            $itau->setSacado($sacado)
                    ->setBoleto($boleto);

            $dados = $itau->prepare();

            switch ($this->params()->fromRoute('format')) {
                case 'html' :
                default :
                    return new ViewModel(array('dados' => $dados));

                case 'pdf' :
                    $pdf = new PdfModel();
                    $pdf->setOption('filename', 'boleto-itau');
                    $pdf->setOption('enable_remote', true);
                    $pdf->setOption('paperSize', 'a4'); // Defaults to "8x11" 
                    $pdf->setVariables(array('dados' => $dados));
                    return $pdf;
            }
        } else {
            echo utf8_decode('Boleto não localizado.');
            exit;
        }
    }

}
