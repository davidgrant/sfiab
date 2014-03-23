<?php

require_once('tcpdf_6/tcpdf_config_sfiab.php');
require_once('tcpdf_6/tcpdf.php');

class pdf extends TCPDF {

	/* Variables for labels */
	var $label_width, $label_height;
	var $label_xspacer, $label_yspacer;
	var $label_rows, $label_cols, $labels_per_page;
	var $label_page_lmargin, $label_page_tmargin;
	var $label_show_fair, $label_show_box, $label_show_logo;
	var $current_label_index;

	var $footer_string;

	function __construct($report_name='', $report_year, $format='LETTER', $orientation='P')
	{
		global $config;
		/* Start an output PDF */

		/* Orientation - Page orientation:
		    * P or Portrait (default)
		    * L or Landscape */
//		$orientation = 'P';

		/* Units - User measure unit:
		/* We're going to do everything in mm and convert, even if we worked
		 * in pts (1/72 of an inch) we'd still need decimals */

		/* Format - Page size
			It can be either one of the following values (case insensitive) or 
			a custom format in the form of a two-element array containing the width and the height (expressed in the unit given by unit).
    * 4A0 * 2A0 * A0 * A1 * A2 * A3 * A4 (default) * A5 * A6 * A7 * A8 * A9 * A10 
    * B0 * B1 * B2 * B3 * B4 * B5 * B6 * B7 * B8 * B9 * B10
    * C0 * C1 * C2 * C3 * C4 * C5 * C6 * C7 * C8 * C9 * C10
    * RA0 * RA1 * RA2 * RA3 * RA4
    * SRA0 * SRA1 * SRA2 * SRA3 * SRA4
    * LETTER * LEGAL * EXECUTIVE * FOLIO 
    * array($width, $height) */
	//	$format = 'LETTER';

		/* Other args: true = turn on unicode, 
				set encoding to UTF-8,
				turn off temp-files-on-disk */
		parent::__construct($orientation, 'mm', $format, true, 'UTF-8', false);

		// set document information
		$this->SetCreator('SFIAB');
		$this->SetAuthor('SFIAB');
		$this->SetTitle(i18n($config['fair_name']));
		$this->SetSubject($report_name);
		$this->SetKeywords('');

		/* Set default header data (K_PATH_IMAGES/logo-500.jpg, 16mm wide, header, name)
		 * PDFs use JPG internally, so we should feed it a jpeg, if we dont', tcpdf will
		 * convert it to a jpg anyway, and that takes FOREVER if there's lots of 
		 * pages/labels.*/
		$this->SetHeaderData('logo-500.jpg', 16 /* mm */, 
				i18n($config['fair_name']).'  '.$report_year,	i18n($report_name));

		// set header and footer fonts
		$this->setHeaderFont(Array('helvetica', '', 14));
		$this->setFooterFont(Array('helvetica', '', 8));

		// set default monospaced font
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		//set margins
		$this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$this->SetHeaderMargin(PDF_MARGIN_HEADER);
		$this->SetFooterMargin(PDF_MARGIN_FOOTER);

		//set auto page breaks
		$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$this->setPrintFooter(TRUE);

		//set image scale factor
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO); 

		$this->current_label_index = 1;
		$this->current_label_row = 0;
		$this->current_label_col = 0;

		date_default_timezone_set('UTC');
		$this->footer_string = date("Y-m-d h:ia").' - '.$report_name;


		//set some language-dependent strings
		//$this->setLanguageArray($l); 
//print_r($this->fontlist);
		
	}

	/* Given a cell of width $w, format  $txt so it fits into that
	 * width, using as many lines as necessary, with 
	 * font ($fontname, $fontstyle, $fontsize).  
	 * - fontsize is in points
	 * Returns an array of lines that fit in the width.  
	 * Compute the final height with: 
	 *   count($lines) * ($this->cMargin * 2 + $fontsize_not_in_pts) */
	function _cell_lines($w,$txt,$fontname, $fontstyle, $fontsize)
	{
		$index = 0;
		$lines  = array();

		/* Get an array of widths */
		$width = $this->getStringWidth($txt,$fontname, $fontstyle,$fontsize,true);
		$chars = $this->UTF8StringToArray($txt);
		$count = count($width); // strlen(utf8_decode($txt));

		$curr_width = $this->cMargin * 2;
		$last_space_index = -1;
		$start_index = 0;

		for($index=0; $index<$count;$index++) {
			$newline = false;
			$skip = false;

			/* Special chars */
			switch($this->unichr($chars[$index])) {
			case ' ': case "\r": case "\t":
				$last_space_index = $index;
				break;

			case "\n":
				$newline = true;
				$skip = true;
				break;
			}

			/* Check for width overflow */
			if($skip == true) {
				/* Do nothing with it */
			} else if ($curr_width + $width[$index] > $w) {
				/* Backup index, leave it pointing to
				 * the last char we print, so when we
				 * increment in the next iteration we
				 * get the next char (the one that just
				 * caused this overflow */
				$index--;
				$newline = true;
			} else {
				$curr_width += $width[$index];
			}

			if($newline) {
				if($last_space_index != -1) {
					/* Backup to the last space index, if there is one */
					$end_index = $last_space_index;
					$index = $last_space_index;
				} else {
					/* No, use the whole line then */
					$end_index = $index;
				}
				$lines[] = $this->UTF8ArrSubString($chars,$start_index,$end_index);
				/* Reset width, set start index */
				$curr_width = $this->cMargin * 2;
				$last_space_index = -1;
				$start_index = $index+1;
			}

		}
		
		$lines[] = $this->UTF8ArrSubString($chars,$start_index,$index);
		return $lines;
	}
	/* Cell( float $w, [float $h = 0], [string $txt = ''], [mixed $border = 0],
		[int $ln = 0], [string $align = ''], [int $fill = 0], [mixed $link = ''], 
		[int $stretch = 0], [boolean $ignore_min_height = false]) */


	function FitCell($w,$h=0,$txt='',$border=0,$ln=0,$align='',$valign='',$on_overflow='scale')
	{
		$x = $this->getX();
		$y = $this->getY();
		$orig_fs = $this->getFontSizePt();
		$add_ellipses = false;

		$fontsize = $orig_fs;
		while(1) {
			$lines = $this->_cell_lines($w, $txt, '', '', $fontsize);

			/* this->FontSize is always correct, we change $fontisze
			 * below, but then use that to set the internal fontsize */
			$cell_height = $this->cMargin * 2 + $this->FontSize;
			$total_height = $cell_height * count($lines);

			if($total_height <= $h) {
				/* It fits! */
				break;
			}

			/* else, it doesn't fit */
			if($on_overflow == 'scale') {	
				/* reduce the font size and try again */
				$fontsize -= 0.5;
				$this->setFontSize($fontsize);
				continue;
			} 

			/* If it doesn't fit, and we're not scaling, it must
			 * be a truncate.  Compute the number of lines that 
			 * can be displayed */
			$display_lines = floor($h / $cell_height);
			/* Adjust height */
			$label_h -= (count($lines) - $display_lines) * $cell_height;

			/* truncate */
			$lines = array_slice($lines, 0, $display_lines);

			if($on_overflow == '...') $add_ellipses = true;
			break;
		}

		/* SetX, find Y based on alignment */
		switch($valign) {
		case 'M': /* Middle */
			$this->SetXY($x, $y + ($h - $total_height) / 2); 
			break;
		case 'B': /* Bottom */
			$this->SetXY($x, $y + ($h - $total_height));
			break;
		case 'T': default: /* Top */
			$this->SetXY($x, $y); 
			break;
		}

		/* Fontsize will be correctly set here */
		/* Cell( float $w, [float $h = 0], [string $txt = ''], [mixed $border = 0],
			[int $ln = 0], [string $align = ''], [int $fill = 0], [mixed $link = ''], 
			[int $stretch = 0], [boolean $ignore_min_height = false]) */
		foreach($lines as $l) {
			$this->Cell($w, 0, $l, 0, 2, $align, 0, 0, 0, false);
		}

		if($add_ellipses) {
			/* Only use fontsize so the '...' is really close to the lower right. */
			$this->SetXY($x, $y + $h - $cell_height);
			$this->Cell($w, 0, '...', 0, 0, 'R');
		}

		/* Restore original location */
		$this->SetXY($x,$y);

		/* Restore original fontsize */
		$this->setFontSize($orig_fs);

		/* Print a Cell to print the border (if we're supposed to), and
		 * to leave x,y wherever $ln tells us to */
		$this->Cell($w, $h, '', $border, $ln, 'R');

		return $total_height;
	}

	function GetFontList()
	{
		if(!is_object($this)) {
			$pdf = new pdf();
			return $pdf->GetFontList();
		} 
		$this->getFontsList();
		return $this->fontlist;
	}

	function Footer()
	{
		$ormargins = $this->getOriginalMargins();
		$pagenumtxt = i18n('Page').' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();

		$this->SetX($ormargins['left']);
		$this->Cell(0, 0, $pagenumtxt, 'T', 0, 'R');

		$this->SetX($ormargins['left']);
		$this->Cell(0, 0, $this->footer_string, 0, 0, 'C');
	}

	function setup_for_labels($show_box, $show_fair, $show_logo, $width, $height, $xspacer, $yspacer, $rows, $cols)
	{
		/* No headers and footers */
		$this->setPrintHeader(false);
		$this->setPrintFooter(false);

		/* No auto-pagebreaks */
		$this->SetAutoPageBreak(false);


		/* the page size/orientation is already set */
		$pw = $this->getPageWidth();
		$ph = $this->getPageHeight();

		$this->label_show_box = $show_box;
		$this->label_show_fair = $show_fair;
		$this->label_show_logo = $show_logo;

		$this->label_width=$width;
		$this->label_height=$height;
		$this->label_xspacer=$xspacer;
		$this->label_yspacer=$yspacer;

		$this->label_rows=$rows;
		$this->label_cols=$cols;
		$this->labels_per_page=$rows * $cols;

		/* labels are always centered in the page */
		
		$this->label_page_lmargin=( $pw - ($cols*$width) - ($xspacer*($cols-1)) )/2;
		$this->label_page_tmargin=( $ph - ($rows*$height) - ($yspacer*($rows-1)) )/2;

		/* Setup so that the first call to label_new also creates
		 * a new page */
		$this->current_label_index = $this->labels_per_page - 1;
	}

	function label_new()
	{
		global $config;
		/* Advance to new label */
//		echo "cindex=$this->current_label_index, perpage=$this->labels_per_page\n";
		if($this->current_label_index + 1 == $this->labels_per_page) {
//			echo "addpage\n";
			$this->AddPage();
			$this->current_label_index = 0;
		} else {
			$this->current_label_index++;
		}

		/* Get row/col, and position of label */
		$r = floor($this->current_label_index / $this->label_cols);
		$c = floor($this->current_label_index % $this->label_cols);

		$lmargin = $this->label_page_lmargin + ($c * ($this->label_width + $this->label_xspacer) );
		$tmargin = $this->label_page_tmargin + ($r * ($this->label_height + $this->label_yspacer) );

		/* Move margins for this label */
//		echo "r=$r, c=$c, cols=$this->label_cols\n";
//		echo "Margins -> ($lmargin, $tmargin)\n";
		$this->SetMargins($lmargin, $tmargin, $lmargin + $this->label_width);

		if($this->label_show_box)
			$this->label_rect(0,0,$this->label_width, $this->label_height);

	}


	/* 	align = left, center, right
		valign = top, middle, bottom,
		fontname,
		fontstyle,
		fontsize,
		border = true/false
		on_overflow = truncate, ..., scale */

	function label_text($x,$y,$w,$h,$text,$border,$align,$valign,$fontname,$fontstyle,$fontsize,
				$on_overflow)
	{
		$orig_name = $this->getFontFamily();
		$orig_style = $this->getFontStyle();
		$orig_size = $this->getFontSizePt();
		$orig_x = $this->GetX();
		$orig_y = $this->GetY();

		/* Do horiz/vert align */
		$align_data = array('left' => 'L', 'center' => 'C', 'right' => 'R');
		$valign_data = array('top' => 'T', 'middle' => 'M', 'bottom' => 'B');
		$align = $align_data[$align];
		$valign = $valign_data[$valign];

		/* Set position and font */
		$st = array('bold' => 'B', 'italic' => 'I', 'underline' => 'U', 'strikethrough' => 'D');
		$fs = '';
		if(is_array($fontstyle)) {
			foreach($fontstyle as $s) $fs .= $st[$s];
		}

		if($fontsize == 0) $fontsize = 10; /* FIXME: getdefaultfontsize? */

		$this->SetXY($this->lMargin + $x,$this->tMargin + $y);
//		echo "position (x,y)=($x,$y)\n";
//		echo "margin (l,t)=({$this->lMargin},{$this->tMargin})\n";
//		echo "(x,y)=(".($this->lMargin + $x).",".($this->tMargin + $y).")\n";
		$this->SetFont($fontname, $fs, $fontsize);

		/* Print text */
		$this->FitCell($w,$h, $text,  $border ? 1 : 0, 2, 
				$align, $valign, $on_overflow);

		/* Restore position and font */
		$this->SetFont($orig_name, $orig_style, $orig_size);
		$this->SetXY($orig_x, $orig_y);
	}

	function label_rect($x,$y,$w,$h) 
	{
		$this->Rect($this->lMargin + $x, $this->tMargin + $y, $w, $h);
	}

	function label_fair_logo($x, $y, $w, $h, $show_box)
	{
		global $config;
		$img_dir = $_SERVER['DOCUMENT_ROOT'].$config['SFIABDIRECTORY'].'/data';
		/* Scale image to proportinally fit in w x h */
		$this->Image("$img_dir/logo.jpg", $this->lMargin + $x, $this->tMargin + $y,
				$w, $h, '', '', '', true,
				300, '', false, false, $show_box ? 1 : 0, true);
	}

/****************************************************************************
 * Table handling */
 	function hr()
	{
		$this->Cell(0, 1, '', 'B', 1, '');
		$this->Cell(0, 1, '', '', 1, '');
	}

	function vspace($space)
	{
		$this->SetY($this->GetY() + $space);
	}

	function setup_for_tables($show_box, $show_fair, $show_logo, $width, $height, $xspacer, $yspacer, $rows, $cols)
	{
		$this->SetFont('times', '', 10);

		/* Need to start with a page if autopagebreak is on */
		$this->addPage();
	}

	function heading($text)
	{
		/* TCPDF will spill this cell over to the next page if the height
		 * doesn't fit, so we don't have to do any height calculations, just
		 * create an oversized cell with nothing in it to force a spillover if
		 * at least the first table row won't fit with the header */
		/* header font size + 5mm + curr font size (2st table rows)*/
		$h = ($this->FontSize * 1.5) + 5 + ($this->FontSize * 2);
		$this->Cell(0, $h, '', 0, 2, '', 0, 0, 0, false);
		$this->SetY($this->GetY() - $h);

		/* Now print it in a normal sized cell with a bit of bottom padding
		 * before the table starts */
		$h = ($this->FontSize * 1.5) + 5;
		$orig = $this->getFontSizePt();
		$this->setFont('', 'B', $orig * 1.5);
		$this->Cell(0, $h, $text, 0, 2, '', 0, 0, 0, false);
		$this->setFont('', '', $orig);
	}

	/* Generates the HTML for a table */
	function get_table_html($table)
	{
		//echo "Add Table\n<pre>";
		//print_r($table);
		/* Compute the lines in height of each row for pagination */
		$html = '<table cellpadding="0"><thead><tr>';
		foreach($table['fields'] as $f) {
			$col = $table['col'][$f];
			$html .= "<td style=\"border-bottom:1px solid black; border-top:1px solid black\" width=\"{$table['widths'][$f]}mm\" align=\"center\"><b>{$table['header'][$f]}</b></td>";
		}
		$html .= '</tr></thead>';

		/* Width of "..." */
		$e_width = $this->getStringWidth('...', '', '', '', false);

		$row_alternator = 0;
		$last_projectnumber = '';
		foreach($table['data'] as $row) {

			/* Hack to alternate rows for students in the same project */
			if(in_array('pn', $table['fields']) && $row['pn'] != '') {
				if($last_projectnumber == $row['pn']) 
					$row_alternator = !$row_alternator;
				$last_projectnumber = $row['pn'];
			}

			if($row_alternator == 0) 
				$style = "";
			else 
				$style='style="background-color:#DDDDDD"';

			$row_alternator = !$row_alternator;

			$html .= "<tr $style >";
			foreach($table['fields'] as $f) {
				$col = $table['col'][$f];
				$d = $row[$f];

				/* unfortunately, HTML doesn't do overflow the 
				 * way we want, so compute the width of each cell
				 * and truncate the text if needed */
				if($table['col'][$f]['on_overflow'] == '...' || $table['col'][$f]['on_overflow'] == 'truncate' ) {
//					echo "check for overflow... $d";
					/* See if $d fits in the allowed width */
					$width = $this->getStringWidth($d, '', '', '', true);
					$target_w = $table['widths'][$f];
					/* FIXME, this doesn't work all the time
					 * there must be some cellpadding that HTML is doing */
					$target_w -= 1.4111111111;  /* 2 points * 2*/
					$target_w -= 0.7055555556;  /* 2 points */

					$cw = 0;
//					echo "target=$target_w, w=";
//					print_r($width);
					for($x=0; $x<count($width); $x++) {
						$w = $width[$x];
//						echo "w=$w, total=".($cw+$w)." target=$target_w\n";
						if($cw + $w > $target_w) {
							/* Overflow */
							while($x >= 0) {
								$x--;
								$cw -= $width[$x];
								if($cw + $e_width < $target_w) break;
							}
							$d = substr($d, 0, $x).'...';
							break;
						}
						$cw += $w;
					}
				}

				$html .= "<td width=\"{$table['widths'][$f]}mm\" align=\"{$table['col'][$f]['align']}\">$d</td>";
			}
			$html .= '</tr>';
		}

		$cols = count($table['fields']);

		$t = count($table['data']);
		$txt = "(Rows: $t)";

		if($table['total'] != 0) {
			if(array_key_exists('total_format', $table)) {
				$total = sprintf($table['total_format'], $table['total']);
			} else {
				$total =  $table['total'];
			}
			$txt .= " (Total: $total)";
		}
		$html .= "<tr><td colspan=\"$cols\" style=\"border-top:1px solid black\" align=\"right\">$txt</td></tr>";
		$html .= '</table>';

		return $html;
	}

	function add_table($table)
	{
		$orig_lmargin = $this->lMargin;
		$orig_tmargin = $this->tMargin;

		$html = $this->get_table_html($table);
		$total_width = 0;
		foreach($table['fields'] as $f) {
			$total_width += $table['widths'][$f];
		}

		$lpad = (($this->getPageWidth() - $this->lMargin - $this->rMargin) - $total_width)/2;

/*		print("<pre>");
		print("lpad=$lpad");
		print("pagewidth={$this->getPageWidth()} - lmargin={$this->lMargin} - rmargin={$this->rMargin} - table_width=$total_width");

		print("set lmargin to {($orig_lmargin + $lpad)}");
*/
		$this->SetLeftMargin($orig_lmargin + $lpad);
		$this->writeHTML($html, false, false, false, false, '');
		$this->SetLeftMargin($orig_lmargin);

	}

	function output($filename='', $dest='I') 
	{
		if($filename == '') {
			$filename=strtolower($this->subject);
			$filename=preg_replace("/[^a-z0-9]/","_",$filename).'.pdf';
		}
		parent::Output($filename, $dest);
	}

	function barcode_2d($x,$y,$w,$h,$text)
	{
		$style = array( 'border' => 1,
				'vpadding' => 'auto',
				'hpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255)
				'module_width' => 1, // width of a single module in points
				'module_height' => 1 // height of a single module in points
			);

//		$this->SetXY($x, $y);
		$this->write2DBarcode($text, 'QRCODE,H', $x, $y, $w, $h, $style, 'N');
	}
}
