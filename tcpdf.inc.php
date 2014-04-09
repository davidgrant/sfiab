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

		/* Turning off subsetting is supposed to help */
		$this->setFontSubsetting(false);

		$this->current_label_index = 1;
		$this->current_label_row = 0;
		$this->current_label_col = 0;

		date_default_timezone_set('UTC');
		$this->footer_string = date("Y-m-d h:ia").' - '.$report_name;

		$this->enable_debug = false;
//		$this->enable_debug = true;

		if($this->enable_debug) print("<pre>");

		//set some language-dependent strings
		//$this->setLanguageArray($l); 
//print_r($this->fontlist);
		
	}

	function debug($text)
	{
		if(!$this->enable_debug) return;

		print(($text));
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

		$this->debug("Fit to width=$w, text=\"$txt\", font=$fontname, $fontstyle, $fontsize\n");

		/* Get an array of widths (getStringWidth does both these, but
		 * we also need the chars array, so no point calling StringToArray twice */
//		$width = $this->getStringWidth($txt,$fontname, $fontstyle,$fontsize,true);
		$chars = TCPDF_FONTS::UTF8StringToArray($txt, $this->isunicode, $this->CurrentFont);
		$width = $this->GetArrStringWidth($chars, $fontname, $fontstyle,$fontsize,true);
		$count = count($width); // strlen(utf8_decode($txt));

//		$this->debug("Widths ($count): " . print_r($width, true));

		$curr_width = 0; //$this->clMargin + $this->crMargin;
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
				$this->debug("Fit line from $start_index to $end_index\n");
				$lines[] = TCPDF_FONTS::UTF8ArrSubString($chars,$start_index,$end_index, $this->isunicode);
				/* Reset width, set start index */
				$curr_width = 0;
				$last_space_index = -1;
				$start_index = $index+1;
			}

		}
		
		$lines[] = TCPDF_FONTS::UTF8ArrSubString($chars,$start_index,$index,$this->isunicode);
		$this->debug("Returning lines[] = ".print_r($lines, true));
		return $lines;
	}
	/* Cell( float $w, [float $h = 0], [string $txt = ''], [mixed $border = 0],
		[int $ln = 0], [string $align = ''], [int $fill = 0], [mixed $link = ''], 
		[int $stretch = 0], [boolean $ignore_min_height = false]) */


	function FitCell($w,$h,$txt='',$border=0,$ln=1,$align='',$valign='',$on_overflow='scale')
	{
		$x = $this->getX();
		$y = $this->getY();
		$orig_fs = $this->getFontSizePt();
		$add_ellipses = false;
		$columns = 1;
		$effective_width = $w;
		$allow_multicolumn = false;

		if(strlen($txt) > 1000) {
			$allow_multicolumn = true;
		}


		if($ln > 0) {
			$fontsize = ($h/$ln) * $this->k;
			$this->debug("Calculate starting font size($h/$ln) * $this->k: $fontsize\n");
		} else {
			$fontsize = $this->getFontSizePt();
			$this->debug("lines=0, starting with default font size $fontsize\n");
		}

		$this->debug("FitCell: w=$w, h=$h, txt=\"$txt\", align=$align, valign=$valign, overflow=$on_overflow\n");
		while(1) {
			$this->debug("=> Trying font size $fontsize\n");
			$this->setFontSize($fontsize);
			$lines = $this->_cell_lines($effective_width, $txt, '', '', $fontsize);

			/* this->FontSize is always correct, we change $fontisze
			 * below, but then use that to set the internal fontsize */
			$cell_height = $this->getCellHeight($this->FontSize, false);
			$total_height = $cell_height * count($lines);

			$this->debug("=> Cell height=$cell_height, lines=".count($lines).", total_height=$total_height, fit_to_height=$h\n");
			$this->debug("=> fontsize=$fontsize, this->fontsize={$this->FontSize}, k={$this->k}, cell_height_ratio={$this->cell_height_ratio}\n");
			$this->debug("=> fontsize*ratio=".($this->FontSize * $this->cell_height_ratio));

			if($ln > 0 && count($lines) > $ln) {
				$this->debug("=> too many lines, max is $ln\n");
				/* continue into resize code below */
			} else if($total_height <= ($h * $columns) + 0.001) {  /* Stupid floating point precision */
				/* It fits! */
				$this->debug("=> Fit in height $h\n");
				break;
			}

			$this->debug("=> Doesn't fit, strategy=$on_overflow\n");

			/* else, it doesn't fit */
			if($on_overflow == 'scale') {	
				/* Try to scale the font intelligently, this gets
				 * us to a fit font faster , use count-1 so if we just spill over on 2 lines, 
				 * we don't end up taking the font scale by 50% lower and significantly undersizing it */
				$scale = $h / ($cell_height * (count($lines)-1));
				if($scale > 0.5 && $scale < 1.0) {
					$fontsize *= $scale;
				}
				$fontsize -= 0.5;

				/* reduce the font size and try again */
				$this->debug("=> Reduce fontsize to $fontsize (scale=$scale, -0.5)\n");

				if($fontsize < 6 && $columns == 1 && $allow_multicolumn) {
					/* Switch to columns */
					$columns = 2;
					$fontsize = 8;
					$effective_width = ($w / $columns) - (5*($columns-1));
				}
				continue;
			} 

			/* If it doesn't fit, and we're not scaling, it must
			 * be a truncate.  Compute the number of lines that 
			 * can be displayed */
			$display_lines = floor($h / $cell_height);

			/* truncate */
			$lines = array_slice($lines, 0, $display_lines);

			$this->debug("=> Display lines=$display_lines, truncate array to: ".print_r($lines, true));

			if($on_overflow == '...') $add_ellipses = true;
			break;
		}

		/* SetX, find Y based on alignment */
		switch($valign) {
		case 'M': /* Middle */
			$top_y = $y + ($h - $total_height) / 2;
			break;
		case 'B': /* Bottom */
			$top_y = $y + ($h - $total_height);
			break;
		case 'T': default: /* Top */
			$top_y = $y;
			break;
		}
		$this->SetXY($x, $top_y);

		/* Fontsize will be correctly set here */
		/* Cell( float $w, [float $h = 0], [string $txt = ''], [mixed $border = 0],
			[int $ln = 0], [string $align = ''], [int $fill = 0], [mixed $link = ''], 
			[int $stretch = 0], [boolean $ignore_min_height = false]) */
		$c = 0;
		$lines_per_column = intval(count($lines) / $columns + 1);
		$current_column = 0;
		foreach($lines as $l) {
			$this->debug("Cell output line: \"$l\"\n");
			$this->Cell($effective_width, 0, $l, 0, 2, $align, 0, 0, 0, false);
			$c++;
			if($c == $lines_per_column) {
				/* Move to the next column */
				$current_column += 1;
				$this->SetXY($x + (($effective_width+5) * $current_column), $top_y);
			}
		}

		if($add_ellipses) {
			/* Only use fontsize so the '...' is really close to the lower right. */
			$this->SetXY($x, $y + $h - $cell_height);
			$this->Cell($w, 0, '...', 0, 0, 'R');
		}

		$this->last_cell_font_size = $fontsize;

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

	function set_use_abs_coords($use_abs_coords) 
	{
		$this->label_use_abs_coords = $use_abs_coords;
	}

	function setup_for_labels($show_box, $show_fair, $show_logo, $width, $height, $xspacer, $yspacer, $rows, $cols)
	{
		/* No headers and footers */
		$this->setPrintHeader(false);
		$this->setPrintFooter(false);

		/* No auto-pagebreaks */
		$this->SetAutoPageBreak(false);

		/* Cells exactly the size to fit text, default is 1.25 height ratio
		 * set all-round padding to zero too 
		 * cell height = FontSize * cell_height_ratio + top padding + bottom padding */
		$this->setCellHeightRatio(1.0);
		$this->setCellPaddings(0, 0, 0, 0);

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

		$this->label_use_abs_coords = false;

		/* labels are always centered in the page */
		
		$this->label_page_lmargin=( $pw - ($cols*$width) - ($xspacer*($cols-1)) )/2;
		$this->label_page_tmargin=( $ph - ($rows*$height) - ($yspacer*($rows-1)) )/2;

		/* Setup so that the first call to label_new also creates
		 * a new page */
		$this->current_label_index = $this->labels_per_page - 1;

		$this->label_header_font_size = 0;

		$this->debug("Label is {$this->label_width}x{$this->label_height}\n");
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
			$this->Rect($this->lMargin, $this->tMargin, $this->label_width, $this->label_height);

		$logo_width = 0;
		$header_height = $this->label_height * 0.1;
		if($header_height > 10) $header_height = 10;

		if($this->label_show_logo) {
			/* Logo at 10% label height */
			$logo_width = $header_height;
			$this->Image("data/logo.png", $this->lMargin + 0.5, $this->tMargin + 0.5, 
				$logo_width, $logo_width, '', '', '', true, 300, '', false, false, 0, true);
			$logo_width += 1;
		}

		if($this->label_show_fair) {
			/* Text beside the logo */
			$ln = 1;

			$this->SetXY($this->lMargin + $logo_width, $this->tMargin);
			if($this->label_header_font_size != 0) {
				/* Cache the label header font size, and reuse that
				 * so we can save some time instead of always recomputing
				 * text on the label header */
				$this->setFontSize($this->label_header_font_size);
				$ln = 0;
			}

			$this->FitCell(($this->label_width - $logo_width) * .9, $header_height+1, 
					"{$config['fair_name']} {$config['year']}",
					0, $ln, 'L', 'M', 'scale');
			$this->label_header_font_size = $this->last_cell_font_size;
			
			$this->Line(0 + $this->lMargin, $header_height+1 + $this->tMargin, 
					$this->label_width +  $this->lMargin, $header_height + 1 + $this->tMargin);
			/* Bring down the top margin */
			$this->SetTopMargin($tmargin + $header_height + 2);
		}

	}

	function x($x) 
	{
		$factor = $this->label_use_abs_coords ? 1.0 : ($this->label_width / 100.0);
		return ($x * $factor) + $this->lMargin;
	}
	function y($y) 
	{
		$lh = $this->label_height - ( $this->label_show_fair ? $this->label_height * 0.1 : 0.0);
		$factor = $this->label_use_abs_coords ? 1.0 : ($lh / 100.0);
		return ($y * $factor) + $this->tMargin;
	}
	function w($w) 
	{
		$factor = $this->label_use_abs_coords ? 1.0 : ($this->label_width / 100.0);
		return ($w * $factor);
	}
	function h($h) 
	{
		$lh = $this->label_height - ( $this->label_show_fair ? $this->label_height * 0.1 : 0.0);
		$factor = $this->label_use_abs_coords ? 1.0 : ($lh / 100.0);
		return ($h * $factor);
	}

	/* 	align = left, center, right
		valign = top, middle, bottom,
		fontname,
		fontstyle,
		fontsize,
		border = true/false
		on_overflow = truncate, ..., scale */

	function label_text($x,$y,$w,$h,$text,$border,$align='center',$valign='middle',
				$max_lines=1, 
				$fontname='helvetica',$fontstyle='',$fontsize='6',
				$on_overflow='scale')
	{
		$this->debug("Label ($x,$y) $w x $h \"$text\" ($fontname, $fontsize) align($align, $valign)\n");
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

		$this->SetXY($this->x($x), $this->y($y));
		$this->debug("=> Actual pos (".$this->x($x).",".$this->y($y).")\n");
		$this->SetFont($fontname, $fs, $fontsize);

		/* Print text */
		$this->FitCell($this->w($w),$this->h($h), $text,  $border ? 1 : 0, $max_lines, 
				$align, $valign, $on_overflow);

		/* Restore position and font */
		$this->SetFont($orig_name, $orig_style, $orig_size);
		$this->SetXY($orig_x, $orig_y);
	}

	function label_html($x,$y,$w,$h,$text,$border,$align='center',$valign='middle',
				$max_lines=1, 
				$fontname='helvetica',$fontstyle='',$fontsize='6',
				$on_overflow='scale')
	{
		$this->debug("HTML label ($x,$y) $w x $h \"$text\" ($fontname, $fontsize)\n");
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

		$this->SetXY($this->x($x), $this->y($y));
		$this->debug("=> Actual pos (".$this->x($x).",".$this->y($y)."\n");
		$this->SetFont($fontname, $fs, $fontsize);

		/* Print text */
		$this->FitCell($this->w($w),$this->h($h), $text,  $border ? 1 : 0, $max_lines, 
				$align, $valign, $on_overflow);

		/* Restore position and font */
		$this->SetFont($orig_name, $orig_style, $orig_size);
		$this->SetXY($orig_x, $orig_y);
	}	

	function label_rect($x,$y,$w,$h) 
	{
		$this->Rect($this->x($x), $this->y($y), $this->w($w), $this->h($h));
	}

	function label_line($x,$y,$x2,$y2) 
	{
		$this->Line($this->x($x), $this->y($y), $this->x($x2), $this->x($y2));
	}

	function label_fair_logo($x, $y, $w, $h, $show_box)
	{
		global $config;
		/* Scale image to proportinally fit in w x h */
		$this->Image("data/logo.jpg", $this->x($x), $this->y($y), $this->w($w), $this->h($h),
				'', '', '', true,
				300, '', false, false, $show_box ? 1 : 0, true);
	}

	function label_barcode($x, $y, $w, $h, $val) 
	{
		$style = array(
			'border' => 2,
			'vpadding' => 'auto',
			'hpadding' => 'auto',
			'fgcolor' => array(0,0,0),
			'bgcolor' => false, //array(255,255,255)
			'module_width' => 2, // width of a single module in points
			'module_height' => 2 // height of a single module in points
		);
		$this->write2DBarcode($val, 'QRCODE,H', $this->x($x), $this->y($y), 
					$this->w($w), $this->h($h), $style, 'N');
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

	function setup_for_tables($fontname='helvetica', $fontsize=10)
	{
		$fontstyle = '';
		$this->SetFont($fontname, $fontstyle, $fontsize);

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

			$td_style = '';
			if(array_key_exists('cell_border', $table)) {
				if($table['cell_border'] == true) {
					$td_style = 'border:1px solid black;';
				}
			}

			$row_alternator = !$row_alternator;

			$html .= "<tr $style >";
			foreach($table['fields'] as $f) {
				/* Convert all entities to HTML, even UTF characters.  Without this
				 * TCPDF won't add a table if it has a UTF char */
				$d = htmlentities(utf8_decode($row[$f]));
				$d = str_replace("\n", "<br/>", $d);

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

				$html .= "<td width=\"{$table['widths'][$f]}mm\" align=\"{$table['col'][$f]['align']}\" style=\"$td_style\" >$d</td>";
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
		$txt = htmlspecialchars($txt);
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
