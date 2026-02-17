<?php
/*******************************************************************************
* SimplePDF - A minimal PDF generation class for PHP (FPDF-like API)           *
* Supports: Text, Pages, Basic Fonts (Core), Cells (Tables)                    *
*******************************************************************************/

class SimplePDF {
    var $page = 0;
    var $n = 2;
    var $buffer = '';
    var $pages = array();
    var $state = 0;
    var $compress;
    var $k;
    var $DefOrientation;
    var $CurOrientation;
    var $StdPageSizes;
    var $DefPageSize;
    var $CurPageSize;
    var $PageSizes = array();
    var $wPt, $hPt;
    var $w, $h;
    var $lMargin;
    var $tMargin;
    var $rMargin;
    var $bMargin;
    var $cMargin;
    var $x, $y;
    var $lasth;
    var $LineWidth;
    var $fontpath;
    var $CoreFonts;
    var $fonts = array();
    var $FontFiles = array();
    var $diffs = array();
    var $FontFamily;
    var $FontStyle;
    var $underline;
    var $CurrentFont;
    var $FontSizePt;
    var $FontSize;
    var $DrawColor;
    var $FillColor;
    var $TextColor;
    var $ColorFlag;
    var $ws;
    var $AutoPageBreak;
    var $PageBreakTrigger;
    var $InHeader = false;
    var $InFooter = false;
    var $ZoomMode;
    var $LayoutMode;
    var $title = '';
    var $subject = '';
    var $author = '';
    var $keywords = '';
    var $creator = '';
    var $AliasNbPages;
    var $PDFVersion;

    function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->k = 2.83464566929134; // Scale factor
        $this->AliasedNbPages = '{nb}';
        $this->SetMargins(10, 10);
        $this->SetAutoPageBreak(true, 20);
        $this->AddPage($orientation, $size);
        $this->SetFont('Courier', '', 10);
        $this->SetDrawColor(0);
        $this->SetFillColor(255);
        $this->SetTextColor(0);
    }

    function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if($right===null) $right = $left;
        $this->rMargin = $right;
    }

    function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    function AddPage($orientation='', $size='') {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->w = 210; 
        $this->h = 297;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        $this->CurOrientation = 'P';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    function SetFont($family, $style='', $size=0) {
        if($family=='') $family = $this->FontFamily;
        else $family = strtolower($family);
        if($size==0) $size = $this->FontSizePt;
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = ['up'=>-100, 'ut'=>50, 'cw'=>chr(32)]; 
    }

    function SetFontSize($size) {
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
    }

    function SetDrawColor($r, $g=null, $b=null) {
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
    }

    function SetFillColor($r, $g=null, $b=null) {
        $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }

    function SetTextColor($r, $g=null, $b=null) {
        $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
    }

    function Text($x, $y, $txt) {
        $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        $this->pages[$this->page] .= $s . "\n";
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        if($this->y + $h > $this->PageBreakTrigger && $this->AutoPageBreak) {
            $this->AddPage();
        }
        if($w==0) $w = $this->w - $this->rMargin - $this->x;
        $s = '';
        if($fill || $border==1) {
            $op = ($fill) ? 'B' : 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $this->k, ($this->h - $this->y) * $this->k, $w * $this->k, -$h * $this->k, $op);
        }
        if($txt!=='') {
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x + 2) * $this->k, ($this->h - ($this->y + $h/1.5)) * $this->k, $this->_escape($txt));
        }
        $this->pages[$this->page] .= $s . "\n";
        
        $this->x += $w;
        if($ln>0) {
            $this->y += $h;
            if($ln==1) $this->x = $this->lMargin;
        }
    }

    function Ln($h=null) {
        $this->x = $this->lMargin;
        $this->y += ($h) ? $h : $this->lasth;
    }

    function _escape($s) {
        return str_replace(array('\\','(',')'), array('\\\\','\\(','\\)'), $s);
    }

    function Output($dest='', $name='') {
        $this->buffer = "%PDF-1.4\n";
        $this->buffer .= "1 0 obj <</Type /Catalog /Pages 2 0 R>> endobj\n";
        $this->buffer .= "2 0 obj <</Type /Pages /Count " . $this->page . " /Kids [";
        for($i=1; $i<=$this->page; $i++) $this->buffer .= (2+$i) . " 0 R ";
        $this->buffer .= "] /MediaBox [0 0 " . sprintf('%.2F %.2F', $this->wPt, $this->hPt) . "]>> endobj\n";
        $this->buffer .= "3 0 obj <</Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding>> endobj\n";
        
        // Pages
        for($i=1; $i<=$this->page; $i++) {
            $content = "BT /F1 " . sprintf('%.2F', $this->FontSizePt) . " Tf ET\n" . $this->pages[$i];
            $this->buffer .= (2+$i) . " 0 obj <</Type /Page /Parent 2 0 R /Resources <</Font <</F1 3 0 R>> >> /Contents " . (2+$this->page+$i) . " 0 R>> endobj\n";
        }
        
        // Page Contents
        for($i=1; $i<=$this->page; $i++) {
            $content = "BT /F1 " . sprintf('%.2F', $this->FontSizePt) . " Tf ET\n" . $this->pages[$i];
            $this->buffer .= (2+$this->page+$i) . " 0 obj <</Length " . strlen($content) . ">> stream\n" . $content . "\nendstream\nendobj\n";
        }
        
        $this->buffer .= "xref\n0 " . (3+$this->page*2) . "\n0000000000 65535 f \n";
        $this->buffer .= "trailer <</Size " . (3+$this->page*2) . " /Root 1 0 R>>\nstartxref\n116\n%%EOF";
        
        if($dest=='F') {
            $f = fopen($name, 'wb');
            fwrite($f, $this->buffer);
            fclose($f);
        } else {
            echo $this->buffer;
        }
    }
}
?>
