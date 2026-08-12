<?php

/**
 * Base comum dos PDFs de certificados.
 *
 * A marca-d'agua e desenhada logo apos a criacao de cada pagina. O marcador
 * nativo do TCPDF e reposicionado em seguida para que textos, preenchimentos
 * e bordas de tabelas sejam inseridos por cima dela.
 */
if (!class_exists('CertificadoPdfComMarcaDagua')) {
    class CertificadoPdfComMarcaDagua extends TCPDF
    {
        public function Header() {}

        public function Footer() {}

        public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
        {
            parent::AddPage($orientation, $format, $keepmargins, $tocpage);

            $estadoGrafico = $this->getGraphicVars();
            $this->InHeader = true;
            $this->desenharMarcaDaguaCertificado();
            $this->InHeader = false;
            $this->setGraphicVars($estadoGrafico);

            // TCPDF insere bordas e preenchimentos a partir deste marcador.
            // Reposiciona-lo apos a imagem evita que a marca cubra tabelas.
            $this->setPageMark();
        }

        protected function desenharMarcaDaguaCertificado(): void
        {
            $marcaDagua = __DIR__ . '/../img/marca-dagua.png';
            if (!is_file($marcaDagua) || filesize($marcaDagua) <= 100) {
                return;
            }

            $largura = 105.0;
            $dimensoes = @getimagesize($marcaDagua);
            $altura = $largura;
            if (is_array($dimensoes) && $dimensoes[0] > 0 && $dimensoes[1] > 0) {
                $altura = $largura * $dimensoes[1] / $dimensoes[0];
            }

            $x = ($this->getPageWidth() - $largura) / 2;
            $y = ($this->getPageHeight() - $altura) / 2;
            $this->Image($marcaDagua, $x, $y, $largura, 0, 'PNG', '', '', true, 300);
        }
    }
}
